<?php

namespace App\Console\Commands;

use App\Services\ProductApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Pré-charge le cache du catalogue afrikard.
 *
 * Le fetch complet de l'API afrikard prend ~70s (50 pages × ~1.4s). En contexte
 * HTTP, ça dépasse `max_execution_time` PHP. Cette commande tourne en CLI sans
 * limite de temps, peuple le cache, et les requêtes web suivantes répondent
 * instantanément depuis le cache.
 *
 * À ajouter au cron (ex: toutes les 50 minutes pour rafraîchir avant que le
 * cache de 1h n'expire) :
 *   * /50 * * * * cd /chemin/vers/site && php artisan catalog:warm > /dev/null
 *
 * Ou à lancer manuellement après chaque déploiement / cache:clear.
 */
class WarmCatalogCache extends Command
{
    protected $signature = 'catalog:warm {--force : Vide le cache avant de le repeupler}';

    protected $description = 'Pré-charge le cache du catalogue afrikard (à lancer après cache:clear ou en cron).';

    public function handle(ProductApiService $service): int
    {
        if ($this->option('force')) {
            $this->info('Vidage du cache catalogue…');
            Cache::forget('processed_all_products_v7_slim');
            Cache::forget('processed_all_products_snapshot_v1');
            Cache::forget('featured_card_types_eu_fr_v1');
            Cache::forget('card_type_counts_v1');
            // Bumper aussi les caches dérivés
            for ($i = 1; $i <= 200; $i++) {
                Cache::forget("card_types_v4_slim_{$i}");
            }
        }

        $this->info('Récupération + enrichissement EU/FR du catalogue afrikard (peut prendre 1-2 min)…');
        $start = microtime(true);

        // Build complet (fetch toutes pages + enrichissement variantes EU/FR),
        // écrit le cache FRAIS + le SNAPSHOT longue durée servi au web.
        $items = $service->rebuildCatalogCache();
        $elapsed = round(microtime(true) - $start, 1);

        if (count($items) < 500) {
            $this->error("Échec : seulement " . count($items) . " produits récupérés en {$elapsed}s.");
            $this->error('L\'API afrikard est probablement injoignable. Réessaie plus tard.');
            return self::FAILURE;
        }

        $this->info("✓ Catalogue récupéré : " . count($items) . " produits en {$elapsed}s.");

        // Pré-calcule les listes servies au premier écran (accueil + top marques
        // + liste curée EU/FR) pour qu'elles soient instantanées elles aussi.
        $this->info('Pré-calcul des types de cartes + liste curée EU/FR…');
        Cache::forget('card_types_v4_slim_200');
        Cache::forget('card_types_v4_slim_12');
        Cache::forget('featured_card_types_eu_fr_v1');
        $service->getCardTypes(200);
        $featured = $service->getFeaturedCardTypes();
        $this->info('✓ ' . count($featured) . ' marques curées prêtes pour l\'accueil.');

        $this->info('✓ Cache catalogue prêt. Les pages web répondent depuis le cache.');
        return self::SUCCESS;
    }
}
