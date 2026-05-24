<?php

namespace App\Support;

use App\Models\MerchantCardPurchase;
use Illuminate\Support\Facades\Crypt;
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
     * @param int $purchaseId  ID du MerchantCardPurchase
     * @param int $merchantId  ID du Reseller (marchand) — défense en profondeur
     * @return string  payload chiffré à embarquer dans le QR
     */
    public static function buildQrPayload(int $purchaseId, int $merchantId): string
    {
        return Crypt::encryptString(json_encode([
            'pid' => $purchaseId,
            'mid' => $merchantId,
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
}
