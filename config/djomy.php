<?php

return [
    'client_id' => env('DJOMY_CLIENT_ID'),
    'client_secret' => env('DJOMY_CLIENT_SECRET'),
    'sandbox' => env('DJOMY_SANDBOX', true),
    'sandbox_url' => env('DJOMY_SANDBOX_URL', 'https://sandbox-api.djomy.africa'),
    'production_url' => env('DJOMY_PRODUCTION_URL', 'https://api.djomy.africa'),
    'country_code' => env('DJOMY_COUNTRY_CODE', 'GN'),
    'currency' => env('DJOMY_CURRENCY', 'GNF'),
    'webhook_secret' => env('DJOMY_WEBHOOK_SECRET'),
    'webhook_url' => env('DJOMY_WEBHOOK_URL', '/api/v1/payments/djomy/webhook'),
];
