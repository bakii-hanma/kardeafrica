<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Libère les fonds bloqués des commandes cash expirées (toutes les 5 minutes)
Schedule::command('cash:expire-orders')->everyFiveMinutes()->withoutOverlapping();

// Garde le cache catalogue afrikard chaud (refresh toutes les 50 min, juste
// avant l'expiration du cache de 1h). Évite que le premier visiteur paie le
// fetch complet de ~70s qui dépasse max_execution_time PHP.
Schedule::command('catalog:warm')->everyThirtyMinutes()->withoutOverlapping();

// Traite la file d'attente (livraisons afrikard asynchrones : ProcessCheckoutJob).
// CRITIQUE sur hébergement mutualisé : aucun worker persistant ne tourne, donc
// sans ceci les commandes en fallback async restaient bloquées et les clients
// n'obtenaient JAMAIS leurs codes. --stop-when-empty : le worker s'arrête dès la
// file vidée (pas de démon). La garde d'idempotence de ProcessCheckoutJob évite
// tout double appel Bamboo si un job est rejoué.
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3 --backoff=30')
    ->everyMinute()
    ->withoutOverlapping();
