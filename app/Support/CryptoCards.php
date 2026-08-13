<?php

namespace App\Support;

/**
 * CryptoCards
 * ===========
 * Règles de la catégorie Crypto (id 8) : ce qui EST une carte crypto, et
 * laquelle est réellement activable depuis le Gabon ou la France.
 *
 * Isolé du service catalogue pour être testable sans réseau — la classification
 * et la règle de disponibilité sont deux décisions produit, pas de la plomberie.
 */
class CryptoCards
{
    public const CATEGORY_ID = 8;

    /**
     * Mots-clés de marque. Volontairement sans les tickers seuls ('btc', 'eth',
     * 'bnb'…) : 'bnb' matcherait « Air**bnb** ». Les déclinaisons Binance et
     * GatePay sont déjà couvertes par le nom de marque.
     */
    public const BRANDS = [
        'binance', 'crypto', 'bitcoin', 'bitnovo', 'gatepay',
        'coinbase', 'bitrefill', 'blockchain',
    ];

    /**
     * Marques dont les déclinaisons ne diffèrent que par le jeton entre
     * parenthèses (« Binance (BTC) », « Binance (USDT) »…) : elles s'agrègent
     * sous une marque unique dans les lignes et le filtre marque.
     */
    public const AGGREGATED_BRANDS = ['binance', 'gatepay', 'gift me crypto', 'rewarble crypto'];

    /**
     * Seules les éditions Global (WW/GLC) et euro (EU/FR) sont vendables : une
     * carte verrouillée sur un autre marché (GBP, AUD, CAD, TRY, CNY, COP,
     * BRL…) ne pourrait être activée ni depuis le Gabon ni depuis la France.
     */
    public const ALLOWED_COUNTRIES  = ['WW', 'GLC', 'GLOBAL', 'GL', 'EU', 'FR'];
    public const ALLOWED_CURRENCIES = ['USD', 'EUR'];

    /** La marque (ou le nom de produit) relève-t-elle de la catégorie Crypto ? */
    public static function isCrypto(?string $brandName, ?string $productName = null): bool
    {
        $brand   = mb_strtolower(trim((string) $brandName));
        $product = mb_strtolower(trim((string) $productName));

        foreach (self::BRANDS as $kw) {
            if (($brand !== '' && str_contains($brand, $kw))
                || ($product !== '' && str_contains($product, $kw))) {
                return true;
            }
        }
        return false;
    }

    /** Carte crypto activable depuis le Gabon / la France ? */
    public static function usableHere(?string $countryCode, ?string $currency): bool
    {
        return in_array(strtoupper((string) $countryCode), self::ALLOWED_COUNTRIES, true)
            && in_array(strtoupper((string) $currency), self::ALLOWED_CURRENCIES, true);
    }
}
