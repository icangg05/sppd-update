<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Services\OpenWAService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OpenWANotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['openwa.url' => 'http://openwa-api:2785']);
        config(['openwa.key' => 'test-key']);
        config(['openwa.session_id' => '5626c952-f0ec-4105-9a66-dac48ed4c04a']);
        config(['openwa.enabled' => true]);
    }

    public function test_openwa_service_normalizes_and_sends_whatsapp(): void
    {
        Http::fake([
            'http://openwa-api:2785/api/sessions/5626c952-f0ec-4105-9a66-dac48ed4c04a/messages/send-text' => Http::response(['status' => 'success'], 200),
        ]);

        $service = app(OpenWAService::class);

        // Test leading 0 conversion
        $result = $service->send('081234567890', 'Hello World');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://openwa-api:2785/api/sessions/5626c952-f0ec-4105-9a66-dac48ed4c04a/messages/send-text' &&
                $request['chatId'] === '6281234567890@c.us' &&
                $request['text'] === 'Hello World' &&
                $request->hasHeader('X-API-Key', 'test-key');
        });
    }

    public function test_openwa_service_normalizes_8xx_correctly(): void
    {
        Http::fake([
            'http://openwa-api:2785/api/sessions/5626c952-f0ec-4105-9a66-dac48ed4c04a/messages/send-text' => Http::response(['status' => 'success'], 200),
        ]);

        $service = app(OpenWAService::class);

        // Test prepending 62 to 8xx
        $result = $service->send('81234567890', 'Test Message');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['chatId'] === '6281234567890@c.us' &&
                $request['text'] === 'Test Message';
        });
    }

    public function test_openwa_service_accepts_already_normalized_chat_id(): void
    {
        Http::fake([
            'http://openwa-api:2785/api/sessions/5626c952-f0ec-4105-9a66-dac48ed4c04a/messages/send-text' => Http::response(['status' => 'success'], 200),
        ]);

        $service = app(OpenWAService::class);

        // Test with full chatId
        $result = $service->send('6281234567890@c.us', 'Hello World');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['chatId'] === '6281234567890@c.us';
        });
    }

    public function test_openwa_service_returns_false_if_disabled(): void
    {
        config(['openwa.enabled' => false]);

        $service = app(OpenWAService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_openwa_service_returns_false_if_no_url(): void
    {
        config(['openwa.url' => '']);

        $service = app(OpenWAService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_openwa_service_returns_false_if_no_session_id(): void
    {
        config(['openwa.session_id' => '']);

        $service = app(OpenWAService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_whatsapp_notification_job_is_dispatched(): void
    {
        Queue::fake();

        SendWhatsAppNotificationJob::dispatch('081234567890', 'Hello World');

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) {
            return $job->phone === '081234567890' && $job->message === 'Hello World';
        });
    }

    public function test_whatsapp_notification_job_calls_service(): void
    {
        Http::fake([
            'http://openwa-api:2785/api/sessions/5626c952-f0ec-4105-9a66-dac48ed4c04a/messages/send-text' => Http::response(['status' => 'success'], 200),
        ]);

        $job = new SendWhatsAppNotificationJob('081234567890', 'Job Message');
        $job->handle(app(OpenWAService::class));

        Http::assertSent(function ($request) {
            return $request['chatId'] === '6281234567890@c.us' &&
                $request['text'] === 'Job Message';
        });
    }
}
