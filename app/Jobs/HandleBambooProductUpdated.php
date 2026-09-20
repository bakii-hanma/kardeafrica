<?php

namespace App\Jobs;

use App\Services\ProductApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Bamboo `productupdated.v1` — invalidation du cache catalogue.
 *
 * Bamboo signale qu'un ou plusieurs produits ont changé (prix, stock,
 * disponibilité). On purge les vues dérivées (réseau web/mobile) et l'on force
 * un rafraîchissement du cache catalogue frais + snapshot via WarmCatalogJob.
 *
 * BORNÉ : le WarmCatalogJob est déjà protégé par le lock anti-flood (5 min),
 * donc un flux de webhooks productupdated ne reconstruit pas le catalogue en
 * continu — un seul rebuild à la fois.
 */
class HandleBambooProductUpdated implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 60;

    public function __construct(private array $products)
    {
    }

    public function handle(): void
    {
        $ids = collect($this->products)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();

        if (! empty($ids)) {
            Log::info('Bamboo productupdated: purge caches produits', ['product_ids' => $ids, 'count' => count($ids)]);
        }

        // 1. Purge ciblée des produits concernés (détail + light).
        foreach ($ids as $id) {
            Cache::forget("product_v3_{$id}");
            Cache::forget("product_light_v1_{$id}");
            Cache::forget("card_type_v3_{$id}");
            Cache::forget("card_type_v3_id_{$id}");
        }

        // 2. Purge des listes dérivées (accueil, top marques, facettes, filtres).
        Cache::forget('card_types_v4_slim_200');
        Cache::forget('card_types_v4_slim_12');
        Cache::forget('featured_card_types_eu_fr_v1');
        Cache::forget('card_type_counts_v1');
        Cache::forget('price_bounds_v1');

        // 3. Invalidation du catalogue complet (fraîs + snapshot) + warm async,
        //    protégé par le lock 5 min de dispatchWarmIfNeeded-equivalent.
        $service = app(ProductApiService::class);
        $service->invalidateCatalogCache();

        if (config('queue.default') === 'redis' && Cache::add('catalog_warm_dispatch_lock', 1, 300)) {
            try {
                WarmCatalogJob::dispatch()->onQueue('catalog');
            } catch (\Throwable $e) {
                Cache::forget('catalog_warm_dispatch_lock');
                Log::warning('HandleBambooProductUpdated: dispatch warm échoué', ['error' => $e->getMessage()]);
            }
        }
    }
}