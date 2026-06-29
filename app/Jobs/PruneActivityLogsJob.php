<?php

namespace App\Jobs;

use App\Services\ActivityLogPruner;
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
 * Memangkas tabel activity_log di latar belakang (worker queue) agar jumlah
 * baris tidak melebihi config('activity_log.pruning.max_rows'). Baris terlama
 * dihapus hingga kembali ke batas — bukan menghapus seluruh log.
 *
 * Dipicu oleh jadwal mingguan di routes/console.php.
 */
class PruneActivityLogsJob implements ShouldQueue, ShouldBeUnique
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /** Batas waktu eksekusi job (detik). */
  public int $timeout = 600;

  /** Cegah pemangkasan dobel: kunci unik berlaku selama proses (maks 10 menit). */
  public int $uniqueFor = 600;

  public function uniqueId(): string
  {
    return 'prune-activity-logs';
  }

  /**
   * Pakai cache store "database" agar kunci unik terbaca lintas-container
   * (dispatch dari scheduler, diproses worker) — file cache tidak di-share.
   */
  public function uniqueVia(): CacheRepository
  {
    return Cache::store('database');
  }

  public function handle(ActivityLogPruner $pruner): void
  {
    if (! config('activity_log.pruning.enabled', true)) {
      return;
    }

    $result = $pruner->prune();

    Log::info('Pemangkasan activity_log selesai.', $result);
  }
}
