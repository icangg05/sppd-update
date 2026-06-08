<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FonnteNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['fonnte.url' => 'https://api.fonnte.com/send']);
        config(['fonnte.token' => 'test-token']);
        config(['fonnte.enabled' => true]);
    }

    public function test_fonnte_service_normalizes_and_sends_whatsapp(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $service = app(FonnteService::class);

        // Test leading 0 conversion
        $result = $service->send('081234567890', 'Hello World');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.com/send' &&
                $request['target'] === '6281234567890' &&
                $request['message'] === 'Hello World' &&
                $request->hasHeader('Authorization', 'test-token');
        });
    }

    public function test_fonnte_service_normalizes_8xx_correctly(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $service = app(FonnteService::class);

        // Test prepending 62 to 8xx
        $result = $service->send('81234567890', 'Test Message');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['target'] === '6281234567890' &&
                $request['message'] === 'Test Message';
        });
    }

    public function test_fonnte_service_accepts_already_normalized_chat_id(): void
    {
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $service = app(FonnteService::class);

        // Test with chat ID format
        $result = $service->send('6281234567890@c.us', 'Hello World');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['target'] === '6281234567890';
        });
    }

    public function test_fonnte_service_returns_false_if_disabled(): void
    {
        config(['fonnte.enabled' => false]);

        $service = app(FonnteService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_fonnte_service_returns_false_if_no_url(): void
    {
        config(['fonnte.url' => '']);

        $service = app(FonnteService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_fonnte_service_returns_false_if_no_token(): void
    {
        config(['fonnte.token' => '']);

        $service = app(FonnteService::class);
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
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        $job = new SendWhatsAppNotificationJob('081234567890', 'Job Message');
        $job->handle(app(FonnteService::class));

        Http::assertSent(function ($request) {
            return $request['target'] === '6281234567890' &&
                $request['message'] === 'Job Message';
        });
    }
}
