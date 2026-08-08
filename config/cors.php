<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // M17/M2 — avec supports_credentials, on n'autorise QUE le HTTPS de prod.
    // Les origines localhost (dev) ne sont ajoutées qu'en environnement local.
    'allowed_origins' => array_values(array_filter([
        'https://kardafrica.com',
        'https://www.kardafrica.com',
        env('APP_ENV') === 'local' ? 'http://localhost:3000' : null,
        env('APP_ENV') === 'local' ? 'http://localhost:8000' : null,
        env('APP_ENV') === 'local' ? 'http://localhost:8081' : null,
    ])),

    // HTTPS uniquement + sous-domaine simple (plus de `https?` ni `.*`).
    'allowed_origins_patterns' => [
        '#^https://([a-z0-9-]+\.)?kardafrica\.com$#',
    ],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-CSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
