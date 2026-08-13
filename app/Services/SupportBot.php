<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Support\Money;
use Illuminate\Support\Facades\Log;

/**
 * SupportBot — assistant de support client WhatsApp (Mistral + function-calling).
 *
 * Décisions produit :
 *  - Autonome AVEC accès aux commandes, mais strictement limité au compte du
 *    contact (rapprochement par numéro WhatsApp vérifié) ou, à défaut, à une
 *    commande dont on vérifie le n° + l'email.
 *  - Ne révèle ni ne demande JAMAIS de code/PIN, mot de passe ou n° de carte
 *    bancaire — pointe vers l'espace client (page authentifiée).
 *  - Escalade vers un agent humain (+ alerte admin) quand il bloque.
 *
 * Tolérant aux pannes : si Mistral est indisponible, on répond poliment et on
 * escalade plutôt que de rester muet.
 */
class SupportBot
{
    private const MAX_TOOL_ROUNDS = 4;
    private const HISTORY_LIMIT   = 10;

    public function __construct(
        private MistralService $mistral,
        private WhatsAppNotifier $notifier,
        private ProductApiService $catalog,
    ) {}

    public function handle(WhatsAppMessage $inbound): void
    {
        $phone = $inbound->phone;
        $user  = $this->resolveUser($phone);

        if (!$this->mistral->isConfigured()) {
            $this->reply($phone, "Merci pour votre message 🙏 Un conseiller KardAfrica vous répond dès que possible.");
            $this->escalate($phone, 'Mistral non configuré', $inbound->body);
            return;
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $this->systemPrompt($user)]],
            $this->history($phone),
        );

        $tools = $this->tools();

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $res = $this->mistral->chat($messages, $tools);

            if (!$res['ok']) {
                $this->reply($phone, "Je rencontre un souci technique momentané 😅 Un conseiller prend le relais.");
                $this->escalate($phone, 'Mistral erreur: ' . ($res['error'] ?? '?'), $inbound->body);
                return;
            }

            $assistant = $res['message'];
            $toolCalls = $assistant['tool_calls'] ?? [];

            // Pas d'appel d'outil → réponse finale.
            if (empty($toolCalls)) {
                $text = trim((string) ($assistant['content'] ?? ''));
                $this->reply($phone, $text !== '' ? $text : "Comment puis-je vous aider ? 💬");
                return;
            }

            // On rejoue le message assistant (avec ses tool_calls) puis les résultats.
            $messages[] = $assistant;
            foreach ($toolCalls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                $result = $this->executeTool($name, $args, $user, $phone, $inbound);
                $messages[] = [
                    'role'         => 'tool',
                    'name'         => $name,
                    'tool_call_id' => $call['id'] ?? '',
                    'content'      => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        // Trop de tours d'outils sans réponse finale → escalade.
        $this->reply($phone, "Je transmets votre demande à un conseiller pour un suivi personnalisé. 🙌");
        $this->escalate($phone, 'Boucle outils dépassée', $inbound->body);
    }

    // ------------------------------------------------------------------
    // Prompt & historique
    // ------------------------------------------------------------------

    private function systemPrompt(?User $user): string
    {
        $identity = $user
            ? "Le contact est identifié : compte de « {$user->name} » (email {$user->email}). Tu peux consulter SES commandes."
            : "Le contact n'est PAS identifié. Pour toute question sur une commande précise, demande poliment le NUMÉRO de commande ET l'email utilisé à l'achat (l'outil en a besoin).";

        return <<<TXT
Tu es l'assistant de support de KardAfrica, une marketplace de cartes cadeaux numériques en Afrique (Gabon). Paiement par Mobile Money (Airtel Money, Moov Money) ou carte bancaire ; le code de la carte arrive en ~30 secondes dans l'espace client de l'acheteur.

CE QUE NOUS VENDONS — plus de 300 marques, dont :
- streaming et divertissement (Netflix, Prime Video, Crunchyroll…) ;
- jeux vidéo (Steam, PlayStation/PSN, Xbox, Nintendo, Roblox, Riot, Epic) ;
- musique (Deezer, Spotify, Apple Music) ;
- shopping et applis (Apple/App Store, Google Play, Amazon) ;
- intelligence artificielle (ChatGPT via Rewarble) ;
- CRYPTO : cartes de rechargement Binance, GatePay, Crypto Voucher, Bitnovo, Gift Me Crypto — pour alimenter un compte crypto sans carte bancaire ;
- cartes cadeaux LOCALES « Carte Gabon » de commerçants de Libreville (restaurants, salons, supermarchés…).

RÈGLES ABSOLUES :
- Réponds en français, de façon concise et chaleureuse (c'est WhatsApp). Émojis avec parcimonie.
- Ne révèle JAMAIS et ne demande JAMAIS un code/PIN de carte, un mot de passe ou un numéro de carte bancaire. Pour récupérer un code, oriente vers l'espace client (lien fourni par les outils).
- Ne donne jamais un statut de commande ou un prix « de mémoire » : utilise les outils.
- Utilise `get_order_status` pour les questions de commande, `search_catalog` pour la disponibilité/prix d'une carte.
- Ne dis JAMAIS qu'une carte est indisponible sans avoir appelé `search_catalog` d'abord. Le catalogue évolue en permanence.
- Ne confonds pas une MARQUE VENDUE avec un moyen de paiement. Binance, PayPal, Visa ou Mastercard cités par un client désignent presque toujours une carte à acheter chez nous : cherche-la. Les seuls moyens de PAIEMENT acceptés sont Airtel Money, Moov Money et la carte bancaire.
- Les prix renvoyés par les outils sont en FCFA, prix de vente réel : reprends-les tels quels, ne les recalcule pas.
- Si tu ne peux pas résoudre, si le client est mécontent, demande un humain, un remboursement, ou un problème de compte/paiement complexe : appelle `escalate_to_human`.

CONTEXTE : {$identity}
TXT;
    }

    /** Reconstitue l'historique récent de la conversation pour ce numéro. */
    private function history(string $phone): array
    {
        $rows = WhatsAppMessage::where('phone', $phone)
            ->whereIn('direction', [WhatsAppMessage::DIR_IN, WhatsAppMessage::DIR_OUT])
            ->whereIn('category', [WhatsAppMessage::CAT_SUPPORT])
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse();

        $out = [];
        foreach ($rows as $r) {
            $content = trim((string) $r->body);
            if ($content === '') continue;
            $out[] = [
                'role'    => $r->direction === WhatsAppMessage::DIR_IN ? 'user' : 'assistant',
                'content' => $content,
            ];
        }
        // Filet de sécurité : garantir au moins un tour utilisateur.
        if (empty($out)) {
            $out[] = ['role' => 'user', 'content' => 'Bonjour'];
        }
        return $out;
    }

    // ------------------------------------------------------------------
    // Outils (function-calling)
    // ------------------------------------------------------------------

    private function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_order_status',
                    'description' => "Récupère le statut d'une commande KardAfrica. Si le contact est identifié, renvoie sa dernière commande (ou celle du numéro fourni). Sinon, exige order_number ET email. Ne renvoie jamais de code de carte.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'order_number' => ['type' => 'string', 'description' => "Numéro de commande (ex. KA-XXXX), optionnel si contact identifié"],
                            'email'        => ['type' => 'string', 'description' => "Email utilisé à l'achat, requis si le contact n'est pas identifié"],
                        ],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_catalog',
                    'description' => "Recherche une carte cadeau dans le catalogue KardAfrica par nom de marque et renvoie le prix « à partir de ».",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => "Nom de la marque/carte recherchée (ex. Netflix, Xbox)"],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'send_product_fiche',
                    'description' => "Envoie au client une fiche visuelle (image + prix + lien) de la carte demandée. À utiliser quand le client veut voir/acheter une carte précise.",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => ['type' => 'string', 'description' => "Nom de la marque/carte (ex. Netflix)"],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'escalate_to_human',
                    'description' => "Transfère la conversation à un conseiller humain (client mécontent, remboursement, problème de compte/paiement, ou impossible à résoudre).",
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'reason' => ['type' => 'string', 'description' => "Motif court de l'escalade"],
                        ],
                        'required' => ['reason'],
                    ],
                ],
            ],
        ];
    }

    private function executeTool(string $name, array $args, ?User $user, string $phone, WhatsAppMessage $inbound): array
    {
        return match ($name) {
            'get_order_status'    => $this->toolOrderStatus($args, $user),
            'search_catalog'      => $this->toolSearchCatalog($args),
            'send_product_fiche'  => $this->toolSendFiche($args, $phone),
            'escalate_to_human'   => $this->toolEscalate($args, $phone, $inbound),
            default               => ['error' => "outil inconnu: {$name}"],
        };
    }

    private function toolOrderStatus(array $args, ?User $user): array
    {
        $orderNumber = trim((string) ($args['order_number'] ?? ''));
        $email       = trim((string) ($args['email'] ?? ''));

        $order = null;
        if ($user) {
            $q = Order::where('user_id', $user->id);
            if ($orderNumber !== '') $q->where('order_number', $orderNumber);
            $order = $q->latest()->first();
        } elseif ($orderNumber !== '' && $email !== '') {
            // Non identifié : vérification stricte n° + email (billing ou compte).
            $order = Order::where('order_number', $orderNumber)
                ->where(function ($q) use ($email) {
                    $q->where('billing_details->email', $email)
                      ->orWhereHas('user', fn ($u) => $u->where('email', $email));
                })->first();
        } else {
            return ['found' => false, 'need' => 'order_number_and_email',
                    'message' => "Demande le numéro de commande ET l'email d'achat."];
        }

        if (!$order) {
            return ['found' => false, 'message' => 'Aucune commande correspondante.'];
        }

        return [
            'found'          => true,
            'order_number'   => $order->order_number,
            'status'         => $order->status_label,
            'payment_status' => $order->payment_status_label,
            'items_count'    => $order->orderItems()->count(),
            'completed'      => (bool) $order->completed_at,
            // Lien vers l'espace client (page authentifiée) — jamais le code.
            'espace_client'  => route('orders.show', $order),
        ];
    }

    /**
     * Classe les résultats pour un client au Gabon ou en France : les éditions
     * qu'il peut RÉELLEMENT utiliser d'abord (France, puis euro, puis mondial),
     * et à rang égal la moins chère. Sans ce tri, afrikard renvoie son ordre
     * brut et le bot proposait des cartes Netflix Brésil en réals à Libreville.
     */
    private function rankForLocalCustomer(array $products): array
    {
        $rank = function (array $p): int {
            $cc  = strtoupper($p['cardType']['countryCode'] ?? '');
            $cur = strtoupper($p['price']['currencyCode'] ?? '');

            if ($cc === 'FR')                                   return 0;
            if (in_array($cc, ['BE', 'EU'], true))              return 1;
            if (($p['cardType']['region'] ?? '') === 'europe')  return 2;
            if (in_array($cc, ['WW', 'GLC', 'GLOBAL', 'GL'], true)) return 3;
            if ($cur === 'EUR')                                 return 4;
            if ($cc === 'US' || $cur === 'USD')                 return 5;
            return 9;   // marchés tiers (BRL, TRY, MXN…) : inactivables ici
        };

        usort($products, function ($a, $b) use ($rank) {
            $ra = $rank($a);
            $rb = $rank($b);
            if ($ra !== $rb) return $ra <=> $rb;
            return ($a['price']['min'] ?? PHP_INT_MAX) <=> ($b['price']['min'] ?? PHP_INT_MAX);
        });

        return $products;
    }

    private function toolSearchCatalog(array $args): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        if ($query === '') {
            return ['results' => []];
        }

        $products = $this->rankForLocalCustomer($this->catalog->searchProductsViaApi($query, 10));
        $results = [];
        foreach (array_slice($products, 0, 3) as $p) {
            $min = $p['price']['min'] ?? null;
            $cur = $p['price']['currencyCode'] ?? 'XAF';
            $ctId = $p['cardType']['id'] ?? $p['cardType']['internalId'] ?? null;
            $results[] = [
                'name'        => $p['name'] ?? ($p['cardType']['name'] ?? 'Carte'),
                'price_fcfa'  => $min ? Money::formatFcfa($min, $cur) : null,
                'face_value'  => Money::formatOriginal($p['minFaceValue'] ?? $min, $cur),
                'link'        => $ctId ? route('card-type.show', $ctId) : route('boutique'),
            ];
        }

        return ['results' => $results, 'boutique' => route('boutique')];
    }

    /** Envoie une fiche produit visuelle (image OG + prix + lien) au client. */
    private function toolSendFiche(array $args, string $phone): array
    {
        $query = trim((string) ($args['query'] ?? ''));
        if ($query === '') {
            return ['sent' => false, 'message' => 'query manquant'];
        }

        // Même classement que la recherche : on envoie la fiche de la carte
        // utilisable ici, pas la première que renvoie afrikard.
        $products = $this->rankForLocalCustomer($this->catalog->searchProductsViaApi($query, 5));
        $p = $products[0] ?? null;
        if (!$p) {
            return ['sent' => false, 'message' => 'aucune carte trouvée'];
        }

        $min  = $p['price']['min'] ?? null;
        $cur  = $p['price']['currencyCode'] ?? 'XAF';
        $name = $p['name'] ?? ($p['cardType']['name'] ?? 'Carte');
        $ctId = $p['cardType']['id'] ?? $p['cardType']['internalId'] ?? null;
        $link = $ctId ? route('card-type.show', $ctId) : route('boutique');

        $caption = "*{$name}*"
            . ($min ? "\nÀ partir de " . Money::formatFcfa($min, $cur) : '')
            . "\n👉 {$link}";

        if ($ctId) {
            $this->notifier->image($phone, route('og.card', $ctId), $caption, ['category' => WhatsAppMessage::CAT_SUPPORT]);
        } else {
            $this->notifier->text($phone, $caption, ['category' => WhatsAppMessage::CAT_SUPPORT]);
        }

        return ['sent' => true, 'name' => $name];
    }

    private function toolEscalate(array $args, string $phone, WhatsAppMessage $inbound): array
    {
        $reason = trim((string) ($args['reason'] ?? 'demande client'));
        $this->escalate($phone, $reason, $inbound->body);
        return ['escalated' => true];
    }

    // ------------------------------------------------------------------
    // Utilitaires
    // ------------------------------------------------------------------

    /** Rapproche un numéro WhatsApp (E.164) d'un compte utilisateur. */
    private function resolveUser(string $phone): ?User
    {
        $d = WhapiService::normalize($phone);
        if ($d === '') return null;

        $candidates = [$d];
        // Gabon : 241XXXXXXXX ⇄ 0XXXXXXXX ⇄ XXXXXXXX
        if (str_starts_with($d, '241') && strlen($d) > 3) {
            $local = substr($d, 3);
            $candidates[] = $local;
            $candidates[] = '0' . $local;
        }

        return User::whereIn('phone', array_unique($candidates))->first();
    }

    private function reply(string $phone, string $text): void
    {
        $this->notifier->text($phone, $text, ['category' => WhatsAppMessage::CAT_SUPPORT]);
    }

    private function escalate(string $phone, string $reason, ?string $lastMessage): void
    {
        $agent = config('services.whapi.agent_number');
        $admin = config('services.whapi.admin_number');
        $summary = "🆘 Escalade support WhatsApp\nDe : {$phone}\nMotif : {$reason}"
            . ($lastMessage ? "\nDernier message : " . mb_substr($lastMessage, 0, 300) : '');

        foreach (array_unique(array_filter([$agent, $admin])) as $target) {
            $this->notifier->text($target, $summary, [
                'category'  => WhatsAppMessage::CAT_OPS,
                // Clé propre à CHAQUE destinataire (sinon l'admin serait dédoublonné
                // avec l'agent) — tout en évitant les doublons vers un même numéro.
                'dedup_key' => 'escalate-' . $target . '-' . md5($phone . $reason . (string) $lastMessage),
            ]);
        }

        Log::info('SupportBot escalade', ['from' => $phone, 'reason' => $reason]);
    }
}
