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
    ],

    /*
    |--------------------------------------------------------------------------
    | Mistral AI — assistante « Kara »
    |--------------------------------------------------------------------------
    | La clé reste UNIQUEMENT côté serveur (.env), jamais dans le frontend.
    */
    'mistral' => [
        'key'   => env('MISTRAL_API_KEY'),
        'model' => env('MISTRAL_MODEL', 'mistral-small-latest'),
    ],

];
