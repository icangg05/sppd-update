<?php

namespace Tests\Feature;

use App\Jobs\LogQueueHeartbeatJob;
use App\Services\QueueHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    private function putHeartbeat(string $processedAt): void
    {
        Cache::store('database')->put(LogQueueHeartbeatJob::PROCESSED_KEY, [
            'token' => 'tok',
            'dispatched_at' => $processedAt,
            'processed_at' => $processedAt,
        ], now()->addDays(7));
    }

    public function test_healthy_when_queue_is_sync(): void
    {
        config(['queue.default' => 'sync', 'queue_health.enabled' => true]);

        // Tidak ada heartbeat sama sekali, tetap sehat karena job jalan inline.
        $this->assertTrue(app(QueueHealthService::class)->isWorkerHealthy());
    }

    public function test_healthy_when_feature_disabled(): void
    {
        config(['queue.default' => 'database', 'queue_health.enabled' => false]);

        $this->assertTrue(app(QueueHealthService::class)->isWorkerHealthy());
    }

    public function test_unhealthy_when_no_heartbeat_recorded(): void
    {
        config(['queue.default' => 'database', 'queue_health.enabled' => true]);

        $this->assertFalse(app(QueueHealthService::class)->isWorkerHealthy());
    }

    public function test_healthy_when_heartbeat_is_fresh(): void
    {
        config([
            'queue.default' => 'database',
            'queue_health.enabled' => true,
            'queue_health.max_staleness_seconds' => 180,
        ]);

        $this->putHeartbeat(now()->subSeconds(30)->toDateTimeString());

        $this->assertTrue(app(QueueHealthService::class)->isWorkerHealthy());
    }

    public function test_unhealthy_when_heartbeat_is_stale(): void
    {
        config([
            'queue.default' => 'database',
            'queue_health.enabled' => true,
            'queue_health.max_staleness_seconds' => 180,
        ]);

        $this->putHeartbeat(now()->subSeconds(600)->toDateTimeString());

        $this->assertFalse(app(QueueHealthService::class)->isWorkerHealthy());
    }
}
