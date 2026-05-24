<?php

namespace App\Support;

use App\Models\Reseller;
use Illuminate\Support\Str;

/**
 * MerchantSlug
 * ===
 * Génère un slug unique URL-safe pour /gabon/marchand/{slug}.
 * Évite les collisions en ajoutant un suffixe court si nécessaire.
 *
 * Exemples :
 *   "Hôtel Le Méridien"      → "hotel-le-meridien"
 *   "Hôtel Le Méridien" (×2) → "hotel-le-meridien-x4k2"
 */
class MerchantSlug
{
    public static function generate(string $businessName, ?int $excludeResellerId = null): string
    {
        $base = Str::slug($businessName);
        if ($base === '') {
            $base = 'marchand';
        }
        $base = Str::limit($base, 60, '');

        // Si libre, on prend tel quel
        if (!self::exists($base, $excludeResellerId)) {
            return $base;
        }

        // Sinon, suffixe court random (évite l'incrémentation séquentielle qui
        // expose le nombre total de marchands d'un nom donné)
        for ($i = 0; $i < 5; $i++) {
            $candidate = $base . '-' . Str::lower(Str::random(4));
            if (!self::exists($candidate, $excludeResellerId)) {
                return $candidate;
            }
        }
        // Fallback ultra rare : timestamp
        return $base . '-' . now()->timestamp;
    }

    private static function exists(string $slug, ?int $excludeResellerId): bool
    {
        $q = Reseller::where('slug', $slug);
        if ($excludeResellerId !== null) {
            $q->where('id', '!=', $excludeResellerId);
        }
        return $q->exists();
    }
}
