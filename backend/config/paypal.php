<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PayPal API Credentials & Configuration
    |--------------------------------------------------------------------------
    |
    | Supported modes: 'sandbox' or 'live'.
    | Default URLs point to PayPal Sandbox REST endpoints for hermetic testing.
    |
    */

    'mode'          => env('PAYPAL_MODE', 'sandbox'),
    'client_id'     => env('PAYPAL_CLIENT_ID', 'mock_paypal_client_id'),
    'client_secret' => env('PAYPAL_CLIENT_SECRET', 'mock_paypal_client_secret'),
    'webhook_id'    => env('PAYPAL_WEBHOOK_ID', 'mock_paypal_webhook_id'),

    'base_url' => env('PAYPAL_MODE', 'sandbox') === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com',

    /*
    |--------------------------------------------------------------------------
    | Token Cache Settings
    |--------------------------------------------------------------------------
    */
    'token_cache_key' => 'paypal_oauth_access_token',
];
