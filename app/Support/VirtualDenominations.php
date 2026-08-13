<?php

namespace App\Support;

/**
 * VirtualDenominations
 * ====================
 * Montants « virtuels » pour les produits afrikard à PLAGE LIBRE.
 *
 * Certaines marques (Apple EU notamment) ne sont pas exposées par afrikard en
 * N dénominations fixes mais en UN produit couvrant une plage de valeurs
 * (ex. « Apple FR » : minFaceValue 2 € → maxFaceValue 150 €). Sans traitement,
 * la boutique n'affiche qu'une carte « 2 € » — invendable.
 *
 * Ce support déplie un tel produit en une échelle de dénominations curées
 * (5, 10, 15, 25 €…) qui traversent le pipeline existant comme des produits
 * fixes ordinaires : cartes, pills de variantes, fiche, prix C1, panier.
 *
 * Identité d'un montant virtuel : id = "{idRéel}v{montant}" (ex. "1571149v25").
 *  - `(int) "1571149v25"` = 1571149 → le payload afrikard (ProductId) retombe
 *    NATURELLEMENT sur l'id réel dans ProcessCheckoutJob, avec Value = 25
 *    (la valeur native est posée par resolveNativeValue à la commande).
 *  - Le déplié est idempotent : un produit virtuel (min == max) n'est jamais
 *    re-déplié.
 *
 * Périmètre volontairement restreint (whitelist par préfixe de marque) : le
 * catalogue compte ~1 170 produits à plage libre, presque tous des enseignes
 * retail nord-américaines sans intérêt. Étendre = ajouter un préfixe.
 */
class VirtualDenominations
{
    /**
     * Échelle par défaut (valeurs natives : EUR, USD, GBP…). 20 et 30 inclus :
     * sans eux, Rewarble ChatGPT (plage 30–1000 $) perdrait son entrée à 30 $.
     */
    public const LADDER_DEFAULT = [5, 10, 15, 20, 25, 30, 50, 75, 100, 150, 200, 250, 500];

    /**
     * Échelle crypto : garde l'entrée à 1–2 $ (~800 FCFA) — le micro-rechargement
     * est un vrai cas d'usage au Gabon, et Binance/GatePay démarrent à 1 $.
     */
    public const LADDER_CRYPTO = [1, 2, 5, 10, 15, 25, 50, 100, 250, 500];

    /** Union des échelles — référentiel de validation des ids virtuels (parse). */
    public const LADDER = [1, 2, 5, 10, 15, 20, 25, 30, 50, 75, 100, 150, 200, 250, 500];

    /**
     * Marques dépliées — match par PRÉFIXE du nom de cardType (minuscule).
     * « apple  » (avec espace) matche « Apple France » mais pas « Applebees ».
     * Les cartes CRYPTO (CryptoCards::isCrypto) sont dépliées d'office : la
     * quasi-totalité (Binance, GatePay, Gift Me Crypto, Rewarble Crypto…) est
     * à plage libre.
     */
    /**
     * @deprecated depuis le 12 août — `applies()` accepte toute carte à plage.
     * Conservé pour mémoire des marques historiquement dépliées, et parce que
     * `ladderFor()` peut avoir à distinguer des familles d'échelles à l'avenir.
     */
    private const BRAND_PREFIXES = [
        'apple ',
        'rewarble ',      // ChatGPT, PayPal, Visa, Mastercard, TikTok…
        'amazon ',        // Amazon France 5–5000 €
        'google play ',   // variantes EU à plage (ES/BE/NL 1–500 €)
        'netflix ',       // Netflix UK 50–200 £
        'uber ',
    ];

    /** Séparateur id réel / montant dans l'id virtuel. */
    private const SEP = 'v';

    // ------------------------------------------------------------------
    // Identité
    // ------------------------------------------------------------------

