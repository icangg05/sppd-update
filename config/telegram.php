<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Telegram Bot API Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk mengirim notifikasi SPPD lewat bot Telegram.
    | Buat bot & dapatkan token dari @BotFather di Telegram.
    |
    */

  'api_url' => env('TELEGRAM_API_URL', 'https://api.telegram.org'),

  'bot_token' => env('TELEGRAM_BOT_TOKEN'),

  /**
   * Username bot tanpa "@" (mis. SppdKendariBot). Dipakai untuk membangun
   * tautan verifikasi https://t.me/<username>?start=<token>.
   */
  'bot_username' => ltrim((string) env('TELEGRAM_BOT_USERNAME', ''), '@'),

  'enabled' => filter_var(env('TELEGRAM_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

  /**
   * Shared-secret untuk memvalidasi request webhook masuk. Didaftarkan lewat
   * `php artisan telegram:webhook` dan dikirim balik oleh Telegram pada header
   * `X-Telegram-Bot-Api-Secret-Token`. Jika dikosongkan, validasi dinonaktifkan
   * (mis. untuk dev lokal).
   */
  'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),

  /*
    |--------------------------------------------------------------------------
    | Cara menerima pesan masuk (verifikasi /start)
    |--------------------------------------------------------------------------
    |
    | 'webhook' : Telegram memanggil POST /webhook/telegram (butuh URL publik
    |             HTTPS). Daftarkan sekali dengan `php artisan telegram:webhook`.
    | 'polling' : Untuk localhost/dev yang tidak bisa menerima webhook. Scheduler
    |             menjalankan `telegram:poll` tiap menit (long-polling getUpdates).
    |             Gunakan bot terpisah dari produksi.
    |
    */
  'update_mode' => env('TELEGRAM_UPDATE_MODE', 'webhook'),
];
