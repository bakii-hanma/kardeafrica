<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WahaService — WhatsApp HTTP API (WAHA) self-hébergé sur le VPS.
 *
 * Remplace WHAPI (dont la version d'essai est épuisée). Permet :
 *   - de CONNECTER un numéro WhatsApp par QR code (depuis l'admin),
 *   - d'ENVOYER les codes OTP (texte + image personnalisée KardAfrica).
 *
 * Auth : header X-Api-Key (clé stockée en .env, jamais exposée au navigateur —
 * le QR et les statuts passent par un proxy admin côté serveur).
 */
class WahaService
{
    private string $base;
    private ?string $key;
    private string $session;

    public function __construct()
    {
        $this->base    = rtrim((string) config('services.waha.base_url', 'http://localhost:3000'), '/');
        $this->key     = config('services.waha.api_key');
        $this->session = (string) config('services.waha.session', 'default');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->key);
    }

    private function http()
    {
        return Http::withHeaders(['X-Api-Key' => (string) $this->key])->timeout(20);
    }

    /** Numéro E.164 (sans +) → chatId WhatsApp (forme simple @c.us). */
    public static function chatId(string $phoneE164): string
    {
        $digits = preg_replace('/\D/', '', $phoneE164);
        return $digits . '@c.us';
    }

    /**
     * Résout le chatId RÉEL d'un numéro. WhatsApp bascule vers l'adressage
     * « @lid » : pour beaucoup de contacts, l'envoi direct en @c.us échoue avec
     * « No LID for user ». On interroge check-exists pour obtenir le bon chatId
     * (@lid ou @c.us) ; à défaut on retombe sur @c.us.
     */
    public function resolveChatId(string $phoneE164): string
    {
        $digits = preg_replace('/\D/', '', $phoneE164);
        try {
            $r = $this->http()->get("{$this->base}/api/contacts/check-exists", [
                'phone'   => $digits,
                'session' => $this->session,
            ]);
            if ($r->successful()) {
                $j = $r->json();
                if (! empty($j['numberExists']) && ! empty($j['chatId'])) {
                    return (string) $j['chatId'];
                }
            }
        } catch (\Throwable $e) {
            // ignore → fallback
        }
        return $digits . '@c.us';
    }

    /** Statut de la session : STOPPED | STARTING | SCAN_QR_CODE | WORKING | FAILED. */
    public function status(): array
    {
        try {
            $r = $this->http()->get("{$this->base}/api/sessions/{$this->session}");
            if ($r->successful()) {
                return $r->json();
            }
            return ['status' => 'UNKNOWN', 'error' => 'http_' . $r->status()];
        } catch (\Throwable $e) {
            return ['status' => 'UNREACHABLE', 'error' => $e->getMessage()];
        }
    }

    /** Crée la session si besoin puis la démarre (→ SCAN_QR_CODE). */
    public function start(): bool
    {
        try {
            // Crée la session (ignore l'erreur si elle existe déjà).
            $this->http()->post("{$this->base}/api/sessions", ['name' => $this->session]);
            $r = $this->http()->post("{$this->base}/api/sessions/{$this->session}/start");
            return $r->successful();
        } catch (\Throwable $e) {
            Log::warning('WAHA start exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function restart(): bool
    {
        try {
            $this->http()->post("{$this->base}/api/sessions/{$this->session}/stop");
            return $this->start();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Déconnecte le numéro (logout WhatsApp). */
    public function logout(): bool
    {
        try {
            $this->http()->post("{$this->base}/api/sessions/{$this->session}/logout");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * QR code (PNG binaire) pour connecter le numéro. Retourne null si la session
     * n'est pas en attente de scan.
     *
     * @return array{ok:bool, body:?string, contentType:?string, status:?int}
     */
    public function qrPng(): array
    {
        try {
            $r = $this->http()->get("{$this->base}/api/{$this->session}/auth/qr");
            return [
                'ok'          => $r->successful(),
                'body'        => $r->successful() ? $r->body() : null,
                'contentType' => $r->header('Content-Type') ?: 'image/png',
                'status'      => $r->status(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'body' => null, 'contentType' => null, 'status' => 0];
        }
    }

    /** Le numéro est-il un compte WhatsApp ? null si indéterminé. */
    public function numberExists(string $phoneE164): ?bool
    {
        $digits = preg_replace('/\D/', '', $phoneE164);
        try {
            $r = $this->http()->get("{$this->base}/api/contacts/check-exists", [
                'phone'   => $digits,
                'session' => $this->session,
            ]);
            if ($r->successful()) {
                $j = $r->json();
                return isset($j['numberExists']) ? (bool) $j['numberExists'] : null;
            }
        } catch (\Throwable $e) {
            // indéterminé
        }
        return null;
    }

    /** Liste des conversations récentes. */
    public function listChats(int $limit = 30): array
    {
        try {
            $r = $this->http()->get("{$this->base}/api/{$this->session}/chats/overview", ['limit' => $limit]);
            if (! $r->successful()) {
                $r = $this->http()->get("{$this->base}/api/{$this->session}/chats", ['limit' => $limit]);
            }
            return $r->successful() ? (array) $r->json() : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Messages d'une conversation (chatId = 2417...@c.us). */
    public function getMessages(string $chatId, int $limit = 40): array
    {
        try {
            $r = $this->http()->get("{$this->base}/api/{$this->session}/chats/" . rawurlencode($chatId) . "/messages", [
                'limit' => $limit, 'downloadMedia' => false,
            ]);
            return $r->successful() ? (array) $r->json() : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Envoi direct à un chatId déjà formaté. */
    public function sendToChat(string $chatId, string $text): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }
        try {
            $r = $this->http()->post("{$this->base}/api/sendText", [
                'session' => $this->session,
                'chatId'  => $chatId,
                'text'    => $text,
            ]);
            return $r->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** Envoi d'un message texte. */
    public function sendText(string $phoneE164, string $text): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }
        try {
            $r = $this->http()->post("{$this->base}/api/sendText", [
                'session' => $this->session,
                'chatId'  => $this->resolveChatId($phoneE164),
                'text'    => $text,
            ]);
            if (! $r->successful()) {
                Log::error('WAHA sendText échoué', ['to' => $phoneE164, 'status' => $r->status()]);
            }
            return $r->successful();
        } catch (\Throwable $e) {
            Log::error('WAHA sendText exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Envoi d'une image (base64) avec légende.
     *
     * @param string $pngBinary  contenu binaire PNG
     */
    public function sendImage(string $phoneE164, string $pngBinary, string $caption = '', string $filename = 'kardafrica.png'): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }
        try {
            $r = $this->http()->post("{$this->base}/api/sendImage", [
                'session' => $this->session,
                'chatId'  => $this->resolveChatId($phoneE164),
                'caption' => $caption,
                'file'    => [
                    'mimetype' => 'image/png',
                    'filename' => $filename,
                    'data'     => base64_encode($pngBinary),
                ],
            ]);
            if (! $r->successful()) {
                Log::error('WAHA sendImage échoué', ['to' => $phoneE164, 'status' => $r->status()]);
            }
            return $r->successful();
        } catch (\Throwable $e) {
            Log::error('WAHA sendImage exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
