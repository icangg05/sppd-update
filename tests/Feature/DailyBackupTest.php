<?php

namespace Tests\Feature;

use App\Jobs\BackupDatabaseJob;
use App\Services\DatabaseBackupService;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Backup otomatis berjalan tiap hari, tetapi tiap minggu tetap satu file: backup harian menimpa
 * file minggu berjalan sehingga isinya selalu data terbaru. Backup yang gagal tidak merusak file
 * minggu itu yang sudah ada.
 */
class DailyBackupTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = sys_get_temp_dir() . '/daily-backup-test-' . uniqid();
        File::ensureDirectoryExists($this->storage . '/app/backups');
        $this->app->useStoragePath($this->storage);

        // Nama file memuat nama database MySQL; env test berisi ':memory:' yang ditolak validasi nama file.
        config(['database.connections.mysql.database' => 'db_sppd_test']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** Service yang dump-nya menulis teks tertentu, atau gagal setelah menulis sebagian. */
    private function service(string $content, bool $fails = false): DatabaseBackupService
    {
        return new class ($content, $fails) extends DatabaseBackupService {
            public function __construct(private string $content, private bool $fails) {}

            protected function dump(string $path): void
            {
                file_put_contents($path, $this->content);
                if ($this->fails) {
                    throw new \RuntimeException('dump gagal');
                }
            }
        };
    }

    private function backupDir(): string
    {
        return $this->storage . '/app/backups';
    }

    public function test_dijadwalkan_harian(): void
    {
        Artisan::call('schedule:list');

        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => $event instanceof CallbackEvent && $event->description === 'daily-db-backup');

        $this->assertNotNull($event, 'Jadwal daily-db-backup tidak terdaftar.');
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertNull(collect(app(Schedule::class)->events())->first(fn ($event) => $event->description === 'weekly-db-backup'));
    }

    public function test_backup_harian_menimpa_file_minggu_berjalan(): void
    {
        Carbon::setTestNow('2026-11-16 03:00'); // Senin
        $name = $this->service('senin')->create();

        Carbon::setTestNow('2026-11-21 03:00'); // Sabtu, minggu yang sama
        $this->assertSame($name, $this->service('sabtu')->create());

        $this->assertSame(['sabtu'], array_map('file_get_contents', glob($this->backupDir() . '/*.sql.gz')));
        $this->assertSame([], glob($this->backupDir() . '/*.part'));

        // Minggu berikutnya (mulai hari Minggu) memakai file baru; file minggu lalu tetap ada.
        Carbon::setTestNow('2026-11-22 03:00');
        $this->assertNotSame($name, $this->service('minggu')->create());
        $this->assertCount(2, (new DatabaseBackupService())->all());
    }

    public function test_backup_gagal_tidak_merusak_file_minggu_ini(): void
    {
        Carbon::setTestNow('2026-11-18 03:00');
        $name = $this->service('rabu')->create();

        Carbon::setTestNow('2026-11-19 03:00');
        try {
            $this->service('sebagian', fails: true)->create();
            $this->fail('Backup gagal seharusnya melempar exception.');
        } catch (\RuntimeException $e) {
            $this->assertSame('dump gagal', $e->getMessage());
        }

        $this->assertSame('rabu', file_get_contents($this->backupDir() . '/' . $name));
        $this->assertSame([], glob($this->backupDir() . '/*.part'));
    }

    public function test_job_tetap_memangkas_ke_batas_retensi(): void
    {
        config(['backup.keep' => 2]);

        foreach (['2026-11-01', '2026-11-08', '2026-11-15'] as $i => $day) {
            Carbon::setTestNow("{$day} 03:00");
            $this->service("minggu {$i}")->create();
            touch($this->backupDir() . '/' . (new DatabaseBackupService())->currentWeekFilename(), Carbon::now()->timestamp);
        }

        Carbon::setTestNow('2026-11-22 03:00');
        (new BackupDatabaseJob())->handle($this->service('terbaru'));

        $this->assertCount(2, (new DatabaseBackupService())->all());
    }
}
