<?php
return [
    'orange_money' => [
        'merchant_id' => env('OM_MERCHANT_ID'),
        'api_key' => env('OM_API_KEY'),
        'api_url' => env('OM_API_URL', 'https://api.orange-money.com'),
        'webhook_secret' => env('OM_WEBHOOK_SECRET'),
    ],
    'mtn_momo' => [
        'merchant_id' => env('MOMO_MERCHANT_ID'),
        'api_key' => env('MOMO_API_KEY'),
        'api_user' => env('MOMO_API_USER'),
        'api_url' => env('MOMO_API_URL', 'https://api.mtn-momo.com'),
        'webhook_secret' => env('MOMO_WEBHOOK_SECRET'),
    ],
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'currency' => env('PAYMENT_CURRENCY', 'GNF'),
];
