<?php

use App\Jobs\BackupDatabaseJob;
use App\Jobs\LogQueueHeartbeatJob;
use App\Jobs\PruneActivityLogsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Keep-Alive WhatsApp (jaga akun Kirim.Chat tetap aktif)
|--------------------------------------------------------------------------
|
| Jadwal dinamis dikontrol lewat config('kirimchat.keepalive.*'):
| frequency = everyMinute | hourly | daily | cron expression mentah.
|
*/
if (config('kirimchat.keepalive.enabled')) {
    $event = Schedule::command('wa:keepalive')->withoutOverlapping();

    match (config('kirimchat.keepalive.frequency', 'daily')) {
        'everyMinute' => $event->everyMinute(),
        'hourly' => $event->hourly(),
        'daily' => $event->dailyAt((string) config('kirimchat.keepalive.time', '08:00')),
        default => $event->cron((string) config('kirimchat.keepalive.frequency')),
    };
}

/*
|--------------------------------------------------------------------------
| Queue Heartbeat (deteksi worker queue hidup/mati)
|--------------------------------------------------------------------------
|
| Tiap menit men-dispatch job heartbeat dengan token segar. Worker yang
| hidup memprosesnya dan menulis timestamp ke cache. QueueHealthService
| membaca timestamp itu untuk memutuskan apakah pembuatan SPPD boleh lanjut.
|
*/
if (config('queue_health.enabled')) {
    Schedule::call(fn () => LogQueueHeartbeatJob::dispatch(
        (string) Str::uuid(),
        now()->toDateTimeString(),
    ))->name('queue-heartbeat')->everyMinute()->withoutOverlapping();
}

/*
|--------------------------------------------------------------------------
| Backup Database Mingguan
|--------------------------------------------------------------------------
|
| Men-dispatch BackupDatabaseJob ke queue agar proses backup berjalan di worker
| (bukan di scheduler). Hari & jam diatur lewat config('backup.*') (BACKUP_DAY,
| BACKUP_TIME) — default Minggu 03:00 agar tidak bertabrakan dengan keepalive
| Kirim.Chat (06:00). Job menyimpan satu file per minggu & menjaga retensi
| sebanyak BACKUP_KEEP backup terbaru.
|
*/
Schedule::job(new BackupDatabaseJob())
    ->weeklyOn((int) config('backup.day', 0), (string) config('backup.time', '03:00'))
    ->name('weekly-db-backup')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Pemangkasan activity_log Mingguan
|--------------------------------------------------------------------------
|
| Men-dispatch PruneActivityLogsJob ke queue agar pemangkasan berjalan di worker.
| Menjaga tabel activity_log tidak melebihi config('activity_log.pruning.max_rows')
| (env ACTIVITY_LOG_MAX_ROWS) dengan menghapus baris terlama — bukan seluruh log.
| Default Minggu 04:00 agar tidak bertabrakan dengan backup (03:00).
|
*/
if (config('activity_log.pruning.enabled')) {
    // Toleransi salah ketik pemisah jam (16.07 -> 16:07) agar tidak diam-diam jadi 16:00.
    $pruneTime = str_replace('.', ':', (string) config('activity_log.pruning.time', '04:00'));

    Schedule::job(new PruneActivityLogsJob())
        ->weeklyOn((int) config('activity_log.pruning.day', 0), $pruneTime)
        ->name('weekly-activity-log-prune')
        ->withoutOverlapping();
}
