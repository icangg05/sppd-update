<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Backup Database Mingguan
    |--------------------------------------------------------------------------
    |
    | Jadwal & retensi backup database otomatis. Lihat penjadwalannya di
    | routes/console.php (BackupDatabaseJob) dan retensinya di
    | App\Services\DatabaseBackupService::prune().
    |
    */

  // Hari backup mingguan (0 = Minggu, 1 = Senin, ... 6 = Sabtu).
  'day' => (int) env('BACKUP_DAY', 0),

  // Jam backup, format 24 jam 'HH:MM' (zona waktu mengikuti config('app.timezone')).
  'time' => env('BACKUP_TIME', '03:00'),

  // Jumlah backup terbaru yang disimpan (1 per minggu). Selebihnya dipangkas.
  'keep' => (int) env('BACKUP_KEEP', 8),
];
