<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS') === '*'
        ? ['*']
        : explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000') === '*'
        ? ['*']
        : explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With') === '*'
        ? ['*']
        : explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With')),

    'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', 'X-Token-Refreshed,X-New-Access-Token')),

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),
];
