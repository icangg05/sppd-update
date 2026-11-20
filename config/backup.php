<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Backup Database Harian (satu file per minggu)
    |--------------------------------------------------------------------------
    |
    | Backup berjalan tiap hari, tetapi tiap minggu hanya ada satu file: backup
    | harian menimpa file minggu berjalan sehingga isinya selalu data terbaru.
    | Jadwal di routes/console.php (BackupDatabaseJob), nama file per minggu &
    | retensi di App\Services\DatabaseBackupService. BACKUP_DAY tidak dipakai lagi.
    |
    */

  // Jam backup harian, format 24 jam 'HH:MM' (zona waktu mengikuti config('app.timezone')).
  'time' => env('BACKUP_TIME', '03:00'),

  // Jumlah backup terbaru yang disimpan (1 per minggu). Selebihnya dipangkas.
  'keep' => (int) env('BACKUP_KEEP', 8),
];
