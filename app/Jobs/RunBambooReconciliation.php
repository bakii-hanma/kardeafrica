<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Exécution de la réconciliation Bamboo, déclenchée par l'API via
 * POST /api/bamboo/reconcile (voir BambooReconcileController).
 *
 * Déléguée en file : l'endpoint répond 202 vite, le job tourne dans le worker
 * de queue (drainé par le schedule Laravel). Idempotent par construction :
 * la commande orders:reconcile ne fait que des GET + recover() (jamais de
 * re-POST), et withoutOverlapping était déjà garanti par le schedule.
 */
class RunBambooReconciliation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public $timeout = 600;

    public function handle(): void
    {
        Log::info('Bamboo reconcile: démarre la réconciliation (jobs).');

        $exit = Artisan::call('orders:reconcile', [
            '--days' => '7',
            '--run'  => true,
        ]);

        Log::info('Bamboo reconcile: terminée.', [
            'exit'   => $exit,
            'output' => trim(Artisan::output()),
        ]);
    }
}