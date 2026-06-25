<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Job diagnostik "berdetak": hanya menulis log lalu menandai waktu eksekusi
 * (heartbeat) supaya route /system/health bisa memastikan worker queue benar-benar
 * memproses job.
 *
 * Marker ditulis ke cache store "database" (bukan default "file") agar bisa dibaca
 * lintas-container — job diproses di container `queue`, sedangkan marker dibaca oleh
 * route di container `app`. File cache tidak di-share antar keduanya di produksi.
 */
class LogQueueHeartbeatJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /** Kunci cache marker saat job dititipkan oleh route. */
  public const DISPATCHED_KEY = 'queue_heartbeat:dispatched';

  /** Kunci cache marker saat job benar-benar diproses worker. */
  public const PROCESSED_KEY = 'queue_heartbeat:processed';

  public function __construct(
    public readonly string $token,
    public readonly string $dispatchedAt,
  ) {}

  public function handle(): void
  {
    Log::info('Queue heartbeat: job diproses worker.', [
      'token' => $this->token,
      'dispatched_at' => $this->dispatchedAt,
    ]);

    Cache::store('database')->put(self::PROCESSED_KEY, [
      'token' => $this->token,
      'dispatched_at' => $this->dispatchedAt,
      'processed_at' => now()->toDateTimeString(),
    ], now()->addDays(7));
  }
}