    /** "1571149v25" → ['real' => 1571149, 'face' => 25]. null si pas virtuel. */
    public static function parse(int|string $productId): ?array
    {
        if (!preg_match('/^(\d+)' . self::SEP . '(\d+)$/', (string) $productId, $m)) {
            return null;
        }
        $face = (int) $m[2];
        // Seuls les montants de l'échelle sont des ids légitimes (un id forgé
        // hors échelle est rejeté → fail-closed côté prix C1).
        if (!in_array($face, self::LADDER, true)) {
            return null;
        }
        return ['real' => (int) $m[1], 'face' => $face];
    }

    public static function id(int $realId, int $face): string
    {
        return $realId . self::SEP . $face;
    }

    // ------------------------------------------------------------------
    // Dépliage
    // ------------------------------------------------------------------

    /** Le produit (déjà processé, avec cardType) doit-il être déplié ? */
    public static function applies(array $product): bool
    {
        $id = $product['id'] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return false; // déjà virtuel, daywatch_*, merchant_*…
        }

        $min = $product['minFaceValue'] ?? null;
        $max = $product['maxFaceValue'] ?? null;
        if (!is_numeric($min) || !is_numeric($max) || (float) $max <= (float) $min) {
            return false; // dénomination fixe
        }

        // GÉNÉRALISÉ le 12 août (décision client) : toute carte à plage est
        // dépliée, plus seulement six marques.
        //
        // L'allowlist `BRAND_PREFIXES` laissait 1 032 cartes à plage affichées
        // avec un seul prix et aucun choix de montant — Roblox FR 10–200 €,
        // Airbnb 50–1000 €, Epic Games 10–150 €, Twitch 15–150 €, Razer Gold
        // USD 10–500. Le client voyait « à partir de X » sans pouvoir acheter
        // autre chose que X.
        //
        // Le garde-fou n'est plus la marque mais la PLAGE elle-même : `expand()`
        // ne rend des variantes que si au moins deux barreaux de l'échelle
        // tombent dedans, et `isPurchasable()` refuse tout montant hors échelle.
        // Un id forgé reste donc inachetable.
        return true;
    }

    /** Échelle applicable à un produit (crypto = échelle basse). */
    private static function ladderFor(array $product): array
    {
        $name = $product['cardType']['name'] ?? $product['name'] ?? '';
        return CryptoCards::isCrypto($name) ? self::LADDER_CRYPTO : self::LADDER_DEFAULT;
    }

    /**
     * Le montant est-il RÉELLEMENT achetable pour ce produit à plage ? Doit être
     * dans la plage fournisseur ET sur l'échelle de CE produit — un id forgé sur
     * l'échelle d'une autre famille (ex. 75 sur une carte crypto) est rejeté,
     * pour que seul ce qui est affiché soit achetable.
     */
    public static function isPurchasable(array $base, int $face): bool
    {
        $min = $base['minFaceValue'] ?? null;
        $max = $base['maxFaceValue'] ?? null;
        if (!is_numeric($min) || !is_numeric($max)) return false;
        if ($face < (float) $min || $face > (float) $max) return false;

        return in_array($face, self::ladderFor($base), true);
    }

    /**
     * Déplie un produit à plage en produits à montant fixe (échelle clipée à
     * [min, max]). Renvoie [$product] inchangé si < 2 montants tombent dans la
     * plage (une « échelle » d'un seul barreau n'apporte rien).
     */
    public static function expand(array $product): array
    {
        $min = (float) ($product['minFaceValue'] ?? 0);
        $max = (float) ($product['maxFaceValue'] ?? 0);

        $faces = array_values(array_filter(
            self::ladderFor($product),
            fn ($f) => $f >= $min && $f <= $max
        ));
        if (count($faces) < 2) {
            return [$product];
        }

        return array_map(fn ($face) => self::materialize($product, $face), $faces);
    }

    /**
     * Fabrique LE produit virtuel d'un montant donné à partir du produit à
     * plage. Aussi utilisé seul pour reconstruire un montant depuis son id
     * (cache froid, écrans admin).
     */
    public static function materialize(array $base, int $face): array
    {
        $minFace  = (float) ($base['minFaceValue'] ?? 0);
        $priceMin = (float) ($base['price']['min'] ?? $minFace);
        // Ratio prix/valeur du produit de base (remise éventuelle conservée).
        // Apple EU : price.min == minFaceValue → ratio 1.0 (pas de remise).
        $ratio = $minFace > 0 ? $priceMin / $minFace : 1.0;

        $virtual = $base;
        $virtual['id']           = self::id((int) $base['id'], $face);
        $virtual['virtual_of']   = (int) $base['id'];
        $virtual['minFaceValue'] = $face;
        $virtual['maxFaceValue'] = $face;
        $virtual['price'] = array_merge($base['price'] ?? [], [
            'min' => round($face * $ratio, 4),
            'max' => round($face * $ratio, 4),
        ]);

        $ctName   = $base['cardType']['name'] ?? $base['name'] ?? '';
        $currency = $base['price']['currencyCode'] ?? '';
        $virtual['name'] = trim($ctName . ' ' . $face . ' ' . $currency);

        return $virtual;
    }

    /**
     * Déplie une liste de produits (les non concernés passent tels quels).
     * Idempotent : les produits virtuels (min == max) ne matchent plus applies().
     *
     * ANTI-DOUBLON : certaines marques ont À LA FOIS des dénominations fixes ET
     * un produit à plage (Amazon France : 5/10/15…1500 € fixes + une plage
     * 5–5000 €). Les montants déjà couverts par un produit fixe de la MÊME carte
     * sont retirés de l'échelle — sinon la fiche afficherait deux fois « 10 € ».
     * Une plage entièrement couverte disparaît (c'était un doublon de son min).
     */
    public static function expandList(array $products): array
    {
        // 1. Montants déjà proposés en dur, par carte.
        $fixedFaces = [];
        foreach ($products as $product) {
            if (!is_array($product)) continue;
            $min = $product['minFaceValue'] ?? null;
            $max = $product['maxFaceValue'] ?? null;
            if (!is_numeric($min) || !is_numeric($max) || (float) $max !== (float) $min) {
                continue; // produit à plage → ne compte pas comme montant fixe
            }
            $fixedFaces[self::cardKey($product)][(string) (float) $min] = true;
        }

        // 2. Dépliage, en sautant les montants déjà couverts.
        $out = [];
        foreach ($products as $product) {
            if (!is_array($product) || !self::applies($product)) {
                $out[] = $product;
                continue;
            }

            $taken = $fixedFaces[self::cardKey($product)] ?? [];
            foreach (self::expand($product) as $virtual) {
                $face = (string) (float) $virtual['minFaceValue'];
                // Un produit non déplié (echelle < 2 barreaux) revient tel quel :
                // on le garde, c'est le produit d'origine.
                if (($virtual['id'] ?? null) === ($product['id'] ?? null)) {
                    $out[] = $virtual;
                    continue;
                }
                if (!isset($taken[$face])) $out[] = $virtual;
            }
        }
        return $out;
    }

    /**
     * Clé d'agrégation d'une carte : nom + devise, PAS l'id de cardType.
     * afrikard expose parfois la même carte sous deux cardTypes distincts —
     * « Amazon France » a l'id 5922 pour ses montants fixes et 15895 pour sa
     * plage libre. Le catalogue les regroupe déjà par nom+région+devise
     * (CatalogGrouping::dedupeByCardType) : on aligne le dédoublonnage dessus.
     */
    private static function cardKey(array $product): string
    {
        $name     = mb_strtolower(trim($product['cardType']['name'] ?? $product['name'] ?? '?'));
        $currency = strtoupper($product['price']['currencyCode'] ?? $product['cardType']['currencyCode'] ?? '');
        return $name . '|' . $currency;
    }
}
