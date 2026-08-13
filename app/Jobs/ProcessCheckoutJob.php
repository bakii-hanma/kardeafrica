<?php

namespace App\Jobs;

use App\Models\MerchantCardPurchase;
use App\Models\Order;
use App\Models\UserCard;
use App\Support\MerchantCardCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessCheckoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives
     */
    public $tries = 3;

    /**
     * Backoff exponentiel : 10s, 60s, 300s
     */
    public $backoff = [10, 60, 300];

    /**
     * Timeout de 60 secondes
     */
    public $timeout = 60;

    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): void
    {
        Log::info('ProcessCheckoutJob: Debut du traitement', ['order_id' => $this->order->id]);

        // Idempotence — garde-fou CRITIQUE. Sans worker/cron, ces jobs sont
        // restés en file des mois ; en réactivant la file ils seraient rejoués.
        // Une commande déjà complétée NE DOIT PAS être re-livrée : cela
        // rappellerait afrikard/Bamboo = double débit + doublons de cartes.
        $this->order->refresh();
        if ($this->order->status === Order::STATUS_COMPLETED) {
            Log::info('ProcessCheckoutJob: commande déjà complétée, aucune action (idempotence)', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        // Garde REMBOURSEMENT/ANNULATION (H2) — une commande remboursée ou annulée
        // ne doit JAMAIS être livrée, même si le paiement fut un jour "completed"
        // (le remboursement peut passer order.status à refunded sans toucher
        // payment_status). Couvre la course remboursement ↔ livraison quand le
        // job est encore en file au moment du remboursement.
        if (in_array($this->order->status, [Order::STATUS_REFUNDED, Order::STATUS_CANCELLED], true)
            || in_array($this->order->payment_status, [Order::PAYMENT_STATUS_REFUNDED, Order::PAYMENT_STATUS_CANCELLED], true)) {
            Log::warning('ProcessCheckoutJob: commande remboursée/annulée, livraison refusée (H2)', [
                'order_id'       => $this->order->id,
                'status'         => $this->order->status,
                'payment_status' => $this->order->payment_status,
            ]);
            return;
        }

        // Garde PAIEMENT — CRITIQUE. Ne JAMAIS livrer une commande dont le
        // paiement n'est pas confirmé : d'anciens jobs en file correspondent à
        // des paiements abandonnés (payment_status != completed). Les livrer
        // donnerait des cartes gratuites (débit Bamboo réel sans encaissement).
        if ($this->order->payment_status !== Order::PAYMENT_STATUS_COMPLETED) {
            Log::warning('ProcessCheckoutJob: paiement non confirmé, livraison refusée', [
                'order_id'       => $this->order->id,
                'payment_status' => $this->order->payment_status,
            ]);
            return;
        }

        // Charger les order items + user pour l'identité buyer (Carte Gabon)
        $this->order->load(['orderItems', 'user']);

        if ($this->order->orderItems->isEmpty()) {
            Log::error('ProcessCheckoutJob: Aucun item dans la commande', ['order_id' => $this->order->id]);
            $this->order->update(['notes' => ($this->order->notes ?? '') . ' | Erreur: Aucun item dans la commande']);
            return;
        }

        // ============================================================
        // Split items : marchand (Carte Gabon, local) vs afrikard (API)
        // Les items marchand ont un product_id préfixé "merchant_<card_id>_<amount>".
        // ============================================================
        $merchantItems = $this->order->orderItems->filter(
            fn ($i) => str_starts_with((string) $i->product_id, 'merchant_')
        );
        // Daywatch est un produit LOCAL : son API (api.daywatch.online) publie
        // un catalogue mais n'expose AUCUN endpoint d'émission de code. Sans ce
        // filtre, `daywatch_6` partait dans le lot afrikard où
        // `(int) 'daywatch_6'` vaut 0 : afrikard reçoit ProductId 0, la
        // livraison échoue, et le client a payé pour rien. La commande reste
        // donc « en attente de livraison » côté admin, à traiter à la main.
        $daywatchItems = $this->order->orderItems->filter(
            fn ($i) => str_starts_with((string) $i->product_id, 'daywatch_')
        );
        $afrikardItems = $this->order->orderItems->reject(
            fn ($i) => str_starts_with((string) $i->product_id, 'merchant_')
                || str_starts_with((string) $i->product_id, 'daywatch_')
        );

        if ($daywatchItems->isNotEmpty()) {
            Log::warning('ProcessCheckoutJob: items Daywatch à livrer manuellement', [
                'order_id'   => $this->order->id,
                'items'      => $daywatchItems->pluck('product_id')->all(),
            ]);

            $this->order->update([
                'status' => Order::STATUS_PROCESSING,
                'notes'  => trim(($this->order->notes ?? '')
                    . ' | Daywatch : ' . $daywatchItems->count()
                    . ' abonnement(s) à activer manuellement (aucune émission automatique).'),
            ]);
        }

        // ============================================================
        // 1) Items marchand : génération LOCALE (pas d'appel afrikard)
        // ============================================================
        foreach ($merchantItems as $item) {
            try {
                MerchantCardCode::createPurchaseForOrderItem($this->order, $item);
            } catch (\Throwable $e) {
                Log::error('ProcessCheckoutJob: échec création MerchantCardPurchase', [
                    'order_id'      => $this->order->id,
                    'order_item_id' => $item->id,
                    'product_id'    => $item->product_id,
                    'error'         => $e->getMessage(),
                ]);
                // On ne throw PAS : un échec marchand ne doit pas retry toute la commande
                // (les afrikard pourraient déjà avoir réussi). On log et on continue.
            }
        }

        // ============================================================
        // 2) Items afrikard : appel API (= comportement historique)
        // ============================================================
        if ($afrikardItems->isEmpty()) {
            // Un abonnement Daywatch en attente d'activation manuelle interdit
            // de clore la commande : elle doit rester dans « livraisons en
            // attente » tant que l'admin ne l'a pas traitée.
            if ($daywatchItems->isNotEmpty()) {
                return;
            }

            // Commande 100 % Carte Gabon → on complète sans appel afrikard
            $this->order->update([
                'status'       => Order::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            Log::info('ProcessCheckoutJob: Commande 100 % marchand complétée localement', [
                'order_id'        => $this->order->id,
                'merchant_count'  => $merchantItems->count(),
                'purchases_count' => MerchantCardPurchase::where('order_id', $this->order->id)->count(),
            ]);
            return;
        }

        // Construire le payload afrikard
        $service = app(\App\Services\ProductApiService::class);
        $checkoutPayload = $afrikardItems->map(function ($item) use ($service) {
            $native = $item->native_value && (float) $item->native_value > 0
                ? (int) round((float) $item->native_value)
                : null;

            if ($native === null) {
                $resolved = $service->resolveNativeValue($item->product_id);
                $native = $resolved['value'] ?? null;
            }

            // TODO(afrikard) : à confirmer avec afrikard si /orders/checkout
            // accepte un champ de référence idempotente (ex. ExternalReference =
            // order_number) — aucun appel existant dans le code n'en envoie et
            // la doc n'en mentionne pas. Tant que ce n'est pas confirmé, la
            // protection anti double-livraison repose sur delivery_requested_at
            // (garde H4 ci-dessous), pas sur le payload.
            return [
                'ProductId' => (int) $item->product_id,
                'Quantity'  => (int) $item->quantity,
                'Value'     => $native ?? (int) round($item->unit_price),
            ];
        })->values()->toArray();

        // ============================================================
        // Garde H4 — double livraison sur retry. On pose le marqueur
        // delivery_requested_at sous verrou AVANT l'appel fournisseur :
        // si un passage précédent a déjà déclenché l'appel afrikard mais a
        // crashé AVANT de compléter la commande (timeout, kill du worker),
        // le retry retrouverait un paiement confirmé et rappellerait afrikard
        // → carte facturée DEUX fois côté fournisseur. Dans ce cas on ne
        // rappelle JAMAIS : réconciliation manuelle obligatoire.
        // ============================================================
        $deliveryState = DB::transaction(function () {
            $fresh = Order::whereKey($this->order->id)->lockForUpdate()->first();

            // Un process concurrent (finalize, admin retry) a pu compléter la
            // commande entre notre refresh() du début et ce verrou.
            if ($fresh->status === Order::STATUS_COMPLETED) {
                return 'completed';
            }

            if ($fresh->delivery_requested_at !== null) {
                return 'already_requested';
            }

            $fresh->delivery_requested_at = now();
            $fresh->save();

            return 'proceed';
        });

        if ($deliveryState === 'completed') {
            Log::info('ProcessCheckoutJob: commande complétée par un process concurrent, aucune action (idempotence)', [
                'order_id' => $this->order->id,
            ]);
            return;
        }

        if ($deliveryState === 'already_requested') {
            Log::critical('ProcessCheckoutJob: livraison afrikard déjà demandée mais commande non complétée — réconciliation manuelle requise (H4)', [
                'order_id'     => $this->order->id,
                'order_number' => $this->order->order_number,
                'attempt'      => $this->attempts(),
            ]);
            $this->order->update([
                'notes' => ($this->order->notes ?? '')
                    . ' | LIVRAISON AFRIKARD DEJA DEMANDEE — cartes peut-être débitées côté fournisseur sans avoir été enregistrées.'
                    . ' Vérifier avec afrikard avant tout retry (' . now()->toDateTimeString() . ')',
            ]);
            // return (et pas throw) : on ne veut SURTOUT PAS de retry automatique
            // qui rappellerait afrikard. La commande reste "processing" avec la
            // note explicite pour l'admin.
            return;
        }

        Log::info('ProcessCheckoutJob: Appel API checkout afrikard', [
            'order_id' => $this->order->id,
            'payload'  => $checkoutPayload,
            'attempt'  => $this->attempts(),
        ]);

        $response = Http::timeout(30)
            ->post(config('services.product_api.base_url') . '/orders/checkout', $checkoutPayload);

        // SÉCURITÉ (C7) : ne JAMAIS logger le corps de la réponse fournisseur —
        // il contient les codes et PIN des cartes en clair. On ne trace que le
        // statut HTTP. Le succès détaillé est loggé plus bas sans données sensibles.
        Log::info('ProcessCheckoutJob: Reponse API afrikard', [
            'order_id' => $this->order->id,
            'status'   => $response->status(),
        ]);

        if ($response->status() === 202 || $response->successful()) {
            $checkoutData = $response->json();

            $this->saveCards($checkoutData);

            $this->order->update([
                // Commande mixte : les cartes afrikard sont livrées, mais un
                // abonnement Daywatch reste à activer à la main. La clore
                // maintenant la ferait disparaître de « livraisons en attente »
                // avec un client qui n'a reçu qu'une partie de son achat.
                'status'       => $daywatchItems->isEmpty()
                    ? Order::STATUS_COMPLETED
                    : Order::STATUS_PROCESSING,
                'completed_at' => $daywatchItems->isEmpty() ? now() : null,
                'billing_details' => array_merge((array) $this->order->billing_details, [
                    'checkout_order_id'   => $checkoutData['orderId'] ?? null,
                    'checkout_request_id' => $checkoutData['requestId'] ?? null,
                    'checkout_status'     => $checkoutData['status'] ?? null,
                ]),
            ]);

            Log::info('ProcessCheckoutJob: Commande complétée avec succès', [
                'order_id'        => $this->order->id,
                'cards_count'     => UserCard::where('order_id', $this->order->id)->count(),
                'purchases_count' => MerchantCardPurchase::where('order_id', $this->order->id)->count(),
            ]);
        } else {
            // SÉCURITÉ (C7) : ne JAMAIS logger le body brut — même en erreur,
            // la réponse fournisseur peut contenir des codes/PIN de cartes.
            // On ne trace que le statut + le message d'erreur structuré (tronqué).
            $errJson = $response->json();
            $errMessage = is_array($errJson)
                ? ($errJson['error'] ?? $errJson['message'] ?? $errJson['title'] ?? 'réponse sans champ error/message')
                : 'réponse non-JSON';

            Log::error('ProcessCheckoutJob: API checkout afrikard a échoué', [
                'order_id' => $this->order->id,
                'status'   => $response->status(),
                'error'    => Str::limit(is_string($errMessage) ? $errMessage : json_encode($errMessage), 200),
                'attempt'  => $this->attempts(),
            ]);

            // Garde H4 (suite) : une erreur 4xx = requête REJETÉE par afrikard,
            // rien n'a été débité → on relâche le marqueur pour autoriser le
            // retry automatique. 5xx/timeout = ambigu (la livraison a pu partir
            // côté fournisseur) → on GARDE delivery_requested_at : le retry
            // s'arrêtera sur la garde et exigera une réconciliation manuelle.
            if ($response->status() >= 400 && $response->status() < 500) {
                $this->order->update(['delivery_requested_at' => null]);
            }

            throw new \Exception(
                'Checkout API échoué avec statut ' . $response->status() .
                ' pour la commande #' . $this->order->order_number
            );
        }
    }


    /**
     * Sauvegarder les cartes du checkout dans user_cards
     */
    private function saveCards(array $checkoutData): void
    {
        $items = $checkoutData['items'] ?? [];

        foreach ($items as $item) {
            $cards = $item['cards'] ?? [];

            // Trouver l'order_item correspondant. Montants virtuels
            // ("1571149v25") : afrikard renvoie l'id RÉEL (1571149) → fallback
            // par id réel + valeur native quand elle est connue.
            $productId = $item['productId'] ?? null;
            $faceValue = $item['productFaceValue'] ?? null;
            $orderItem = $this->order->orderItems
                ->where('product_id', $productId)
                ->first()
                ?? $this->order->orderItems->first(fn ($oi) => (int) $oi->product_id === (int) $productId
                    && ($faceValue === null || $oi->native_value === null
                        || (float) $oi->native_value == (float) $faceValue));

            foreach ($cards as $card) {
                $attributes = [
                    'user_id' => $this->order->user_id,
                    'order_id' => $this->order->id,
                    'order_item_id' => $orderItem?->id,
                    'product_id' => $item['productId'] ?? null,
                    'name' => $orderItem?->name ?? 'Carte cadeau',
                    'brand' => $orderItem?->name ?? 'Unknown',
                    'serial_number' => $card['serialNumber'] ?? null,
                    'card_code' => $card['cardCode'] ?? null,
                    'pin' => $card['pin'] ?? null,
                    'expiration_date' => isset($card['expirationDate'])
                        ? \Carbon\Carbon::parse($card['expirationDate'])->toDateTimeString()
                        : null,
                    'status' => $this->mapCardStatus($card['status'] ?? null),
                    'face_value' => $item['productFaceValue'] ?? $orderItem?->unit_price ?? 0,
                    'currency' => $checkoutData['currency'] ?? 'XAF',
                    'image_url' => $orderItem?->image_url ?? null,
                    'metadata' => [
                        'checkout_order_id' => $checkoutData['orderId'] ?? null,
                        'card_status' => $card['status'] ?? null,
                    ],
                ];

                // Idempotence H4 : checkout_card_id est l'identifiant unique de
                // la carte côté afrikard (contrainte UNIQUE en BDD). Un rejeu du
                // job après crash post-appel ne doit pas dupliquer la carte →
                // firstOrCreate retrouve la ligne existante au lieu d'en recréer.
                $checkoutCardId = $card['id'] ?? null;
                if ($checkoutCardId !== null) {
                    UserCard::firstOrCreate(
                        ['checkout_card_id' => $checkoutCardId],
                        $attributes
                    );
                } else {
                    // Pas d'identifiant fournisseur : comportement historique
                    // (aucune clé fiable pour dédupliquer).
                    UserCard::create($attributes + ['checkout_card_id' => null]);
                }
            }
        }
    }

    /**
     * Mapping du statut renvoye par l'API externe ("Sold", "Active", "Used"...).
     */
    private function mapCardStatus(?string $apiStatus): string
    {
        $normalized = strtolower($apiStatus ?? '');

        return match ($normalized) {
            'used', 'redeemed', 'consumed' => UserCard::STATUS_USED,
            'expired'                       => UserCard::STATUS_EXPIRED,
            default                         => UserCard::STATUS_ACTIVE,
        };
    }

    /**
     * Traitement en cas d'echec definitif (apres toutes les tentatives)
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical('ProcessCheckoutJob: ECHEC DEFINITIF', [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Mettre a jour les notes de la commande
        $this->order->update([
            'notes' => ($this->order->notes ?? '') . ' | ECHEC CHECKOUT: ' . $exception->getMessage() . ' (' . now()->toDateTimeString() . ')',
        ]);
    }
}
