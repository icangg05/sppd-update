<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class KeepAliveCommandTest extends TestCase
{
    public function test_keepalive_command_dispatches_job_to_configured_phone(): void
    {
        Queue::fake();

        config(['kirimchat.keepalive.enabled' => true]);
        config(['kirimchat.keepalive.phone' => '081341770730']);
        config(['kirimchat.keepalive.message' => 'Ping keep-alive layanan SPPD.']);

        $this->artisan('wa:keepalive')->assertExitCode(0);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) {
            return $job->phone === '081341770730'
                && str_contains($job->message, 'Ping keep-alive layanan SPPD.');
        });
    }

    public function test_keepalive_command_skips_when_disabled(): void
    {
        Queue::fake();

        config(['kirimchat.keepalive.enabled' => false]);

        $this->artisan('wa:keepalive')->assertExitCode(0);

        Queue::assertNotPushed(SendWhatsAppNotificationJob::class);
    }
}
