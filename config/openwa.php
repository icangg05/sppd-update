<?php

return [
  /*
    |--------------------------------------------------------------------------
    | OpenWA WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for sending WhatsApp notifications using OpenWA Gateway.
    |
    */

  'url' => env('OPENWA_URL', 'http://localhost:2785'),

  'key' => env('OPENWA_API_KEY', 'dev-admin-key'),

  'session_id' => env('OPENWA_SESSION_ID', '5626c952-f0ec-4105-9a66-dac48ed4c04a'),

  'enabled' => filter_var(env('OPENWA_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
