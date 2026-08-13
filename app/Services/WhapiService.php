<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhapiService — passerelle WhatsApp via WHAPI (https://whapi.cloud).
 *
 * Point d'entrée UNIQUE pour parler à l'API WHAPI. Le token vit uniquement
 * côté serveur (config/services.php). Tolérant aux pannes : ne lève jamais
 * d'exception pour les helpers booléens historiques (OTP, notifs admin) ;
 * la méthode bas-niveau `send()` renvoie un résultat normalisé pour que le
 * job d'envoi puisse capturer l'id de message et le statut.
 *
 * ⚠️ Politique : on n'envoie JAMAIS de code/PIN de carte via WHAPI (passerelle
 * tierce). Les notifications d'achat pointent vers un lien sécurisé.
 *
 * Numéros attendus : E.164 SANS « + » (ex. 24106871309).
 */
class WhapiService
{
    private ?string $baseUrl;
    private ?string $token;

    /** endpoint WHAPI par type de message. */
    private const ENDPOINTS = [
        'text'        => '/messages/text',
        'image'       => '/messages/image',
        'document'    => '/messages/document',
        'video'       => '/messages/video',
        'audio'       => '/messages/audio',
        'interactive' => '/messages/interactive',
    ];

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.whapi.base_url'), '/');
        $this->token   = config('services.whapi.token');
    }

    /** L'intégration est-elle configurée (token présent) ? */
    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->baseUrl);
    }

    /** Normalise un numéro en E.164 sans « + » (garde seulement les chiffres). */
    public static function normalize(string $phone): string
    {
        // Les JID WhatsApp (groupes @g.us, channels @newsletter) sont des
        // destinataires valides tels quels — ne pas les altérer.
        if (str_contains($phone, '@')) {
            return trim($phone);
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        // 00xxxx (préfixe international) → xxxx
        return preg_replace('/^00/', '', $digits) ?? $digits;
    }

    // ------------------------------------------------------------------
    // Bas-niveau : chemin HTTP unique
    // ------------------------------------------------------------------

    /**
     * Envoie une charge utile à WHAPI pour un type de message donné.
     *
     * @return array{ok:bool,id:?string,status:int,error:?string}
     */
    public function send(string $type, string $phone, array $payload): array
    {
        $endpoint = self::ENDPOINTS[$type] ?? null;
        if ($endpoint === null) {
            return ['ok' => false, 'id' => null, 'status' => 0, 'error' => "type inconnu: {$type}"];
        }

        if (!$this->isConfigured()) {
            Log::warning('WHAPI non configuré — message non envoyé', [
                'type' => $type,
                'to'   => $phone,
            ]);
            return ['ok' => false, 'id' => null, 'status' => 0, 'error' => 'not_configured'];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($this->token)
                ->acceptJson()
                ->post($this->baseUrl . $endpoint, array_merge(['to' => $phone], $payload));

            $json = is_array($response->json()) ? $response->json() : [];

            if (!$response->successful()) {
                $error = $json['error']['message'] ?? ($json['error'] ?? 'http_' . $response->status());
                Log::error('WHAPI envoi échoué', [
                    'type'   => $type,
                    'to'     => $phone,
                    'status' => $response->status(),
                    'error'  => is_string($error) ? $error : json_encode($error),
                ]);
                return ['ok' => false, 'id' => null, 'status' => $response->status(),
                        'error' => is_string($error) ? $error : 'error'];
            }

            // WHAPI renvoie typiquement { sent:true, message:{ id:... } } ou { id:... }.
            $id = $json['message']['id'] ?? $json['id'] ?? null;
            return ['ok' => true, 'id' => $id, 'status' => $response->status(), 'error' => null];
        } catch (\Throwable $e) {
            Log::error('WHAPI exception réseau', ['type' => $type, 'to' => $phone, 'error' => $e->getMessage()]);
            return ['ok' => false, 'id' => null, 'status' => 0, 'error' => 'network'];
        }
    }

    // ------------------------------------------------------------------
    // Helpers typés
    // ------------------------------------------------------------------

    /** Message texte. Renvoie true si accepté par WHAPI. */
    public function sendText(string $phoneE164, string $message): bool
    {
        return $this->send('text', $phoneE164, ['body' => $message])['ok'];
    }

    /** Image depuis une URL publique (+ légende optionnelle). */
    public function sendImage(string $phoneE164, string $mediaUrl, ?string $caption = null): array
    {
        $payload = ['media' => $mediaUrl];
        if ($caption !== null && $caption !== '') {
            $payload['caption'] = $caption;
        }
        return $this->send('image', $phoneE164, $payload);
    }

    /** Document (PDF, etc.) depuis une URL publique. */
    public function sendDocument(string $phoneE164, string $mediaUrl, ?string $filename = null, ?string $caption = null): array
    {
        $payload = ['media' => $mediaUrl];
        if ($filename !== null) $payload['filename'] = $filename;
        if ($caption !== null && $caption !== '') $payload['caption'] = $caption;
        return $this->send('document', $phoneE164, $payload);
    }

    /**
     * Message à boutons de réponse rapide.
     *
     * @param array<int,array{id:string,title:string}> $buttons  (max 3, titres ≤ 20 car.)
     */
    public function sendButtons(string $phoneE164, string $body, array $buttons, ?string $footer = null): array
    {
        $action = array_map(
            fn ($b) => ['type' => 'quick_reply', 'title' => mb_substr($b['title'], 0, 20), 'id' => $b['id']],
            array_slice(array_values($buttons), 0, 3),
        );

        $payload = [
            'type'   => 'button',
            'body'   => ['text' => $body],
            'action' => ['buttons' => $action],
        ];
        if ($footer !== null && $footer !== '') {
            $payload['footer'] = ['text' => $footer];
        }
        return $this->send('interactive', $phoneE164, $payload);
    }

    /**
     * Message « liste » (menu déroulant).
     *
     * @param array<int,array{title:string,rows:array<int,array{id:string,title:string,description?:string}>}> $sections
     */
    public function sendList(string $phoneE164, string $body, string $buttonLabel, array $sections, ?string $footer = null): array
    {
        $payload = [
            'type'   => 'list',
            'body'   => ['text' => $body],
            'action' => ['list' => ['label' => mb_substr($buttonLabel, 0, 20), 'sections' => $sections]],
        ];
        if ($footer !== null && $footer !== '') {
            $payload['footer'] = ['text' => $footer];
        }
        return $this->send('interactive', $phoneE164, $payload);
    }

    /** Notifie le numéro admin configuré (si présent). */
    public function notifyAdmin(string $message): bool
    {
        $admin = config('services.whapi.admin_number');
        if (empty($admin)) {
            Log::info('WHAPI admin_number non configuré — notification admin ignorée');
            return false;
        }
        return $this->sendText($admin, $message);
    }

    /** Notifie l'agent de support (escalade humaine), si configuré. */
    public function notifyAgent(string $message): bool
    {
        $agent = config('services.whapi.agent_number') ?: config('services.whapi.admin_number');
        if (empty($agent)) {
            Log::info('WHAPI agent_number/admin_number non configuré — escalade ignorée');
            return false;
        }
        return $this->sendText($agent, $message);
    }

    // ------------------------------------------------------------------
    // Catalogue WhatsApp Business (Phase 4 — nécessite un compte Business lié)
    // ------------------------------------------------------------------

    /**
     * Crée un produit dans le catalogue WhatsApp Business.
     * @return array{ok:bool,id:?string,error:?string}
     */
    public function createProduct(array $product): array
    {
        return $this->business('post', '/business/products', $product);
    }

    /** Liste les produits du catalogue Business. */
    public function getProducts(): array
    {
        return $this->business('get', '/business/products');
    }

    /** Supprime un produit du catalogue Business. */
    public function deleteProduct(string $productId): array
    {
        return $this->business('delete', "/business/products/{$productId}");
    }

    /** Appel générique aux endpoints /business/* (catalogue). */
    private function business(string $method, string $endpoint, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'id' => null, 'error' => 'not_configured', 'data' => null];
        }
        try {
            $req = Http::timeout(20)->withToken($this->token)->acceptJson();
            $response = match ($method) {
                'post'   => $req->post($this->baseUrl . $endpoint, $payload),
                'delete' => $req->delete($this->baseUrl . $endpoint),
                default  => $req->get($this->baseUrl . $endpoint),
            };
            $json = is_array($response->json()) ? $response->json() : [];
            if (!$response->successful()) {
                return ['ok' => false, 'id' => null, 'error' => 'http_' . $response->status(), 'data' => null];
            }
            return ['ok' => true, 'id' => $json['id'] ?? ($json['product']['id'] ?? null), 'error' => null, 'data' => $json];
        } catch (\Throwable $e) {
            Log::error('WHAPI business exception', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['ok' => false, 'id' => null, 'error' => 'network', 'data' => null];
        }
    }
}
