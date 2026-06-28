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
    | Kirim.Chat menonaktifkan layanan bila tidak ada aktivitas minimal 7 hari.
    | Job terjadwal `wa:keepalive` melakukan ping API ter-autentikasi secara
    | berkala agar akun tetap "aktif" — bukan mengirim pesan WhatsApp (teks bebas
    | tunduk pada window 24-jam Meta, template berbiaya).
    |
    | 'frequency' menentukan jadwalnya secara dinamis:
    |   - 'everyMinute' | 'hourly' | 'daily'
    |   - atau cron expression mentah, mis. '0 8 * * *'
    | 'time' hanya dipakai saat frequency = 'daily'.
    |
    */
  'keepalive' => [
    'enabled' => filter_var(env('KEEPALIVE_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'frequency' => env('KEEPALIVE_FREQUENCY', 'daily'),
    'time' => env('KEEPALIVE_TIME', '08:00'),
  ],
];
