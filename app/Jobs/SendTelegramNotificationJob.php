<?php

namespace App\Jobs;

use App\Services\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTelegramNotificationJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  /** @var int Number of retry attempts */
  public int $tries = 3;

  /** @var array<int, int> Backoff in seconds between retries */
  public array $backoff = [10, 60, 300];

  public function __construct(
    public readonly string $chatId,
    public readonly string $message,
  ) {}

  public function handle(TelegramService $telegram): void
  {
    if (empty($this->chatId)) {
      Log::warning('SendTelegramNotificationJob: no chat_id provided, skipping.');

      return;
    }

    $telegram->send($this->chatId, $this->message);
  }

  public function failed(Throwable $exception): void
  {
    Log::error('SendTelegramNotificationJob gagal setelah semua percobaan.', [
      'chat_id' => $this->chatId,
      'error' => $exception->getMessage(),
    ]);
  }
}
