<?php

namespace App\Http\Controllers;

use App\Jobs\RunBambooReconciliation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Déclencheur de réconciliation Bamboo, appelé par l'API (afrikard).
 *
 * Le cron de réconciliation vit côté API (scheduler Spring de l'app afrikard,
 * actif 24/7 sur le VPS), et non dans l'app Laravel (hébergement mutualisé sans
 * cron). Chaque jour à 06:15 (Africa/Libreville), l'API POSTe sur ce endpoint.
 *
 * Sécurité : endpoint public → protégé par un secret partagé envoyé dans le
 * header `X-Bamboo-Reconcile-Secret`. Fail-closed : secret absent/mauvais → 403.
 * On répond 202 immédiatement, le travail est délégué à un job (queue Redis).
 */
class BambooReconcileController extends Controller
{
    public function trigger(Request $request)
    {
        $secret = (string) config('services.bamboo.reconcile_secret');

        if ($secret === '') {
            Log::warning('Bamboo reconcile: secret non configuré — rejeté.');
            return response()->json(['ok' => false], 403);
        }

        $provided = (string) $request->header('X-Bamboo-Reconcile-Secret', '');
        if (! hash_equals($secret, $provided)) {
            Log::warning('Bamboo reconcile: secret invalide.', ['ip' => $request->ip()]);
            return response()->json(['ok' => false], 403);
        }

        RunBambooReconciliation::dispatch();

        return response()->json(['ok' => true, 'scheduled' => true], 202);
    }
}