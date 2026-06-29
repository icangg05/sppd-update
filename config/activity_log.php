<?php

return [
  /*
  |----------------------------------------------------------------------
  | Pemangkasan (pruning) activity_log berbasis jumlah baris
  |----------------------------------------------------------------------
  |
  | Worker mingguan menjaga tabel activity_log tidak melebihi 'max_rows'.
  | Bila melebihi, baris TERLAMA (id terkecil) dihapus hingga kembali ke
  | batas — bukan menghapus seluruh log. Dijadwalkan di routes/console.php.
  |
  */
  'pruning' => [
    'enabled'  => env('ACTIVITY_LOG_PRUNE_ENABLED', true),
    'max_rows' => (int) env('ACTIVITY_LOG_MAX_ROWS', 50000),
    'day'      => (int) env('ACTIVITY_LOG_PRUNE_DAY', 0),   // 0 = Minggu
    'time'     => env('ACTIVITY_LOG_PRUNE_TIME', '04:00'),  // hindari tabrakan backup 03:00
    'chunk'    => (int) env('ACTIVITY_LOG_PRUNE_CHUNK', 5000),
  ],
];
