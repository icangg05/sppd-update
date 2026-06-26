<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Queue Health Gate
    |--------------------------------------------------------------------------
    |
    | Saat membuat/merevisi SPPD, sistem men-dispatch notifikasi WhatsApp ke
    | queue. Jika worker queue mati, notifikasi hanya menumpuk dan approver
    | tidak pernah tahu. Gate ini menolak pembuatan SPPD bila worker terdeteksi
    | tidak berjalan.
    |
    | Deteksi memakai heartbeat: scheduler men-dispatch LogQueueHeartbeatJob
    | tiap menit; worker yang hidup memprosesnya dan menulis timestamp ke cache.
    | Worker dianggap sehat bila timestamp terakhir tidak lebih tua dari
    | 'max_staleness_seconds'.
    |
    */

  // Kill-switch keseluruhan fitur. Jika false, pembuatan SPPD tidak pernah
  // diblokir oleh status queue (dan heartbeat berkala tidak dijadwalkan).
  'enabled' => filter_var(env('QUEUE_HEALTH_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

  // Ambang basi heartbeat (detik). Heartbeat dijadwalkan tiap menit, jadi
  // default 180 dtk memberi toleransi ~3 siklus sebelum dianggap mati.
  'max_staleness_seconds' => (int) env('QUEUE_HEALTH_MAX_STALENESS', 180),
];
