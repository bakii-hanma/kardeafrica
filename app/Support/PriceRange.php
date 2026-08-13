<?php

namespace App\Support;

/**
 * PriceRange — correspondance d'un prix aux plages du filtre boutique.
 *
 * ⚠️ Les plages sont exprimées en FCFA : le prix passé DOIT être le prix
 * converti (Money::toFcfa), jamais le prix natif EUR/USD. Le bug historique
 * comparait « 1000_5000 » à 9.42 (EUR) → 0 résultat dès qu'une région
 * européenne était filtrée, et « under_1000 » matchait tout le catalogue.
 */
class PriceRange
{
    /** @param array<int,string> $ranges ex. ['1000_5000', 'over_20000'] */
    public static function matches(float $priceFcfa, array $ranges): bool
    {
        foreach ($ranges as $range) {
            $ok = match ($range) {
                'under_1000' => $priceFcfa < 1000,
                '1000_5000'  => $priceFcfa >= 1000 && $priceFcfa <= 5000,
                '5000_20000' => $priceFcfa > 5000 && $priceFcfa <= 20000,
                'over_20000' => $priceFcfa > 20000,
                default      => false,
            };
            if ($ok) {
                return true;
            }
        }
        return false;
    }

    /**
     * P1 §5 — slider min/max : le prix de vente FCFA est-il dans [min, max] ?
     * null = borne non renseignée (pas de contrainte de ce côté).
     */
    public static function withinBounds(float $priceFcfa, ?int $min, ?int $max): bool
    {
        if ($min !== null && $priceFcfa < $min) return false;
        if ($max !== null && $priceFcfa > $max) return false;
        return true;
    }
}
