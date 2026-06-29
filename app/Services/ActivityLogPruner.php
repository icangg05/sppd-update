<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;

/**
 * Memangkas tabel activity_log agar tidak melebihi batas jumlah baris.
 *
 * Strategi FIFO berbasis primary key: pertahankan N baris terbaru (id terbesar),
 * hapus selebihnya secara berchunk. Efisien karena memakai index primary key
 * (tidak butuh index created_at). Bukan menghapus seluruh log.
 */
class ActivityLogPruner
{
  /**
   * @return array{total: int, max: int, kept: int, deleted: int, cutoff_id: int|null, dry_run: bool}
   */
  public function prune(?int $maxRows = null, bool $dryRun = false): array
  {
    $max   = $maxRows ?? (int) config('activity_log.pruning.max_rows', 50000);
    $chunk = max(1, (int) config('activity_log.pruning.chunk', 5000));
    $total = Activity::query()->count();

    $result = [
      'total'     => $total,
      'max'       => $max,
      'kept'      => $total,
      'deleted'   => 0,
      'cutoff_id' => null,
      'dry_run'   => $dryRun,
    ];

    if ($max <= 0 || $total <= $max) {
      return $result;
    }

    // id baris ke-$max terbaru; baris dengan id < cutoff adalah kelebihan.
    $cutoffId = Activity::query()
      ->orderByDesc('id')
      ->offset($max - 1)
      ->limit(1)
      ->value('id');

    if (! $cutoffId) {
      return $result;
    }

    $result['cutoff_id'] = (int) $cutoffId;

    if ($dryRun) {
      $result['deleted'] = Activity::query()->where('id', '<', $cutoffId)->count();
      $result['kept']    = $total - $result['deleted'];

      return $result;
    }

    $deleted = 0;
    do {
      $n = Activity::query()->where('id', '<', $cutoffId)->limit($chunk)->delete();
      $deleted += $n;
    } while ($n > 0);

    $result['deleted'] = $deleted;
    $result['kept']    = $total - $deleted;

    return $result;
  }
}
