<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Duitku Merchant Code
    |--------------------------------------------------------------------------
    |
    | The merchant code provided by Duitku.
    |
    */
    'merchant_code' => env('DUITKU_MERCHANT_CODE', ''),

    /*
    |--------------------------------------------------------------------------
    | Duitku API Key
    |--------------------------------------------------------------------------
    |
    | The API key provided by Duitku.
    |
    */
    'api_key' => env('DUITKU_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | Set to true to use the sandbox environment for testing.
    |
    */
    'sandbox_mode' => env('DUITKU_SANDBOX_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Expiry Period
    |--------------------------------------------------------------------------
    |
    | The default expiry time for payment requests in minutes.
    |
    */
    'default_expiry' => env('DUITKU_DEFAULT_EXPIRY', 60),

    /*
    |--------------------------------------------------------------------------
    | Duitku Disbursement Config
    |--------------------------------------------------------------------------
    |
    | Required for Disbursement (Transfer Online) features.
    |
    */
    'user_id' => env('DUITKU_USER_ID', ''),
    'email' => env('DUITKU_EMAIL', ''),
];
