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
 * Rafraîchit le cache catalogue afrikard EN ARRIÈRE-PLAN (queue Redis).
 *
 * Dispatché par ProductApiService::dispatchWarmIfNeeded() dès qu'une requête
 * web tombe sur un cache expiré. Le visiteur, lui, est servi immédiatement
 * depuis le snapshot (stale-while-revalidate) ; ce job reconstruit le cache
 * frais + le snapshot + les listes dérivées (accueil, boutique) sans jamais
 * bloquer une page.
 *
 * Doit tourner sur un worker : `php artisan queue:work redis --queue=catalog`.
 */
class WarmCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Une seule tentative : le prochain hit web redéclenchera si besoin. */
    public $tries = 1;

    /** Le fetch complet + enrichissement peut prendre 1-2 min. */
    public $timeout = 300;

    public function handle(ProductApiService $service): void
    {
        try {
            $items = $service->rebuildCatalogCache();

            if (count($items) >= 500) {
                // Invalide les listes dérivées AVANT recalcul (sinon on ré-affiche
                // l'ancien top marques / l'ancienne liste curée).
                Cache::forget('card_types_v4_slim_200');
                Cache::forget('card_types_v4_slim_12');
                Cache::forget('featured_card_types_eu_fr_v1');
                Cache::forget('card_type_counts_v1');

                // Pré-calcule les listes servies au premier écran pour qu'elles
                // soient instantanées elles aussi (accueil + top marques).
                $service->getCardTypes(200);
                $service->getFeaturedCardTypes();
                Log::info('WarmCatalogJob: cache catalogue rafraîchi (' . count($items) . ' produits).');
            } else {
                Log::warning('WarmCatalogJob: afrikard a renvoyé ' . count($items) . ' produits (< 500) — cache conservé.');
            }
        } finally {
            // Libère le lock anti-flood pour autoriser un prochain refresh.
            Cache::forget('catalog_warm_dispatch_lock');
        }
    }
}
