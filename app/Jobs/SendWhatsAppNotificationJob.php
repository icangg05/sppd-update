<?php

namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotificationJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /** @var int Number of retry attempts */
  public int $tries = 3;

  /** @var array<int, int> Backoff in seconds between retries */
  public array $backoff = [10, 60, 300];

  public function __construct(
    public readonly string $phone,
    public readonly string $message,
  ) {}

  public function handle(FonnteService $fonnte): void
  {
    if (empty($this->phone)) {
      Log::warning('SendWhatsAppNotificationJob: no phone number provided, skipping.');

      return;
    }

    $fonnte->send($this->phone, $this->message);
  }

  public function failed(Throwable $exception): void
  {
    Log::error('SendWhatsAppNotificationJob gagal setelah semua percobaan.', [
      'phone' => $this->phone,
      'error' => $exception->getMessage(),
    ]);
  }
}
