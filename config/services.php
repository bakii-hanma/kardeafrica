<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | E-Billing Payment Gateway (legacy - direct integration, deprecated)
    |--------------------------------------------------------------------------
    | Kept for backward compatibility with PaymentController::finalize().
    | New flow uses payment_backend (futursowax) which wraps E-Billing.
    */
    'ebilling' => [
        'user' => env('EBILLING_USER'),
        'key' => env('EBILLING_KEY'),
        'auth' => env('EBILLING_AUTH'),
        'api_url' => env('EBILLING_API_URL', 'https://stg.billing-easy.com/api/v1/merchant/e_bills'),
        'portal_url' => env('EBILLING_PORTAL_URL', 'https://staging.billing-easy.net/?invoice='),
    ],

    /*
    |--------------------------------------------------------------------------
    | External Payment Backend (futursowax portal)
    |--------------------------------------------------------------------------
    | Single-call wrapper around E-Billing. See:
    | https://futursowax.com/paiement/portal-docs.php
    */
    'payment_backend' => [
        'init_url'     => env('PAYMENT_BACKEND_INIT_URL', 'https://futursowax.com/paiement/portal.php'),
        'check_url'    => env('PAYMENT_BACKEND_CHECK_URL', 'https://futursowax.com/paiement/check_status.php'),
        // Endpoint de transfert/remboursement E-Billing (renvoie l'argent au payeur)
        'transfer_url' => env('PAYMENT_BACKEND_TRANSFER_URL', 'https://futursowax.com/paiement/transfer.php'),
        'callback_url' => env('PAYMENT_BACKEND_CALLBACK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | External Product/Checkout API
    |--------------------------------------------------------------------------
    */
    'product_api' => [
        'base_url' => env('PRODUCT_API_URL', 'https://srv1882929.hstgr.cloud/api/v1'),
        // Authentification sortante optionnelle (vigilance prestataires) : si le
        // fournisseur exige une clé/HMAC, la fournir ici et l'ajouter aux appels.
        'key'      => env('PRODUCT_API_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WHAPI — WhatsApp API (OTP d'inscription pro + notifications)
    |--------------------------------------------------------------------------
    | Le token reste UNIQUEMENT côté serveur (.env), jamais dans le frontend.
    | https://whapi.cloud — endpoint messages/text.
    */
    'whapi' => [
        'base_url' => env('WHAPI_BASE_URL', 'https://gate.whapi.cloud'),
        'token'    => env('WHAPI_TOKEN'),
        // Numéro WhatsApp de l'admin qui reçoit les alertes (nouveaux dossiers pro).
        'admin_number' => env('WHAPI_ADMIN_NUMBER'),
        // Numéro/groupe WhatsApp de l'agent support (escalade du bot). Fallback: admin.
        'agent_number' => env('WHAPI_AGENT_NUMBER'),
        // Numéros admin autorisés à piloter le catalogue par WhatsApp (liste séparée
        // par des virgules). Vide → repli sur admin_number.
        'admin_numbers' => env('WHAPI_ADMIN_NUMBERS'),
        // Secret partagé qui protège le webhook entrant POST /api/webhooks/whapi.
        'webhook_secret' => env('WHAPI_WEBHOOK_SECRET'),
        // ID du channel WhatsApp (newsletter) pour la diffusion (Phase 4).
        'channel_id' => env('WHAPI_CHANNEL_ID'),
        // Sync du catalogue vers WhatsApp Business (nécessite un compte Business lié).
        // Désactivé tant que le compte Business n'existe pas.
        'catalog_sync' => env('WHAPI_CATALOG_SYNC_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supervision externe (dead-man's switch)
    |--------------------------------------------------------------------------
    | URL de ping (healthchecks.io ou équivalent) que le cron queue:work
    | appelle à chaque passage réussi. Vide = supervision désactivée (H6).
    */
    'healthcheck' => [
        'queue_url' => env('HEALTHCHECK_QUEUE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Mistral AI — assistante « Kara »
    |--------------------------------------------------------------------------
    | La clé reste UNIQUEMENT côté serveur (.env), jamais dans le frontend.
    */
    'mistral' => [
        'key'      => env('MISTRAL_API_KEY'),
        'model'    => env('MISTRAL_MODEL', 'mistral-small-latest'),
        'base_url' => env('MISTRAL_BASE_URL', 'https://api.mistral.ai'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Daywatch — catalogue des formules
    |--------------------------------------------------------------------------
    | Seul produit LOCAL du catalogue : il ne vient pas d'afrikard et se
    | synchronise par `php artisan daywatch:sync`.
    */
    'daywatch' => [
        'catalog_url' => env('DAYWATCH_CATALOG_URL', 'https://api.daywatch.online/api/gift-cards/catalog'),
    ],

    /*
    |--------------------------------------------------------------------------
    | E-Billing (billing-easy) — création d'e-bill pour le flux MOBILE (C6)
    |--------------------------------------------------------------------------
    | Clé marchand UNIQUEMENT côté serveur (.env) ; le mobile appelle un endpoint
    | Laravel qui crée l'e-bill à sa place, avec un montant calculé serveur.
    */
    'ebilling' => [
        'url'         => env('EBILLING_URL', 'https://stg.billing-easy.com/api/v1/merchant/e_bills'),
        'auth'        => env('EBILLING_AUTH'),
        'portal_base' => env('EBILLING_PORTAL_BASE', 'https://staging.billing-easy.net/?invoice='),
    ],

];
