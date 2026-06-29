<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tanda Tangan Elektronik (TTE) Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk sistem penandatanganan elektronik (TTE) SPPD
    |
    */

  'default_provider' => env('E_SIGN_PROVIDER', 'local_proxy'),

  'providers' => [
    'local_proxy' => [
      'name'       => 'Local Proxy',
      'driver'     => 'local_proxy',
      'endpoint'   => env('E_SIGN_API_ENDPOINT', 'http://103.85.5.99'),
      'basic_auth' => env('E_SIGN_AUTH_BASIC', 'ZXNwcGQ6TUEzNDVnRmJCR0hUTWRkeDdlVXI='),
      'timeout'    => env('E_SIGN_TIMEOUT', 90),
    ],

    'bssn' => [
      'name'          => 'BSSN e-Sign',
      'driver'        => 'bssn',
      'endpoint'      => env('E_SIGN_BSSN_ENDPOINT', 'https://esign-bsre.bssn.go.id'),
      'client_id'     => env('E_SIGN_BSSN_CLIENT_ID'),
      'client_secret' => env('E_SIGN_BSSN_CLIENT_SECRET'),
      'timeout'       => env('E_SIGN_TIMEOUT', 90),
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Verifikasi Dokumen ber-TTE (keaslian PAdES via BSrE)
  |--------------------------------------------------------------------------
  | Verifikasi dilakukan dengan mengunggah PDF ke endpoint verify lalu BSrE
  | memeriksa keaslian tanda tangannya. Berkas dikirim multipart field
  | 'signed_file'. Mendukung tiga mode auth:
  |   - 'basic'  : lewat proxy lokal /api/sign/verify (default; reuse kredensial signing)
  |   - 'bearer' : token statis (E_SIGN_VERIFY_TOKEN), langsung ke BSrE
  |   - 'oauth'  : client_credentials (E_SIGN_VERIFY_CLIENT_ID/SECRET), langsung ke BSrE
  | Jika nonaktif / kredensial kosong, halaman cek dokumen menampilkan pesan
  | bahwa verifikasi belum aktif.
  */
  'verify' => [
    'enabled'        => env('TTE_VERIFY_BSRE_ENABLED', true),
    'auth'           => env('E_SIGN_VERIFY_AUTH', 'basic'),
    // Default lewat proxy yang sama dengan signing: {endpoint}/api/sign/verify
    'endpoint'       => env('E_SIGN_VERIFY_ENDPOINT', rtrim(env('E_SIGN_API_ENDPOINT', 'http://103.85.5.99'), '/') . '/api/sign/verify'),
    // Mode 'basic' — default sama dengan Basic auth signing (E_SIGN_AUTH_BASIC).
    'basic_auth'     => env('E_SIGN_VERIFY_BASIC', env('E_SIGN_AUTH_BASIC', 'ZXNwcGQ6TUEzNDVnRmJCR0hUTWRkeDdlVXI=')),
    // Mode 'bearer' / 'oauth' — opsional, bila verify langsung ke BSrE.
    'token'          => env('E_SIGN_VERIFY_TOKEN'),
    'oauth_endpoint' => env('E_SIGN_VERIFY_OAUTH_ENDPOINT', 'https://esign-bsre.bssn.go.id/oauth/token'),
    'client_id'      => env('E_SIGN_VERIFY_CLIENT_ID'),
    'client_secret'  => env('E_SIGN_VERIFY_CLIENT_SECRET'),
    'timeout'        => (int) env('E_SIGN_VERIFY_TIMEOUT', 30),
  ],

  'storage' => [
    'disk' => env('PDF_STORAGE_DISK', 'public'),

    'paths' => [
      'draft'      => 'doc_dummy',              // Draft PDF sebelum sign
      'signed'     => date('Y') . '/doc_tte',   // PDF sudah di-sign
      'signatures' => 'tanda_tangan',           // Gambar tanda tangan
      'qr_codes'   => 'qr_codes',               // QR code files
    ],
  ],

  'queue' => [
    'connection'     => env('QUEUE_CONNECTION', 'database'),
    'driver'         => 'database',
    'retry_attempts' => env('TTE_RETRY_ATTEMPTS', 3),
    'retry_delay'    => env('TTE_RETRY_DELAY', 300),           // detik
  ],

  'pdf' => [
    'format'        => 'A4',
    'margin_top'    => 10,
    'margin_bottom' => 10,
    'margin_left'   => 10,
    'margin_right'  => 10,
    'font_size'     => 10,
    'font_family'   => 'Arial',
  ],

  'signature' => [
    'qr_code_enabled'      => env('TTE_QR_CODE_ENABLED', true),
    'qr_code_size'         => 15,                                 // mm
    'signature_box_width'  => 50,                                 // mm
    'signature_box_height' => 20,                                 // mm
    'visible_signature'    => true,                               // Tampilkan tanda tangan di PDF
  ],

  'logging' => [
    'enabled' => env('TTE_LOGGING_ENABLED', true),
    'channel' => 'single',
    'level'   => env('TTE_LOG_LEVEL', 'info'),
  ],
];
