<?php

use App\Jobs\BackupDatabaseJob;
use App\Jobs\LogQueueHeartbeatJob;
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
