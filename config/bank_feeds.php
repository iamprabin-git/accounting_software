<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bank statement feed providers (APIs)
    |--------------------------------------------------------------------------
    |
    | Plaid, TrueLayer, and similar services require OAuth, webhooks, and
    | compliance steps. This app ships with hooks only: enable a provider in
    | .env when you integrate the SDK and map transactions into CSV-compatible
    | rows for the importer.
    |
    */

    'plaid' => [
        'label' => 'Plaid',
        'enabled' => (bool) env('PLAID_ENABLED', false),
        'client_id' => env('PLAID_CLIENT_ID'),
        'secret' => env('PLAID_SECRET'),
        'environment' => env('PLAID_ENV', 'sandbox'),
    ],

    'truelayer' => [
        'label' => 'TrueLayer',
        'enabled' => (bool) env('TRUELAYER_ENABLED', false),
        'client_id' => env('TRUELAYER_CLIENT_ID'),
        'client_secret' => env('TRUELAYER_CLIENT_SECRET'),
    ],

];
