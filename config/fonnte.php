<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Fonnte WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending WhatsApp notifications using Fonnte.
    |
    */

  'url' => env('FONNTE_URL', 'https://api.fonnte.com/send'),

  'token' => env('FONNTE_TOKEN'),

  'enabled' => filter_var(env('FONNTE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
