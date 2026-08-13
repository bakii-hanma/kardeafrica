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
// H15 : le worker DOIT consommer aussi la file `catalog` (WarmCatalogJob y est
// dispatché en config Redis). Sans --queue=default,catalog, ces jobs
// s'accumulaient indéfiniment et le stale-while-revalidate du catalogue était mort.
$queueEvent = Schedule::command('queue:work --queue=default,catalog --stop-when-empty --max-time=55 --tries=3 --backoff=30')
    ->everyMinute()
    ->withoutOverlapping();

// H6 : dead-man's switch — ping un moniteur externe (healthchecks.io ou
// équivalent) à chaque passage réussi. Si le cron meurt sur l'hébergement
// mutualisé, l'absence de ping déclenche une alerte au lieu d'un silence total.
// Activé uniquement si HEALTHCHECK_QUEUE_URL est défini.
if (config('services.healthcheck.queue_url')) {
    $queueEvent->thenPing(config('services.healthcheck.queue_url'));
}

// Phase 3 — relances WhatsApp programmées (enfilent des jobs SendWhatsAppMessage,
// drainés par le queue:work ci-dessus). Dédoublonnées → sûres à rejouer.
// Panier abandonné : toutes les heures. Onboarding pro (KYC) : une fois par jour.
Schedule::command('whatsapp:cart-reminders')->hourly()->withoutOverlapping();
Schedule::command('whatsapp:kyc-reminders')->dailyAt('09:00')->withoutOverlapping();

// Phase 4 — diffusion des nouvelles cartes Gabon sur le channel WhatsApp
// (no-op si aucun channel configuré). Dédoublonnée par carte.
Schedule::command('whatsapp:announce-new-cards')->hourly()->withoutOverlapping();

// Catalogue WhatsApp Business : cartes populaires (prix réels, réévalués à
// chaque passage) + cartes Gabon actives. `retailer_id` rend l'opération
// idempotente — un rejeu met à jour au lieu de dupliquer. No-op tant que
// WHAPI_CATALOG_SYNC_ENABLED=false (compte Business requis).
Schedule::command('whatsapp:catalog-sync')->dailyAt('05:30')->withoutOverlapping();

// Formules Daywatch : leur API publie les prix et les remises, qui bougent au
// gré des promotions. Une passe quotidienne suffit — c'est un catalogue de six
// lignes, pas un flux temps réel. La commande refuse un catalogue vide, donc
// une panne côté Daywatch ne vide pas le rayon.
Schedule::command('daywatch:sync')->dailyAt('04:20')->withoutOverlapping();
