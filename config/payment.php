<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    | Supported: "null_adapter", "paymob", "stripe", "fawry", "paypal"
    | Set to "null_adapter" for local development and testing.
    */
    'default' => env('PAYMENT_GATEWAY', 'null_adapter'),

    'paymob' => [
        'api_key'        => env('PAYMOB_API_KEY', ''),
        'integration_id' => env('PAYMOB_INTEGRATION_ID', ''),
        'hmac_secret'    => env('PAYMOB_HMAC_SECRET', ''),
        'base_url'       => env('PAYMOB_BASE_URL', 'https://accept.paymob.com/api'),
    ],

    'stripe' => [
        'secret_key'      => env('STRIPE_SECRET_KEY', ''),
        'webhook_secret'  => env('STRIPE_WEBHOOK_SECRET', ''),
    ],

];
