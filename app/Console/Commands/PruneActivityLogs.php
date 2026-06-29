<?php

namespace App\Console\Commands;

use App\Services\ActivityLogPruner;
use Illuminate\Console\Command;

/**
 * Memangkas tabel activity_log agar tidak melebihi batas jumlah baris.
 *
 * Batas default diambil dari config('activity_log.pruning.max_rows')
 * (env ACTIVITY_LOG_MAX_ROWS). Baris terlama dihapus hingga kembali ke batas.
 * Dipakai untuk menjalankan/menguji pemangkasan secara manual; versi terjadwal
 * berjalan otomatis tiap minggu lewat PruneActivityLogsJob (routes/console.php).
 */
class PruneActivityLogs extends Command
{
  protected $signature = 'logs:prune {--max= : Batas maksimum baris (override config)} {--dry-run : Hitung saja, tanpa menghapus}';

  protected $description = 'Pangkas activity_log agar tidak melebihi batas jumlah baris (hapus yang terlama)';

  public function handle(ActivityLogPruner $pruner): int
  {
    $max = $this->option('max') !== null ? (int) $this->option('max') : null;
    $dryRun = (bool) $this->option('dry-run');

    $result = $pruner->prune($max, $dryRun);

    $this->table(
      ['Total', 'Batas', 'Disisakan', $dryRun ? 'Akan dihapus' : 'Dihapus', 'Cutoff ID'],
      [[
        $result['total'],
        $result['max'],
        $result['kept'],
        $result['deleted'],
        $result['cutoff_id'] ?? '-',
      ]],
    );

    if ($dryRun) {
      $this->info('Dry-run: tidak ada data yang dihapus.');
    } else {
      $this->info("Selesai. {$result['deleted']} baris activity_log dihapus.");
    }

    return self::SUCCESS;
  }
}
