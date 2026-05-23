<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\DaywatchProduct;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\User;
use App\Services\ProductApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SettingsController extends Controller
{
    public function index()
    {
        // Snapshot système (rendu côté serveur, le ping API se fait en AJAX côté JS)
        $info = $this->collectSystemInfo();

        $currencyRates = [
            'eur'        => (float) AppSetting::get('currency_rate_eur', 750),
            'usd'        => (float) AppSetting::get('currency_rate_usd', 700),
            'aed'        => (float) AppSetting::get('currency_rate_aed', 200),
            'round_step' => (int) AppSetting::get('currency_round_step', 100),
        ];

        return view('admin.settings.index', compact('info', 'currencyRates'));
    }

    /**
     * Bascule le mode maintenance via flag BDD (table app_settings).
     * - Le middleware CheckMaintenanceMode lit ce flag à chaque requête
     * - Les admins authentifiés sont automatiquement bypass
     * - Pas d'utilisation de Laravel native (php artisan down) qui bloquait l'admin
     */
    public function toggleMaintenance(Request $request)
    {
        try {
            $isDown = !AppSetting::isMaintenanceMode();
            AppSetting::setMaintenanceMode($isDown);

            $message = $isDown
                ? 'Mode maintenance ACTIVÉ. Le site public est en pause — tu gardes ton accès admin.'
                : 'Mode maintenance désactivé. Le site est de nouveau accessible.';

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'is_down' => $isDown,
                    'message' => $message,
                ]);
            }

            return back()->with('success', $message);
        } catch (Throwable $e) {
            Log::error('Toggle maintenance error', ['error' => $e->getMessage()]);

            if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur : ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Renvoie l'état actuel du mode maintenance (lecture BDD).
     */
    public function maintenanceStatus()
    {
        return response()->json([
            'is_down' => AppSetting::isMaintenanceMode(),
        ]);
    }

    /**
     * Vide les caches applicatifs (cache, config, route, view).
     */
    public function clearCache(Request $request)
    {
        $request->validate([
            'kind' => 'required|in:app,config,route,view,all',
        ]);

        try {
            $messages = [];

            if (in_array($request->kind, ['app', 'all'])) {
                Artisan::call('cache:clear');
                $messages[] = 'Cache applicatif vidé.';
            }
            if (in_array($request->kind, ['config', 'all'])) {
                Artisan::call('config:clear');
                $messages[] = 'Cache de configuration vidé.';
            }
            if (in_array($request->kind, ['route', 'all'])) {
                Artisan::call('route:clear');
                $messages[] = 'Cache des routes vidé.';
            }
            if (in_array($request->kind, ['view', 'all'])) {
                Artisan::call('view:clear');
                $messages[] = 'Cache des vues vidé.';
            }

            return back()->with('success', implode(' ', $messages));
        } catch (Throwable $e) {
            return back()->with('error', "Erreur lors du vidage : " . $e->getMessage());
        }
    }

    /**
     * Met à jour les taux de change FCFA et le palier d'arrondi.
     * Persistés en BDD (table app_settings, cache invalidé via AppSetting::set).
     */
    public function updateCurrencyRates(Request $request)
    {
        $validated = $request->validate([
            'rate_eur'   => 'required|numeric|min:1|max:5000',
            'rate_usd'   => 'required|numeric|min:1|max:5000',
            'rate_aed'   => 'required|numeric|min:1|max:5000',
            'round_step' => 'required|integer|min:1|max:10000',
        ]);

        try {
            AppSetting::set('currency_rate_eur',   (string) $validated['rate_eur']);
            AppSetting::set('currency_rate_usd',   (string) $validated['rate_usd']);
            AppSetting::set('currency_rate_aed',   (string) $validated['rate_aed']);
            AppSetting::set('currency_round_step', (string) $validated['round_step']);

            // Reset le memo in-process pour cette requête.
            \App\Support\Money::resetMemo();

            // Invalide les caches qui mettent en cache des prix FCFA déjà calculés.
            // ⚠️ NE PAS faire Cache::flush() — ça wipe aussi le catalogue afrikard
            //    et la prochaine requête prend 20-60s pour le refetch complet.
            //    On ne touche qu'au cache des cardTypes et popular qui contient
            //    des price_fcfa pré-calculés ; le catalogue brut afrikard reste
            //    et les prix se recalculent à la volée à partir de native+currency.
            $this->invalidatePricedCaches();

            Log::info('Currency rates updated', $validated);

            return back()->with('success', 'Taux de change mis à jour. Les nouveaux prix FCFA s\'appliquent immédiatement.');
        } catch (Throwable $e) {
            Log::error('Currency rates update error', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * Invalide les caches qui contiennent des prix FCFA pré-calculés.
     * Préserve le cache du catalogue brut afrikard pour éviter un refetch
     * complet (20-60s) à chaque update admin des taux.
     */
    private function invalidatePricedCaches(): void
    {
        // Caches contenant des price_fcfa figés (à recalculer)
        Cache::forget('card_types_v4_slim_12');
        Cache::forget('card_types_v4_slim_24');
        Cache::forget('card_types_v4_slim_50');
        Cache::forget('card_types_v4_slim_99999');

        // Pour les cardTypes individuels (richement détaillés) — on en a peu,
        // on les laisse expirer naturellement (TTL court).
        // Idem pour le catalogue slim brut : il n'a PAS de prix FCFA,
        // juste native_value + currency, donc les prix sont recalculés au
        // moment de la sérialisation (slimItem) → pas besoin d'invalider.
    }

    /**
     * Test envoi mail vers une adresse.
     */
    public function testMail(Request $request)
    {
        $request->validate(['to' => 'required|email']);

        try {
            Mail::raw(
                "Test SMTP KardAfrica\n\nSi tu reçois ce message, la configuration mail fonctionne.\n\nDate : " . now()->toDateTimeString(),
                function ($m) use ($request) {
                    $m->to($request->to)->subject('[KardAfrica] Test SMTP — ' . now()->format('d/m H:i'));
                }
            );
            return back()->with('success', "Email de test envoyé à {$request->to}.");
        } catch (Throwable $e) {
            return back()->with('error', "Échec de l'envoi : " . $e->getMessage());
        }
    }

    /**
     * Snapshot d'infos système pour la page Paramètres.
     */
    private function collectSystemInfo(): array
    {
        $dbDriver = config('database.default');
        $dbSize = null;
        if ($dbDriver === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            if ($path && File::exists($path)) {
                $dbSize = $this->formatBytes(File::size($path));
            }
        }

        return [
            'app' => [
                'name'        => config('app.name'),
                'env'         => config('app.env'),
                'debug'       => (bool) config('app.debug'),
                'url'         => config('app.url'),
                'locale'      => config('app.locale'),
                'timezone'    => config('app.timezone'),
                'php'         => PHP_VERSION,
                'laravel'     => app()->version(),
            ],
            'maintenance' => [
                'down' => AppSetting::isMaintenanceMode(),
            ],
            'database' => [
                'driver' => $dbDriver,
                'size'   => $dbSize,
            ],
            'queue' => [
                'driver' => config('queue.default'),
                'sync'   => config('queue.default') === 'sync',
            ],
            'cache' => [
                'driver' => config('cache.default'),
            ],
            'mail' => [
                'mailer' => config('mail.default'),
                'from'   => config('mail.from.address'),
                'host'   => config('mail.mailers.smtp.host'),
            ],
            'afrikard' => [
                'base_url' => config('services.product_api.base_url', 'https://afrikard-api.duckdns.org/api/v1'),
            ],
            'futursowax' => [
                'base_url' => config('services.futursowax.base_url'),
                'configured' => (bool) config('services.futursowax.api_key'),
            ],
            'counts' => [
                'users'         => User::count(),
                'orders'        => Order::count(),
                'orders_paid'   => Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)->count(),
                'orders_pending'=> Order::where('payment_status', Order::PAYMENT_STATUS_COMPLETED)
                                        ->whereDoesntHave('userCards')->count(),
                'daywatch'      => DaywatchProduct::count(),
                'newsletter'    => NewsletterSubscriber::where('is_active', true)->count(),
            ],
            'storage' => [
                'cache_dir_size'   => $this->dirSize(storage_path('framework/cache')),
                'view_dir_size'    => $this->dirSize(storage_path('framework/views')),
                'session_dir_size' => $this->dirSize(storage_path('framework/sessions')),
                'logs_size'        => $this->dirSize(storage_path('logs')),
            ],
        ];
    }

    private function dirSize(string $path): ?string
    {
        if (!File::isDirectory($path)) return null;
        $size = 0;
        foreach (File::allFiles($path) as $f) {
            $size += $f->getSize();
        }
        return $this->formatBytes($size);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' o';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' Ko';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / 1024 / 1024, 1) . ' Mo';
        return round($bytes / 1024 / 1024 / 1024, 2) . ' Go';
    }
}
