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
