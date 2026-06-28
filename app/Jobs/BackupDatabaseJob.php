<?php

namespace App\Jobs;

use App\Services\DatabaseBackupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Membuat backup database di latar belakang (diproses worker queue), lalu
 * memangkas backup lama sehingga hanya tersisa sejumlah file terbaru sesuai
 * config('backup.keep') (1 per minggu).
 *
 * Dipicu oleh: jadwal mingguan (hari & jam via config('backup.*'), lihat
 * routes/console.php) atau tombol manual di halaman Backup Database.
 */
class BackupDatabaseJob implements ShouldQueue, ShouldBeUnique
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /** Batas waktu eksekusi job (detik) — cukup untuk DB besar. */
  public int $timeout = 600;

  /** Cegah backup dobel: kunci unik berlaku selama proses (maks 30 menit). */
  public int $uniqueFor = 1800;

  public function uniqueId(): string
  {
    return 'backup-database';
  }

  /**
   * Pakai cache store "database" agar kunci unik terbaca lintas-container
   * (dispatch dari app/scheduler, diproses worker) — file cache tidak di-share.
   */
  public function uniqueVia(): CacheRepository
  {
    return Cache::store('database');
  }

  public function handle(DatabaseBackupService $service): void
  {
    $filename = $service->create();
    $pruned = $service->prune();

    Log::info('Backup database selesai.', [
      'file' => $filename,
      'pruned' => $pruned,
    ]);
  }
}
