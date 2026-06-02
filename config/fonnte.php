<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Fonnte WhatsApp API
    |--------------------------------------------------------------------------
    | Token perangkat dari https://fonnte.com/dashboard
    | Endpoint: https://api.fonnte.com/send
    */

    'token' => env('FONNTE_TOKEN', ''),

    'enabled' => env('FONNTE_ENABLED', true),

    'country_code' => env('FONNTE_COUNTRY_CODE', '62'),

    'endpoint' => 'https://api.fonnte.com/send',
];
