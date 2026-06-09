<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Kirim.Chat WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk mengirim notifikasi WhatsApp menggunakan Kirim.Chat.
    | Dapatkan API key dari dashboard kirim.chat.
    |
    */

  'base_url' => env('KIRIMCHAT_BASE_URL', 'https://api-prod.kirim.chat/api/v1/public'),

  'api_key' => env('KIRIMCHAT_API_KEY'),

  'enabled' => filter_var(env('KIRIMCHAT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

  /**
   * Nomor admin/operator untuk proses verifikasi nomor WhatsApp pengguna.
   * User mengirim pesan ke nomor ini sebagai langkah verifikasi.
   */
  'verification_number' => env('KIRIMCHAT_VERIFICATION_NUMBER', '628217611919'),
];
