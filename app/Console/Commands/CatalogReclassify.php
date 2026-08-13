<?php

namespace App\Console\Commands;

use App\Services\CatalogClassifier;
use App\Services\ProductApiService;
use Illuminate\Console\Command;

/**
 * Rapport de reclassification du catalogue — AVANT bascule du drapeau.
 *
 * Le catalogue n'est pas persisté (classement en mémoire, décision du 12 août) :
 * cette commande n'écrit donc jamais rien. Elle rejoue le classifieur sur le
 * catalogue réel et montre ce que la bascule de `catalog.use_classifier`
 * changerait — répartition par rayon, par modèle de rachat, raisons de
 * masquage, écarts avec la catégorisation héritée, échantillon de changements.
 *
 * C'est le « --dry-run » du prompt, adapté à l'architecture réelle : le rapport
 * se lit AVANT de poser CATALOG_CLASSIFIER=true.
 */
class CatalogReclassify extends Command
{
    protected $signature = 'catalog:reclassify
        {--dry-run : Accepté pour compatibilité — la commande ne modifie jamais rien}
        {--sample=12 : Nombre d\'exemples de changements à afficher}';

    protected $description = 'Compare la catégorisation héritée et le nouveau classifieur (aucune écriture)';

    public function handle(ProductApiService $api): int
    {
        $classifier = new CatalogClassifier(config('catalog'));
        $rayons     = config('catalog.categories');

        $this->info('Lecture du catalogue (cache 24 h ou API)…');
        $items = $api->getFilteredProducts([], 1, 20000)['items'] ?? [];

        if ($items === []) {
            $this->error('Catalogue vide — API indisponible et aucun snapshot.');

            return self::FAILURE;
        }

        $parRayon = $parModele = $raisons = [];
        $changes  = [];
        $identiques = 0;

        foreach ($items as $p) {
            $ct  = $p['cardType'] ?? [];
            $nom = trim(($ct['name'] ?? '') . ' ' . ($p['name'] ?? ''));

            $v = $classifier->classify(
                $nom,
                $ct['region_code'] ?? $ct['countryCode'] ?? null,
                $ct['id'] ?? null,
                $p['price']['currencyCode'] ?? null,
            );

            $parRayon[$v['category_id']] = ($parRayon[$v['category_id']] ?? 0) + 1;
            $parModele[$v['redeem_model']] = ($parModele[$v['redeem_model']] ?? 0) + 1;

            if ($v['hidden_reason'] !== null) {
                $cle = explode(':', $v['hidden_reason'])[0];
                $raisons[$cle] = ($raisons[$cle] ?? 0) + 1;
            }

            // Écart avec l'existant : l'héritage est multi-rayons, le nouveau
            // n'en a qu'un. « Changement » = le rayon retenu n'était pas déjà
            // porté par le produit, ou sa visibilité bascule.
            $anciens = array_column($ct['categories'] ?? [], 'id');
            $memeRayon = in_array($v['category_id'], $anciens, true);
            $etaitVisible = true; // l'héritage n'a pas de notion de visibilité

            if ($memeRayon && $v['visible'] === $etaitVisible) {
                $identiques++;
            } elseif (count($changes) < 400) {
                $changes[] = [
                    'nom'    => mb_substr($nom, 0, 40),
                    'avant'  => $anciens === [] ? '(orphelin)' : implode('+', $anciens),
                    'apres'  => $v['category_id'] . ' ' . ($rayons[$v['category_id']]['name'] ?? '?'),
                    'modele' => $v['redeem_model'],
                    'sort'   => $v['visible'] ? 'vitrine' : 'masqué (' . $v['hidden_reason'] . ')',
                ];
            }
        }

        $total = count($items);

        $this->newLine();
        $this->info("=== {$total} produits analysés — AUCUNE écriture ===");

        $this->newLine();
        $this->line('<comment>Répartition par rayon :</comment>');
        ksort($parRayon);
        $this->table(
            ['Rayon', 'Réf.', '%'],
            collect($parRayon)->map(fn ($n, $id) => [
                $id . ' ' . ($rayons[$id]['name'] ?? '?'),
                $n,
                round($n / $total * 100, 1) . ' %',
            ])->values()->all(),
        );

        $this->line('<comment>Par modèle de rachat :</comment>');
        $this->table(['Modèle', 'Réf.', '%'], collect($parModele)->map(
            fn ($n, $m) => [$m, $n, round($n / $total * 100, 1) . ' %'],
        )->values()->all());

        $vitrine = ($parModele['global'] ?? 0) + ($parModele['account_region'] ?? 0)
            - array_sum($raisons) + ($raisons['redeem_model'] ?? 0);
        $visibles = $total - array_sum($raisons);

        $this->line('<comment>Visibilité :</comment>');
        $this->table(['', 'Réf.'], array_merge(
            [['En vitrine', $visibles]],
            collect($raisons)->map(fn ($n, $r) => ['Masqués — ' . $r, $n])->values()->all(),
        ));

        $this->line('<comment>Écarts avec la catégorisation héritée :</comment>');
        $this->line("  inchangés : {$identiques} · modifiés : " . ($total - $identiques));

        $this->newLine();
        $this->line('<comment>Échantillon de changements :</comment>');
        $this->table(
            ['Produit', 'Rayons avant', 'Rayon après', 'Rachat', 'Sort'],
            array_map(
                fn ($c) => [$c['nom'], $c['avant'], $c['apres'], $c['modele'], $c['sort']],
                array_slice($changes, 0, (int) $this->option('sample')),
            ),
        );

        $this->newLine();
        $this->info('Pour appliquer : CATALOG_CLASSIFIER=true dans .env (bascule instantanée, cache v9 séparé).');

        return self::SUCCESS;
    }
}
