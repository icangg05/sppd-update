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

  /**
   * Shared-secret untuk memvalidasi request webhook masuk.
   * Dikirim oleh Kirim.Chat lewat header `X-Webhook-Secret` atau query string `?secret=`.
   * Jika dikosongkan, verifikasi webhook dinonaktifkan (mis. untuk dev lokal).
   */
  'webhook_secret' => env('KIRIMCHAT_WEBHOOK_SECRET'),

  /*
    |--------------------------------------------------------------------------
    | Keep-Alive (jaga akun WA Business tetap aktif)
    |--------------------------------------------------------------------------
    |
    | Kirim.Chat menonaktifkan layanan bila tidak ada aktivitas kirim minimal
    | 7 hari. Job terjadwal `wa:keepalive` mengirim pesan otomatis ke nomor
    | di bawah secara berkala agar akun tetap "aktif".
    |
    | 'frequency' menentukan jadwalnya secara dinamis:
    |   - 'everyMinute' | 'hourly' | 'daily'
    |   - atau cron expression mentah, mis. '0 8 * * *'
    | 'time' hanya dipakai saat frequency = 'daily'.
    |
    */
  'keepalive' => [
    'enabled' => filter_var(env('KEEPALIVE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'phone' => env('KEEPALIVE_PHONE', '081341770730'),
    'frequency' => env('KEEPALIVE_FREQUENCY', 'daily'),
    'time' => env('KEEPALIVE_TIME', '08:00'),
    'message' => env('KEEPALIVE_MESSAGE', 'Sistem WhatsApp SPPD tetap aktif & siap mengirim notifikasi perjalanan dinas.'),
  ],
];
