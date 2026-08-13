<?php

namespace App\Support;

/**
 * ProductPricing
 * ==============
 * Règles d'affichage du prix d'une carte : variante d'entrée, économie réelle
 * par rapport à la valeur faciale, et pourcentage correspondant.
 *
 * Ce que dit la donnée afrikard : chaque produit porte une VALEUR FACIALE
 * (`minFaceValue`, ce que la carte vaut chez le marchand) et un PRIX
 * (`price.min`, ce que le client paie). Sur ~7 300 produits le prix est
 * INFÉRIEUR à la valeur faciale — une carte de 10 € vendue 9,80 €.
 *
 * ⚠️ Ce n'est donc PAS un « ancien prix » : c'est la valeur du bon. On l'affiche
 * comme tel (« vaut 7 450 FCFA, payez 7 100 FCFA ») et jamais comme un tarif
 * antérieur barré — l'affichage d'un prix de référence fictif est trompeur, et
 * encadré par la loi dans plusieurs marchés.
 */
class ProductPricing
{
    /** En dessous, l'écart relève de l'arrondi de conversion, pas d'un avantage. */
    public const MIN_SAVING_PERCENT = 1.0;

    /**
     * Variante d'entrée de gamme d'une carte : la moins chère.
     *
     * @param  array<int, array> $variants  produits/variantes du cardType
     * @return array|null
     */
    public static function entryVariant(array $variants): ?array
    {
        $candidates = array_values(array_filter(
            $variants,
            fn ($v) => is_array($v) && (float) ($v['price']['min'] ?? 0) > 0
        ));
        if ($candidates === []) return null;

        usort($candidates, fn ($a, $b) => self::fcfa($a) <=> self::fcfa($b));

        return $candidates[0];
    }

    /** Prix de vente d'une variante, en FCFA. */
    public static function fcfa(array $variant): int
    {
        return Money::toFcfa(
            (float) ($variant['price']['min'] ?? 0),
            $variant['price']['currencyCode'] ?? 'XAF'
        );
    }

    /** Valeur faciale d'une variante convertie en FCFA (ce que la carte vaut). */
    public static function faceValueFcfa(array $variant): int
    {
        $face = (float) ($variant['minFaceValue'] ?? 0);
        if ($face <= 0) return 0;

        return Money::toFcfa($face, self::faceCurrency($variant));
    }

    /**
     * Devise de la VALEUR FACIALE — celle de la marque, pas celle du prix.
     *
     * Le catalogue afrikard les dissocie : « Google Play Iraq » vaut 2 000 IQD
     * mais se paie 1,51 USD. Confondre les deux produisait des écarts absurdes
     * (« −100 % »). Elles coïncident sur les marchés euro et dollar, qui sont
     * les nôtres, mais la règle doit tenir partout.
     */
    private static function faceCurrency(array $variant): string
    {
        return strtoupper(
            $variant['cardType']['currencyCode']
            ?? $variant['face_currency']
            ?? $variant['price']['currencyCode']
            ?? 'XAF'
        );
    }

    /**
     * Économie en % entre la valeur faciale et le prix payé.
     * null dès qu'elle n'est pas calculable de façon fiable.
     */
    public static function savingPercent(array $variant): ?int
    {
        $face  = (float) ($variant['minFaceValue'] ?? 0);
        $price = (float) ($variant['price']['min'] ?? 0);
        if ($face <= 0 || $price <= 0 || $price >= $face) return null;

        $faceCurrency  = self::faceCurrency($variant);
        $priceCurrency = strtoupper($variant['price']['currencyCode'] ?? 'XAF');

        // Comparer 2 000 IQD à 1,51 USD n'a aucun sens : sans devise commune,
        // on n'annonce rien.
        if ($faceCurrency !== $priceCurrency) return null;

        // Une devise sans taux réel est convertie au taux 1 par défaut : les
        // montants FCFA obtenus ne veulent rien dire.
        if (!Money::hasRate($priceCurrency)) return null;

        $percent = (1 - $price / $face) * 100;

        if ($percent < self::MIN_SAVING_PERCENT) return null;

        // Pas de plafond : mesuré sur le catalogue, les gros pourcentages ne
        // sont PAS des anomalies mais des clés de jeux et logiciels (Steam à
        // 59,99 € vendue 2,49 €), où la valeur faciale est le prix conseillé.
        // Les vraies aberrations sont déjà écartées par les deux gardes
        // ci-dessus (devise commune + taux réel).

        return (int) round($percent);
    }

    /**
     * Tout ce dont une card a besoin pour afficher son prix.
     *
     * @return array{price_fcfa:int, face_fcfa:int, saving_percent:?int, has_saving:bool}
     */
    public static function display(array $variant): array
    {
        $percent = self::savingPercent($variant);
        $face    = self::faceValueFcfa($variant);
        $price   = self::fcfa($variant);

        return [
            'price_fcfa'     => $price,
            'face_fcfa'      => $face,
            'saving_percent' => $percent,
            // On ne barre la valeur faciale que si l'écart est visible EN FCFA :
            // l'arrondi au pas de vente peut aplatir 1 % à zéro franc.
            'has_saving'     => $percent !== null && $face > $price,
        ];
    }

    /**
     * Meilleure économie d'une carte, pour le tri « Meilleures économies ».
     * 0 quand la carte n'en propose aucune.
     *
     * Accepte les DEUX formes de variante qui circulent dans le catalogue :
     * le produit complet (`minFaceValue` + `price.min`) et la variante allégée
     * produite par CatalogGrouping (`face` + `price_min`). Ne gérer que la
     * première renvoyait 0 partout et rendait le tri inopérant.
     */
    public static function bestSavingPercent(array $variants): int
    {
        $best = 0;
        foreach ($variants as $v) {
            if (!is_array($v)) continue;
            $best = max($best, self::savingPercent(self::normalize($v)) ?? 0);
        }
        return $best;
    }

    /** Ramène une variante allégée (CatalogGrouping) à la forme produit. */
    public static function normalize(array $variant): array
    {
        if (isset($variant['price']['min'])) return $variant;

        if (isset($variant['face'], $variant['price_min'])) {
            return [
                'minFaceValue'   => $variant['face'],
                // CatalogGrouping ne porte qu'une devise : elle vaut pour les
                // deux, ce qui est le cas des variantes d'un même cardType.
                'face_currency'  => $variant['currency'] ?? 'XAF',
                'price'          => [
                    'min'          => $variant['price_min'],
                    'currencyCode' => $variant['currency'] ?? 'XAF',
                ],
            ];
        }

        return $variant;
    }
}
