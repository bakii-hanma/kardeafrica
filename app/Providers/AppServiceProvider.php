<?php

namespace App\Providers;

use App\Models\Order;
use App\Observers\OrderObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Compteurs de la navigation admin : une seule instance par requête,
        // l'arborescence étant rendue deux fois (volet de bureau + tiroir).
        $this->app->singleton(\App\Support\AdminBadges::class);

        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // L'interface est intégralement en français mais `APP_LOCALE` vaut `en` :
        // sans cette ligne, tout l'espace vendeur affiche « 32 minutes ago » et
        // « Expires in 5 minutes » au comptoir.
        \Illuminate\Support\Carbon::setLocale('fr');

        // Pagination de la console admin : les `->links()` utilisent le rendu
        // Tailwind par défaut, incohérent avec le design « Layered ». On branche
        // une vue custom (tokens .adm) pour les vues pleines ET simples, mais
        // UNIQUEMENT côté admin — le front-office (`/boutique`, `/gabon`, espace
        // vendeur…) garde la pagination par défaut (Tailwind, raccord avec lui).
        // L'else remet explicitement le défaut Tailwind car les propriétés
        // statiques persistent entre boot dans un même processus (Octane, Octane
        // fork, Workers…).
        if (($this->app['request'] ?? null)?->is('admin/*')) {
            \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.kardafrica');
            \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.kardafrica-simple');
        } else {
            \Illuminate\Pagination\Paginator::useTailwind();
        }

        // Le catalogue vit en mémoire (pas de table produits) et le dépliage
        // généralisé des plages l'a porté à ~19 000 entrées. Le premier build
        // — catalogue brut + traité + sérialisation du cache (~20 Mo) —
        // dépasse les 128 Mo par défaut du serveur de dev. 256 Mo donne la
        // marge sans masquer une vraie fuite ; on ne touche pas à un plafond
        // déjà plus haut (prod) ni illimité (-1).
        $limite = ini_get('memory_limit');
        if ($limite !== '-1' && (int) $limite > 0 && (int) $limite < 256) {
            ini_set('memory_limit', '256M');
        }


        // Phase 1 — notification WhatsApp « commande prête » à la complétion.
        Order::observe(OrderObserver::class);
    }
}
