<?php

namespace App\Support;

use Illuminate\Support\Facades\Hash;
use App\Models\MerchantCard;
use App\Models\MerchantCardPurchase;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reseller;
use App\Models\UserCard;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
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
     * Commission revendeur PAR DÉFAUT sur les cartes locales (décision produit
     * du 10 août 2026 : 4,5 %). Le taux par carte (`vendor_commission_rate`,
     * fixé par l'admin) prime s'il est renseigné (> 0).
     */
    public const DEFAULT_RESELLER_RATE = 4.5;

    /**
     * Commission KardAfrica sur les Cartes Gabon : 15 % du montant encaissé.
     *
     * Elle était structurellement nulle — `admin_commission_rate` valait 0 sur
     * toutes les cartes et rien ne fournissait de valeur de repli, contrairement
     * à la part revendeur.
     *
     * LA PART REVENDEUR SE PRÉLÈVE SUR CES 15 %, PAS EN PLUS.
     * Le commerçant touche 85 % quel que soit le canal de vente — c'est un
     * engagement envers lui, il n'a pas à être moins payé parce qu'un revendeur
     * s'est interposé. KardAfrica garde donc 10,5 % sur une vente au comptoir
     * (15 − 4,5) et 15 % sur une vente en ligne directe.
     */
    public const DEFAULT_ADMIN_RATE = 15.0;

    /** Taux de commission revendeur applicable à une carte locale. */
    /** Part KardAfrica, avec repli sur le taux plateforme. */
    public static function adminRate(MerchantCard $card): float
    {
        $rate = (float) ($card->admin_commission_rate ?? 0);

        return $rate > 0 ? $rate : self::DEFAULT_ADMIN_RATE;
    }

    public static function resellerRate(MerchantCard $card): float
    {
        $rate = (float) ($card->vendor_commission_rate ?? 0);
        return $rate > 0 ? $rate : self::DEFAULT_RESELLER_RATE;
    }

    /**
     * Réserve une carte locale pour une vente REVENDEUR au comptoir.
     *
     * La purchase naît `inactive` + `pending` : le code/PIN existent en base
     * mais sont INERTES (refusés au scan comptoir) tant que le revendeur n'a
     * pas « récupéré » la carte — action atomique qui débite son wallet,
     * prouve la vente à KardAfrica et bascule le statut à `active` (voir
     * Vendor\LocalCardController::claim). Aucune stat n'est incrémentée ici :
     * total_sold/revenue ne bougent qu'à la récupération.
     */
    public static function createReservationForReseller(
        MerchantCard $card,
        float $amount,
        Reseller $reseller,
        ?string $buyerName = null,
        ?string $buyerPhone = null,
    ): MerchantCardPurchase {
        // C2 — montant strictement conforme aux règles de la carte, carte ACTIVE
        // exigée (contrairement au flux Order où le paiement a déjà eu lieu).
        if (!$card->isValidAmount($amount, true)) {
            throw new RuntimeException("Montant invalide pour la carte #{$card->id} : {$amount}");
        }

        // Une carte sans propriétaire ne pourra jamais être débitée au comptoir.
        // Le scope `active()` l'écarte déjà des catalogues ; ce garde-fou couvre
        // les appels directs et les cartes orphelinées entre-temps.
        if ($card->card_owner_id === null) {
            throw new RuntimeException(
                "Carte #{$card->id} sans propriétaire : elle ne pourrait être validée par aucun commerçant."
            );
        }

        return DB::transaction(function () use ($card, $amount, $reseller, $buyerName, $buyerPhone) {
            // Snapshot commissions : la part revendeur suit resellerRate()
            // (4,5 % par défaut), le reste du modèle est identique au flux en ligne.
            $adminRate    = self::adminRate($card);
            $vendorRate   = self::resellerRate($card);
            $adminAmount  = round($amount * $adminRate / 100, 2);
            $vendorAmount = round($amount * $vendorRate / 100, 2);
            // 85 % au commerçant, invariablement : la part revendeur est
            // déduite des 15 % de KardAfrica, pas de la sienne.
            $ownerNet     = round($amount - $adminAmount, 2);

            $purchase = MerchantCardPurchase::create([
                'merchant_card_id'         => $card->id,
                'reseller_id'              => $reseller->id,
                'unique_code'              => self::generateUniqueCode(),
                'pin_code'                 => $pinComptoir = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                'pin_hash'                 => Hash::make($pinComptoir),
                'qr_payload'               => 'pending-' . uniqid('', true),
                'buyer_name'               => $buyerName ?: 'Client comptoir',
                // Jamais le téléphone du revendeur en repli : le lien de
                // révélation du code partirait chez lui au lieu du client.
                'buyer_phone'              => $buyerPhone ?: '',
                'amount'                   => $amount,
                'remaining_balance'        => $amount,
                'currency'                 => 'XAF',
                'admin_commission_amount'  => $adminAmount,
                'vendor_commission_amount' => $vendorAmount,
                'owner_net_amount'         => $ownerNet,
                'payment_method'           => 'reseller_wallet',
                'payment_status'           => MerchantCardPurchase::PAYMENT_PENDING,
                'status'                   => MerchantCardPurchase::STATUS_INACTIVE,
                'delivery_channel'         => ['reseller_counter'],
                'expires_at'               => now()->addMonths((int) ($card->validity_months ?? 12)),
            ]);

            // Finalise le QR signé avec l'ID réel (même pattern que le flux en ligne).
            $purchase->update(['qr_payload' => self::buildQrPayload($purchase->id)]);

            Log::info('MerchantCardCode: réservation revendeur créée (inactive)', [
                'purchase_id' => $purchase->id,
                'card_id'     => $card->id,
                'reseller_id' => $reseller->id,
                'amount'      => $amount,
            ]);

            return $purchase;
        });
    }

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
        $purchase->loadMissing('merchantCard');
        $card = $purchase->merchantCard;

        // Génère un PIN unique à cet achat s'il manque (vieilles purchases)
        if (empty($purchase->pin_code)) {
            $pinRattrapage = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $purchase->update([
                'pin_code' => $pinRattrapage,
                'pin_hash' => Hash::make($pinRattrapage),
            ]);
            $purchase->refresh();
        }

        // UserCard miroir si absent (lookup par order_item_id pour éviter le doublon)
        $hasMirror = UserCard::where('order_item_id', $item->id)
            ->where('user_id', $order->user_id)
            ->exists();

        if (!$hasMirror) {
            UserCard::create([
                'user_id'         => $order->user_id,
                'order_id'        => $order->id,
                'order_item_id'   => $item->id,
                'product_id'      => $item->product_id,
                'checkout_card_id'=> null, // colonne INT afrikard, N/A ici (réf dans metadata)
                'name'            => $card?->name ?? 'Carte locale',
                'brand'           => 'KardAfrica',
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

    /**
     * Prix/montant FAISANT AUTORITÉ pour un product_id marchand (anti-fraude C2).
     * Une carte marchand est une valeur stockée 1:1 : son prix EST son montant.
     * Retourne le montant si le product_id est marchand ET le montant est
     * ACHETABLE pour la carte (denomination listée, ou montant libre dans la
     * plage, carte active) ; null sinon (non-marchand ou montant invalide).
     */
    public static function authoritativeAmount(string $productId): ?int
    {
        if (!preg_match('/^merchant_(\d+)_(\d+)$/', $productId, $m)) {
            return null;
        }
        $card = MerchantCard::find((int) $m[1]);
        if (!$card) return null;

        $amount = (int) $m[2];
        return $card->isValidAmount($amount, true) ? $amount : null;
    }

    public static function createPurchaseForOrderItem(Order $order, OrderItem $item): MerchantCardPurchase
    {
        try {
            return self::createOrGetPurchase($order, $item);
        } catch (QueryException $e) {
            // H16 — course perdante : une requête concurrente a créé la purchase
            // entre notre contrôle et notre INSERT. La contrainte UNIQUE sur
            // order_item_id fait rollback notre transaction (23000) : on relit
            // et on retourne l'enregistrement gagnant au lieu d'échouer.
            $isUniqueViolation = $e instanceof UniqueConstraintViolationException
                || (string) $e->getCode() === '23000';
            if (!$isUniqueViolation) {
                throw $e;
            }

            $existing = MerchantCardPurchase::where('order_item_id', $item->id)->first();
            if ($existing) {
                self::backfillPinAndUserCard($existing, $item, $order);
                Log::info('MerchantCardCode: course détectée (unique order_item_id), purchase existante retournée', [
                    'order_id'      => $order->id,
                    'order_item_id' => $item->id,
                    'purchase_id'   => $existing->id,
                ]);
                return $existing;
            }

            // Violation d'unicité sur autre chose (unique_code, qr_payload…) :
            // pas notre cas, on laisse remonter.
            throw $e;
        }
    }

    /**
     * Cœur de createPurchaseForOrderItem : contrôle d'existence + création,
     * le tout DANS une même transaction (H16). Le contrôle hors transaction
     * permettait à deux requêtes simultanées (self-heal de OrderController::show
     * dans deux onglets) de créer deux purchases pour un seul item payé.
     */
    private static function createOrGetPurchase(Order $order, OrderItem $item): MerchantCardPurchase
    {
        return DB::transaction(function () use ($order, $item) {
            // Idempotence : si la purchase existe déjà, on s'assure qu'elle a un
            // PIN ET une UserCard miroir, puis on retourne. Permet de réparer les
            // achats créés avant l'ajout du PIN/mirror. lockForUpdate : sérialise
            // les appels concurrents sur le même order_item.
            $existing = MerchantCardPurchase::where('order_item_id', $item->id)
                ->lockForUpdate()
                ->first();
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

            // SÉCURITÉ (C2) — le montant (= solde encaissable de la carte) ne doit
            // JAMAIS être hors des règles de la carte. On tolère is_active=false ici
            // (la commande a déjà été payée quand la carte était en vente), mais on
            // refuse tout montant hors denominations / hors plage libre.
            if (!$card->isValidAmount($amount, false)) {
                throw new RuntimeException("Montant marchand invalide pour la carte #{$cardId} : {$amount}");
            }

            // Carte orphelinée entre l'achat et la livraison (la contrainte est
            // `ON DELETE SET NULL`). On ne refuse PAS : la commande est déjà
            // payée, échouer laisserait le client sans code ET sans carte. On
            // alerte pour qu'un administrateur réattribue la carte — sans quoi
            // aucun commerçant ne pourra la valider.
            if ($card->card_owner_id === null) {
                Log::critical('MerchantCardCode: carte sans propriétaire livrée à un client', [
                    'merchant_card_id' => $cardId,
                    'order_id'         => $order->id,
                    'amount'           => $amount,
                ]);
            }

            $order->loadMissing('user');
            $user    = $order->user;
            $billing = (array) $order->billing_details;

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

            // Snapshot commissions au moment de la vente (taux figés sur la purchase)
            $adminRate     = self::adminRate($card);
            $vendorRate    = (float) ($card->vendor_commission_rate ?? 0);
            $adminAmount   = round($amount * $adminRate / 100, 2);
            $vendorAmount  = round($amount * $vendorRate / 100, 2);
            // 85 % au commerçant, invariablement (cf. DEFAULT_ADMIN_RATE).
            $ownerNet      = round($amount - $adminAmount, 2);

            $purchase = MerchantCardPurchase::create([
                'merchant_card_id'         => $card->id,
                'order_id'                 => $order->id,
                'order_item_id'            => $item->id,
                'unique_code'              => $uniqueCode,
                'pin_code'                 => $pinCode,
                'pin_hash'                 => Hash::make($pinCode),
                'qr_payload'               => 'pending-' . uniqid('', true),
                'buyer_name'               => $billing['name']  ?? $user->name  ?? 'Client KardAfrica',
                'buyer_phone'              => $billing['phone'] ?? $user->phone ?? '',
                'buyer_email'              => $billing['email'] ?? $user->email ?? null,
                'amount'                   => $amount,
                'remaining_balance'        => $amount,
                'currency'                 => 'XAF',
                'admin_commission_amount'  => $adminAmount,
                'vendor_commission_amount' => $vendorAmount,
                'owner_net_amount'         => $ownerNet,
                'payment_method'           => $order->payment_method ?? 'ebilling',
                'payment_status'           => MerchantCardPurchase::PAYMENT_PAID,
                'payment_ref'              => $order->external_reference,
                'status'                   => MerchantCardPurchase::STATUS_ACTIVE,
                'delivery_channel'         => ['email', 'orders_page'],
                'delivered_at'             => now(),
                'paid_at'                  => now(),
                'expires_at'               => $expiresAt,
            ]);

            // 2. Finalise le qr_payload signé avec l'ID réel
            $purchase->update([
                'qr_payload' => self::buildQrPayload($purchase->id),
            ]);

            // 3. Mirroir UserCard : la carte apparaît dans /cards à côté des
            //    cartes afrikard, mêmes champs (code + PIN + date d'expiration).
            //    L'utilisateur a UNE liste unifiée de ses cartes-cadeau.
            UserCard::create([
                'user_id'         => $order->user_id,
                'order_id'        => $order->id,
                'order_item_id'   => $item->id,
                'product_id'      => $item->product_id,
                'checkout_card_id'=> null, // colonne INT afrikard, N/A ici (réf dans metadata.merchant_purchase_id)
                'name'            => $card->name,
                'brand'           => 'KardAfrica',
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
