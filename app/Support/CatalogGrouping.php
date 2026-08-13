<?php

namespace App\Support;

/**
 * CatalogGrouping — dédoublonnage des listings par IDENTITÉ PRODUIT (P1 §1).
 *
 * Identité = cardType (marque + région + devise). Variante = un montant.
 * Un listing ne doit JAMAIS montrer « PSN FR 10 EUR », « PSN FR 20 EUR »…
 * en cards séparées : une seule card par cardType, prix « à partir de »,
 * avec le résumé des variantes (mini-montants cliquables).
 *
 * Classe pure (aucune dépendance service) → testée unitairement.
 */
class CatalogGrouping
{
    /**
     * Regroupe une liste de produits individuels par cardType.
     *
     * Retourne UNE entrée par cardType : le produit le MOINS CHER comme
     * représentant (cohérent avec « à partir de »), enrichi de :
     *  - `variants` : toutes les variantes triées par prix croissant
     *    [['product_id', 'face', 'currency', 'price_min'], …]
     *  - `variants_count` : nombre de montants disponibles.
     *
     * Les produits sans cardType id (ex. items exotiques) restent tels quels,
     * chacun comme sa propre entrée.
     *
     * @param  array<int,array> $products
     * @return array<int,array>
     */
    public static function dedupeByCardType(array $products): array
    {
        $groups  = [];   // clé identité => ['rep' => produit, 'variants' => [...]]
        $orphans = [];   // produits sans cardType — conservés dans l'ordre
        $order   = [];   // ordre de première apparition (stabilité du tri amont)

        foreach ($products as $product) {
            $ctId = $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? null;

            if ($ctId === null) {
                $orphans[] = $product;
                continue;
            }

            // Identité produit (P1 §1) = MARQUE + RÉGION + DEVISE — pas l'id
            // technique : afrikard expose parfois deux cardTypes distincts pour
            // le même « Netflix UK » → une seule card (les mini-montants portent
            // chacun leur URL, donc les variantes des deux gammes restent accessibles).
            $ctKey = mb_strtolower(trim($product['cardType']['name'] ?? '')) !== ''
                ? mb_strtolower(trim($product['cardType']['name']))
                    . '|' . strtoupper($product['cardType']['countryCode'] ?? '')
                    . '|' . strtoupper($product['price']['currencyCode'] ?? '')
                : (string) $ctId;
            $ctId = $ctKey;

            $variant = [
                'product_id'   => $product['id'] ?? null,
                'face'         => (float) ($product['minFaceValue'] ?? ($product['price']['min'] ?? 0)),
                'currency'     => $product['price']['currencyCode'] ?? 'XAF',
                'price_min'    => (float) ($product['price']['min'] ?? 0),
                // Fiche d'origine de CETTE variante (deux gammes du même nom
                // peuvent être fusionnées dans une card → chaque pill garde son URL).
                'card_type_id' => $product['cardType']['internalId'] ?? $product['cardType']['id'] ?? null,
            ];

            if (!isset($groups[$ctId])) {
                $order[] = $ctId;
                $groups[$ctId] = ['rep' => $product, 'variants' => [$variant]];
                continue;
            }

            $groups[$ctId]['variants'][] = $variant;

            // Représentant = la variante la moins chère (« à partir de »)
            $currentMin = (float) ($groups[$ctId]['rep']['price']['min'] ?? PHP_FLOAT_MAX);
            if ($variant['price_min'] > 0 && $variant['price_min'] < $currentMin) {
                $groups[$ctId]['rep'] = $product;
            }
        }

        $result = [];
        foreach ($order as $ctId) {
            $rep = $groups[$ctId]['rep'];
            $variants = $groups[$ctId]['variants'];
            usort($variants, fn ($a, $b) => $a['price_min'] <=> $b['price_min']);

            $rep['variants']       = $variants;
            $rep['variants_count'] = count($variants);
            $result[] = $rep;
        }

        return array_merge($result, $orphans);
    }

    /**
     * Résout la variante d'un cardType correspondant à un « montant » d'URL
     * (valeur faciale, ex. 50 pour « Xbox FR 50 EUR »). Match sur la valeur
     * faciale arrondie ; null si aucun montant ne correspond.
     *
     * @param array<int,array> $products  cardType['products']
     */
    public static function resolveVariantByFace(array $products, float $montant): ?array
    {
        foreach ($products as $p) {
            $face = (float) ($p['minFaceValue'] ?? ($p['price']['min'] ?? 0));
            if (abs($face - $montant) < 0.005) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Variante par défaut d'une fiche (URL racine sans montant) : la MOINS
     * CHÈRE — proxy déterministe de « la plus populaire » (aucune donnée de
     * ventes par variante côté afrikard), cohérent avec « à partir de ».
     */
    public static function defaultVariant(array $products): ?array
    {
        $best = null;
        foreach ($products as $p) {
            $price = (float) ($p['price']['min'] ?? 0);
            if ($price <= 0) continue;
            if ($best === null || $price < (float) $best['price']['min']) {
                $best = $p;
            }
        }
        return $best ?? ($products[0] ?? null);
    }
}
