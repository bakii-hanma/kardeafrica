<?php

namespace App\Services;

/**
 * Classe un produit du catalogue à partir de son nom, de son code pays et de
 * son id fournisseur.
 *
 * Aucune dépendance à Eloquent : la classe est pure, donc testable sans base.
 *
 *   $c = app(CatalogClassifier::class);
 *   $c->classify('Netflix EU', 'EU', 4528);
 *   // ['category_id' => 1, 'redeem_model' => 'account_region', 'visible' => true]
 */
class CatalogClassifier
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? config('catalog');
    }

    /**
     * @return array{category_id:int, redeem_model:string, visible:bool, matched_by:string}
     */
    public function classify(
        string $name,
        ?string $countryCode = null,
        int|string|null $brandId = null,
        ?string $currencyCode = null,
    ): array {
        // 1. Exception manuelle : priorité absolue
        $overrides = $this->config['overrides'] ?? [];
        if ($brandId !== null && isset($overrides[$brandId])) {
            $o = $overrides[$brandId];
            $category = $o['category_id'] ?? $this->matchCategory($name)[0];
            $model    = $o['redeem_model'] ?? $this->matchRedeemModel($name, $countryCode, $category);

            return $this->result($category, $model, 'override', $countryCode, $currencyCode, $name);
        }

        // 2. Règles
        [$category, $matchedBy] = $this->matchCategory($name);
        $model = $this->matchRedeemModel($name, $countryCode, $category);

        return $this->result($category, $model, $matchedBy, $countryCode, $currencyCode, $name);
    }

    /** @return array{0:int, 1:string} */
    private function matchCategory(string $name): array
    {
        foreach ($this->config['category_rules'] as $i => [$categoryId, $pattern]) {
            if (preg_match($pattern, $name) === 1) {
                return [$categoryId, 'rule#' . $i];
            }
        }

        return [$this->config['category_fallback'], 'fallback'];
    }

    private function matchRedeemModel(string $name, ?string $countryCode, int $categoryId): string
    {
        $r = $this->config['redeem'];

        // Rayons toujours globaux (crypto, IA)
        if (in_array($categoryId, $r['global_categories'], true)) {
            return 'global';
        }

        // Le retail physique ne devient jamais global parce que son nom
        // contient "Global" (cf. "Global Experience Card").
        $isPhysicalCategory = in_array($categoryId, $r['physical_categories'], true);

        if (! $isPhysicalCategory) {
            $globalByCountry = $countryCode !== null
                && in_array(strtoupper($countryCode), $r['global_country_codes'], true);

            if ($globalByCountry
                || preg_match($r['global_name'], $name) === 1
                || preg_match($r['global_brand'], $name) === 1) {
                return 'global';
            }
        }

        if (in_array($categoryId, $r['account_region_categories'], true)) {
            return 'account_region';
        }

        // Exception pays sur un rayon physique : le retail français reste
        // utilisable depuis le Gabon (commande en ligne, livraison à un proche).
        if ($isPhysicalCategory
            && $countryCode !== null
            && in_array(strtoupper($countryCode), $r['physical_visible_countries'] ?? [], true)) {
            return 'account_region';
        }

        // Voyage réservable en ligne
        if (preg_match($r['online_travel'], $name) === 1) {
            return 'account_region';
        }

        return 'physical';
    }

    /**
     * @return array{category_id:int, redeem_model:string, visible:bool,
     *               matched_by:string, hidden_reason:?string,
     *               priority_score:int, is_popular:bool}
     */
    private function result(int $categoryId, string $model, string $matchedBy, ?string $countryCode, ?string $currencyCode, string $name = ''): array
    {
        $rachetable = in_array($model, $this->config['visible_redeem_models'], true);

        // Devise absente = on ne sait pas convertir ET on ne sait pas si le
        // client peut s'en servir : dans les deux cas, pas de vitrine.
        $devises   = $this->config['market_currencies'] ?? [];
        $surMarche = $currencyCode === null
            ? true   // devise inconnue de l'appelant : on ne tranche pas ici
            : in_array(strtoupper($currencyCode), $devises, true);

        // La raison est conservée : un rapport qui dit « 900 masqués » sans
        // dire pourquoi n'aide personne à décider.
        $raison = match (true) {
            ! $rachetable => 'redeem_model:' . $model,
            ! $surMarche  => 'devise:' . strtoupper((string) $currencyCode),
            default       => null,
        };

        $score = $this->score($categoryId, $model, $countryCode, $currencyCode, $name);

        return [
            'category_id'    => $categoryId,
            'redeem_model'   => $model,
            'visible'        => $rachetable && $surMarche,
            'matched_by'     => $matchedBy,
            'hidden_reason'  => $raison,
            'priority_score' => $score,
            // Une carte masquée n'est jamais « populaire » : elle ne peut pas
            // être mise en avant sur une page où elle n'apparaît pas.
            'is_popular'     => ($rachetable && $surMarche)
                && $score >= ($this->config['priority']['popular_threshold'] ?? 50),
        ];
    }

    /**
     * Score de mise en avant.
     *
     * Additionne quatre signaux plutôt que de trancher sur la seule géographie :
     * « Decathlon France » est française ET en vitrine, mais ne doit jamais
     * remonter en tête — c'est son rayon qui la retient.
     */
    private function score(
        int $categoryId,
        string $model,
        ?string $countryCode,
        ?string $currencyCode,
        string $name = '',
    ): int {
        $p = $this->config['priority'] ?? [];

        $score = $p['geo'][strtoupper((string) $countryCode)] ?? ($p['geo_default'] ?? 0);
        $score += $p['redeem'][$model] ?? 0;

        if (($this->config['categories'][$categoryId]['featured'] ?? false) === true) {
            $score += $p['category_featured'] ?? 0;
        }

        $score += $p['category_penalty'][$categoryId] ?? 0;

        $devises = $this->config['market_currencies'] ?? [];
        $score += $currencyCode !== null && ! in_array(strtoupper($currencyCode), $devises, true)
            ? ($p['currency_off_market'] ?? 0)
            : ($p['currency_in_market'] ?? 0);

        // Notoriété : premier palier atteint, on s'arrête — les paliers sont
        // exclusifs, une marque forte ne cumule pas avec le palier moyen.
        foreach ($p['notoriety'] ?? [] as $bonus => $motif) {
            if (preg_match($motif, $name) === 1) {
                $score += (int) $bonus;
                break;
            }
        }

        return $score;
    }
}
