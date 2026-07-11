<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int Number of retry attempts */
    public int $tries = 3;

    /** @var array<int, int> Backoff in seconds between retries */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $body,
        public readonly string $url,
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $user = User::find($this->userId);
        if (!$user) {
            Log::warning("SendPushNotificationJob: User ID {$this->userId} not found, skipping.");
            return;
        }

        $webPush->sendToUser($user, $this->title, $this->body, $this->url);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendPushNotificationJob failed after all attempts.', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
