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
            Cache::forget('catalog_all_pages_v3_robust_size_100');
            Cache::forget('processed_all_products_v4_regions');
            Cache::forget('processed_all_products_v5_slim');
            // Bumper aussi les caches dérivés
            for ($i = 1; $i <= 200; $i++) {
                Cache::forget("card_types_v3_{$i}");
                Cache::forget("card_types_v4_slim_{$i}");
            }
        }

        $this->info('Récupération de toutes les pages afrikard (peut prendre 1-2 min)…');
        $start = microtime(true);

        // Pas de budget temps en CLI : on prend tout le temps qu'il faut
        $items = $service->fetchAllCatalogPages(100, 50, null);
        $elapsed = round(microtime(true) - $start, 1);

        if (count($items) < 500) {
            $this->error("Échec : seulement " . count($items) . " produits récupérés en {$elapsed}s.");
            $this->error('L\'API afrikard est probablement injoignable. Réessaie plus tard.');
            return self::FAILURE;
        }

        $this->info("✓ Catalogue récupéré : " . count($items) . " produits en {$elapsed}s.");

        // Touche getCardTypes pour que la home soit instantanée elle aussi
        $this->info('Pré-calcul des types de cartes pour la page d\'accueil…');
        $service->getCardTypes(12);
        $service->getCardTypes(20);

        $this->info('✓ Cache catalogue prêt. Les pages web vont répondre depuis le cache.');
        return self::SUCCESS;
    }
}
