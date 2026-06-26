<?php

namespace App\Services;

use App\Jobs\LogQueueHeartbeatJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Menentukan apakah worker queue benar-benar berjalan, berdasarkan heartbeat
 * yang dijadwalkan tiap menit (lihat routes/console.php) dan diproses worker
 * (LogQueueHeartbeatJob menulis PROCESSED_KEY ke cache store "database").
 *
 * Dipakai sebagai gerbang sebelum membuat/merevisi SPPD: jika worker mati,
 * notifikasi WhatsApp ke approver tidak akan terkirim, jadi pengajuan ditolak.
 */
class QueueHealthService
{
  public function __construct(
    private readonly int $maxStalenessSeconds = 0,
  ) {}

  /**
   * Worker dianggap sehat bila heartbeat terakhir diproses belum melewati
   * ambang basi. Bypass otomatis saat queue 'sync' (job jalan inline) atau
   * fitur dimatikan.
   */
  public function isWorkerHealthy(): bool
  {
    if (config('queue.default') === 'sync') {
      return true;
    }

    if (! config('queue_health.enabled')) {
      return true;
    }

    $processedAt = $this->lastProcessedAt();
    if ($processedAt === null) {
      return false;
    }

    return abs(CarbonImmutable::parse($processedAt)->diffInSeconds(now())) <= $this->maxStaleness();
  }

  /**
   * Timestamp (Y-m-d H:i:s) saat worker terakhir memproses heartbeat, atau null
   * bila belum pernah / marker hilang.
   */
  public function lastProcessedAt(): ?string
  {
    $processed = Cache::store('database')->get(LogQueueHeartbeatJob::PROCESSED_KEY);

    return $processed['processed_at'] ?? null;
  }

  private function maxStaleness(): int
  {
    return $this->maxStalenessSeconds > 0
      ? $this->maxStalenessSeconds
      : (int) config('queue_health.max_staleness_seconds', 180);
  }
}
