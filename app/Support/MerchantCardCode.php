<?php

namespace App\Support;

use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\UserCard;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * MerchantCardCode
 * ===
 * Génère :
 *  - un code 8 chiffres unique (présenté au comptoir pour saisie manuelle)
 *  - un QR payload signé (encodé via Laravel Crypt = AES-256-CBC + HMAC)
 *
 * Le QR payload contient {purchase_id, merchant_id, iat} — JAMAIS le solde,
 * cf spec §SÉCURITÉ #1. Au scan, on déchiffre, on extrait purchase_id, on va
 * chercher le purchase à jour en DB.
 *
 * On utilise Crypt (déjà disponible dans Laravel) au lieu d'introduire une
 * dépendance JWT — résultat équivalent en sécurité car la clé APP_KEY est secrète.
 */
class MerchantCardCode
{
    /**
     * Génère un code unique 8 chiffres en évitant les collisions DB.
     * Pool = 10^8 = 100M combinaisons, donc collisions très rares.
     */
    public static function generateUniqueCode(int $maxAttempts = 8): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            // mt_rand est cryptographiquement faible mais OK pour un code de
            // vérification 8 chiffres (la sécurité repose sur la combinaison
            // code + status="active" + payment_status="paid" en DB)
            $code = (string) random_int(10000000, 99999999);
            if (!MerchantCardPurchase::where('unique_code', $code)->exists()) {
                return $code;
            }
        }
        throw new RuntimeException(
            "Impossible de générer un code unique après {$maxAttempts} tentatives. " .
            "C'est statistiquement impossible — vérifier l'index unique sur merchant_card_purchases.unique_code."
        );
    }

    /**
     * Chiffre la payload pour le QR. Output = string base64 URL-safe.
     *
     * @param int      $purchaseId  ID du MerchantCardPurchase
     * @param int|null $merchantId  ID du Reseller (null = carte catalogue admin)
     * @return string  payload chiffré à embarquer dans le QR
     */
    public static function buildQrPayload(int $purchaseId, ?int $merchantId = null): string
    {
        return Crypt::encryptString(json_encode([
            'pid' => $purchaseId,
            'mid' => $merchantId ?? 0, // 0 = carte catalogue admin (pas de marchand)
            'iat' => now()->timestamp,
            'v'   => 1, // version du format payload (pour migrations futures)
        ]));
    }

    /**
     * Déchiffre une payload QR scannée. Retourne null si invalide/corrompue.
     *
     * @return array{pid: int, mid: int, iat: int, v: int}|null
     */
    public static function decodeQrPayload(string $encrypted): ?array
    {
        try {
            $json = Crypt::decryptString($encrypted);
            $data = json_decode($json, true);
            if (!is_array($data) || !isset($data['pid'], $data['mid'])) {
                return null;
            }
            return $data;
        } catch (\Throwable $e) {
            // Crypt lève DecryptException si signature HMAC invalide
            return null;
        }
    }

    /**
     * Détermine si un OrderItem est une carte marchand (Carte Gabon)
     * d'après le préfixe du product_id (= "merchant_<card_id>_<amount>").
     */
    public static function isMerchantOrderItem(OrderItem $item): bool
    {
        return str_starts_with((string) $item->product_id, 'merchant_');
    }

    /**
     * Pour les achats marchand créés AVANT l'ajout du PIN / du miroir UserCard :
     *  - génère un pin_code s'il manque
     *  - crée la UserCard miroir si elle n'existe pas
     *
     * Idempotent (peut être appelé plusieurs fois sans dupliquer).
     */
    public static function backfillPinAndUserCard(
        MerchantCardPurchase $purchase,
        OrderItem $item,
        Order $order
    ): void {
        $purchase->loadMissing(['merchantCard.reseller']);
        $card = $purchase->merchantCard;

        // Génère un PIN unique à cet achat s'il manque (vieilles purchases)
        if (empty($purchase->pin_code)) {
            $purchase->update([
                'pin_code' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            ]);
            $purchase->refresh();
        }

        // UserCard miroir si absent (lookup par order_item_id pour éviter le doublon)
        $hasMirror = UserCard::where('order_item_id', $item->id)
            ->where('user_id', $order->user_id)
            ->exists();

        if (!$hasMirror) {
            $merchantName = $card?->reseller?->business_name
                ?? $card?->reseller?->name
                ?? 'KardAfrica';

            UserCard::create([
                'user_id'         => $order->user_id,
                'order_id'        => $order->id,
                'order_item_id'   => $item->id,
                'product_id'      => $item->product_id,
                'checkout_card_id'=> 'mp_' . $purchase->id,
                'name'            => $card?->name ?? 'Carte locale',
                'brand'           => $merchantName,
                'serial_number'   => 'MGAB-' . str_pad((string) $purchase->id, 8, '0', STR_PAD_LEFT),
                'card_code'       => $purchase->unique_code,
                'pin'             => $purchase->pin_code,
                'expiration_date' => $purchase->expires_at?->toDateString(),
                'status'          => UserCard::STATUS_ACTIVE,
                'face_value'      => $purchase->amount,
                'currency'        => 'XAF',
                'image_url'       => $card && $card->visual_url ? asset($card->visual_url) : null,
                'metadata'        => [
                    'source'              => 'merchant',
                    'merchant_purchase_id'=> $purchase->id,
                    'merchant_card_id'    => $card?->id,
                    'reseller_id'         => $purchase->reseller_id,
                    'merchant_name'       => $merchantName,
                    'merchant_city'       => $card?->reseller?->city ?? null,
                ],
            ]);

            Log::info('MerchantCardCode: UserCard miroir backfillée', [
                'purchase_id' => $purchase->id,
                'order_id'    => $order->id,
            ]);
        }
    }

    /**
     * Crée localement une MerchantCardPurchase pour un OrderItem marchand.
     * Équivalent du payload `cards[]` renvoyé par afrikard pour une carte de marque,
     * mais 100% local (pas d'appel API).
     *
     * Idempotent : si une purchase existe déjà pour cet order_item_id, on la
     * retourne sans rien créer. Indispensable pour gérer les retry de paiement
     * et les replay du callback.
     *
     * Lève une RuntimeException si :
     *   - le product_id n'est pas au format "merchant_<id>" ou "merchant_<id>_<amount>"
     *   - la MerchantCard n'existe pas (supprimée ?)
     *
     * @return MerchantCardPurchase la purchase créée ou retrouvée
     */
    public static function createPurchaseForOrderItem(Order $order, OrderItem $item): MerchantCardPurchase
    {
        // Idempotence : si la purchase existe déjà, on s'assure qu'elle a un
        // PIN ET une UserCard miroir, puis on retourne. Permet de réparer les
        // achats créés avant l'ajout du PIN/mirror.
        $existing = MerchantCardPurchase::where('order_item_id', $item->id)->first();
        if ($existing) {
            self::backfillPinAndUserCard($existing, $item, $order);
            Log::info('MerchantCardCode: purchase déjà existante, backfill check', [
                'order_id'       => $order->id,
                'order_item_id'  => $item->id,
                'purchase_id'    => $existing->id,
            ]);
            return $existing;
        }

        if (!preg_match('/^merchant_(\d+)(?:_(\d+))?$/', (string) $item->product_id, $m)) {
            throw new RuntimeException("product_id marchand invalide : {$item->product_id}");
        }

        $cardId = (int) $m[1];
        $amount = isset($m[2]) ? (float) $m[2] : (float) $item->unit_price;

        $card = MerchantCard::find($cardId);
        if (!$card) {
            throw new RuntimeException("MerchantCard #{$cardId} introuvable (peut-être supprimée)");
        }

        $order->loadMissing('user');
        $user    = $order->user;
        $billing = (array) $order->billing_details;

        return DB::transaction(function () use ($card, $amount, $item, $order, $user, $billing) {
            // 1. Crée la purchase avec un qr_payload PROVISOIRE — qr_payload est
            //    NOT NULL/UNIQUE en BDD, et buildQrPayload() a besoin de l'ID
            //    réel (qu'on n'a pas encore). On finalise juste après.
            //
            // Le code + PIN + expiration sont générés À LA LIVRAISON, UNIQUES par
            // achat : plusieurs clients peuvent acheter la même carte template,
            // chacun reçoit son propre code stocké dans merchant_card_purchases.
            $uniqueCode = self::generateUniqueCode();
            $pinCode    = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $expiresAt  = now()->addMonths((int) ($card->validity_months ?? 12));

            $purchase = MerchantCardPurchase::create([
                'merchant_card_id'  => $card->id,
                'reseller_id'       => $card->reseller_id,
                'order_id'          => $order->id,
                'order_item_id'     => $item->id,
                'unique_code'       => $uniqueCode,
                'pin_code'          => $pinCode,
                'qr_payload'        => 'pending-' . uniqid('', true),
                'buyer_name'        => $billing['name']  ?? $user->name  ?? 'Client KardAfrica',
                'buyer_phone'       => $billing['phone'] ?? $user->phone ?? '',
                'buyer_email'       => $billing['email'] ?? $user->email ?? null,
                'amount'            => $amount,
                'remaining_balance' => $amount,
                'currency'          => 'XAF',
                'payment_method'    => $order->payment_method ?? 'ebilling',
                'payment_status'    => MerchantCardPurchase::PAYMENT_PAID,
                'payment_ref'       => $order->external_reference,
                'status'            => MerchantCardPurchase::STATUS_ACTIVE,
                'delivery_channel'  => ['email', 'orders_page'],
                'delivered_at'      => now(),
                'paid_at'           => now(),
                'expires_at'        => $expiresAt,
            ]);

            // 2. Finalise le qr_payload signé avec l'ID réel
            $purchase->update([
                'qr_payload' => self::buildQrPayload($purchase->id, $card->reseller_id),
            ]);

            // 3. Mirroir UserCard : la carte apparaît dans /cards à côté des
            //    cartes afrikard, mêmes champs (code + PIN + date d'expiration).
            //    L'utilisateur a UNE liste unifiée de ses cartes-cadeau.
            $merchantName = $card->reseller?->business_name ?? $card->reseller?->name ?? 'KardAfrica';
            UserCard::create([
                'user_id'         => $order->user_id,
                'order_id'        => $order->id,
                'order_item_id'   => $item->id,
                'product_id'      => $item->product_id,
                'checkout_card_id'=> 'mp_' . $purchase->id, // ref interne
                'name'            => $card->name,
                'brand'           => $merchantName,
                'serial_number'   => 'MGAB-' . str_pad((string) $purchase->id, 8, '0', STR_PAD_LEFT),
                'card_code'       => $uniqueCode,
                'pin'             => $pinCode,
                'expiration_date' => $expiresAt->toDateString(),
                'status'          => UserCard::STATUS_ACTIVE,
                'face_value'      => $amount,
                'currency'        => 'XAF',
                'image_url'       => $card->visual_url ? asset($card->visual_url) : null,
                'metadata'        => [
                    'source'              => 'merchant',         // distingue de 'afrikard'
                    'merchant_purchase_id'=> $purchase->id,      // lien vers la purchase
                    'merchant_card_id'    => $card->id,
                    'reseller_id'         => $card->reseller_id,
                    'merchant_name'       => $merchantName,
                    'merchant_city'       => $card->reseller?->city ?? null,
                ],
            ]);

            // 4. Stats marchand
            $card->increment('total_sold');
            $card->increment('total_revenue', $amount);

            Log::info('MerchantCardCode: purchase + UserCard mirror créés', [
                'order_id'    => $order->id,
                'purchase_id' => $purchase->id,
                'card_id'     => $card->id,
                'amount'      => $amount,
                'unique_code' => $purchase->unique_code,
                'has_pin'     => !empty($pinCode),
            ]);

            return $purchase;
        });
    }
}
