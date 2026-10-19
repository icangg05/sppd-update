<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KeepAliveCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['kirimchat.base_url' => 'https://api-prod.kirim.chat/api/v1/public']);
        config(['kirimchat.api_key' => 'test-key']);
    }

    public function test_keepalive_command_pings_activity_endpoint(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/health' => Http::response([
                'success' => true,
                'data' => ['status' => 'ok', 'authenticated' => true],
            ], 200),
        ]);

        config(['kirimchat.keepalive.enabled' => true]);

        $this->artisan('wa:keepalive')->assertExitCode(0);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-prod.kirim.chat/api/v1/public/health'
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_keepalive_command_still_succeeds_when_ping_fails(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/health' => Http::response(['success' => false], 401),
        ]);

        config(['kirimchat.keepalive.enabled' => true]);

        $this->artisan('wa:keepalive')->assertExitCode(0);

        Http::assertSentCount(1);
    }

    public function test_keepalive_command_skips_when_disabled(): void
    {
        Http::fake();

        config(['kirimchat.keepalive.enabled' => false]);

        $this->artisan('wa:keepalive')->assertExitCode(0);

        Http::assertNothingSent();
    }
}
