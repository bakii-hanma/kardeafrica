<?php

/*
|--------------------------------------------------------------------------
| Relances WhatsApp programmées (Phase 3)
|--------------------------------------------------------------------------
| Fenêtres de temps des envois automatiques. Chaque relance est envoyée UNE
| fois par cible (dédoublonnage via whatsapp_messages.dedup_key). Les commandes
| associées : `whatsapp:cart-reminders` et `whatsapp:kyc-reminders`.
*/

return [

    'reminders' => [

        // Panier abandonné : commande créée mais non payée.
        'cart' => [
            'enabled'         => env('WA_REMINDER_CART_ENABLED', true),
            // On attend au moins ce délai avant de relancer (laisser finir un paiement en cours)…
            'min_age_minutes' => (int) env('WA_REMINDER_CART_MIN_MINUTES', 120),
            // …et on ne relance jamais une commande trop vieille (nuisance).
            'max_age_hours'   => (int) env('WA_REMINDER_CART_MAX_HOURS', 48),
        ],

        // Onboarding pro bloqué : dossier à compléter ou pièces réclamées.
        'kyc' => [
            'enabled'       => env('WA_REMINDER_KYC_ENABLED', true),
            'min_age_hours' => (int) env('WA_REMINDER_KYC_MIN_HOURS', 24),
            'max_age_days'  => (int) env('WA_REMINDER_KYC_MAX_DAYS', 14),
        ],

    ],

];
