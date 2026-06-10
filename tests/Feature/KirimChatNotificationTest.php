<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotificationJob;
use App\Services\KirimChatService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class KirimChatNotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['kirimchat.base_url' => 'https://api-prod.kirim.chat/api/v1/public']);
        config(['kirimchat.api_key' => 'test-key']);
        config(['kirimchat.enabled' => true]);
    }

    public function test_kirim_chat_service_normalizes_and_sends_whatsapp(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/messages/send' => Http::response(['success' => true], 200),
        ]);

        $service = app(KirimChatService::class);

        // Test leading 0 conversion
        $result = $service->send('081234567890', 'Hello World');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api-prod.kirim.chat/api/v1/public/messages/send' &&
                $request['phone_number'] === '6281234567890' &&
                $request['channel'] === 'whatsapp' &&
                $request['message_type'] === 'text' &&
                $request['content'] === 'Hello World' &&
                $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }

    public function test_kirim_chat_service_normalizes_8xx_correctly(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/messages/send' => Http::response(['success' => true], 200),
        ]);

        $service = app(KirimChatService::class);

        // Test prepending 62 to 8xx
        $result = $service->send('81234567890', 'Test Message');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['phone_number'] === '6281234567890' &&
                $request['content'] === 'Test Message';
        });
    }

    public function test_kirim_chat_service_accepts_already_normalized_chat_id(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/messages/send' => Http::response(['success' => true], 200),
        ]);

        $service = app(KirimChatService::class);

        // Test with chat ID format
        $result = $service->send('6281234567890@c.us', 'Hello World');
        $this->assertTrue($result);

        Http::assertSent(function ($request) {
            return $request['phone_number'] === '6281234567890';
        });
    }

    public function test_kirim_chat_service_returns_false_if_disabled(): void
    {
        config(['kirimchat.enabled' => false]);

        $service = app(KirimChatService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_kirim_chat_service_returns_false_if_no_api_key(): void
    {
        config(['kirimchat.api_key' => '']);

        $service = app(KirimChatService::class);
        $result = $service->send('081234567890', 'Hello');
        $this->assertFalse($result);
    }

    public function test_kirim_chat_service_health_check_success(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/health' => Http::response([
                'success' => true,
                'data' => [
                    'status' => 'ok',
                    'authenticated' => true,
                ]
            ], 200),
        ]);

        $service = app(KirimChatService::class);
        $result = $service->healthCheck();

        $this->assertTrue($result['success']);
        $this->assertTrue($result['authenticated']);
        $this->assertEquals('Koneksi berhasil.', $result['message']);
    }

    public function test_kirim_chat_service_health_check_failed(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/health' => Http::response([
                'success' => false,
            ], 401),
        ]);

        $service = app(KirimChatService::class);
        $result = $service->healthCheck();

        $this->assertFalse($result['success']);
        $this->assertFalse($result['authenticated']);
        $this->assertStringContainsString('API tidak dapat dijangkau', $result['message']);
    }

    public function test_whatsapp_notification_job_is_dispatched(): void
    {
        Queue::fake();

        SendWhatsAppNotificationJob::dispatch('081234567890', 'Hello World');

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) {
            return $job->phone === '081234567890' && $job->message === 'Hello World';
        });
    }

    public function test_whatsapp_notification_job_calls_kirim_chat_service(): void
    {
        Http::fake([
            'https://api-prod.kirim.chat/api/v1/public/messages/send' => Http::response(['success' => true], 200),
        ]);

        $job = new SendWhatsAppNotificationJob('081234567890', 'Job Message');
        $job->handle(app(KirimChatService::class));

        Http::assertSent(function ($request) {
            return $request['phone_number'] === '6281234567890' &&
                $request['content'] === 'Job Message';
        });
    }

    public function test_kirim_chat_webhook_verifies_successfully(): void
    {
        Queue::fake();

        $token = 'V-12345';
        \Illuminate\Support\Facades\Cache::put("wa_verification:{$token}", [
            'phone' => '081341770730',
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'user_id' => null,
        ], now()->addMinutes(15));

        $payload = [
            'event' => 'message.received',
            'data' => [
                'from' => '6281341770730',
                'message' => "📱 VERIFIKASI WA SPPD\nKode Verifikasi: {$token}",
            ]
        ];

        $response = $this->postJson(route('webhook.kirimchat'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $status = \Illuminate\Support\Facades\Cache::get("wa_verified_status:{$token}");
        $this->assertNotNull($status);
        $this->assertTrue($status['verified']);
        $this->assertEquals('081341770730', $status['phone']);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) {
            return $job->phone === '6281341770730' &&
                str_contains($job->message, 'VERIFIKASI BERHASIL');
        });
    }

    public function test_kirim_chat_webhook_fails_with_expired_or_invalid_token(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'message.received',
            'data' => [
                'from' => '6281341770730',
                'message' => "📱 VERIFIKASI WA SPPD\nKode Verifikasi: V-99999",
            ]
        ];

        $response = $this->postJson(route('webhook.kirimchat'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => false]);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) {
            return $job->phone === '6281341770730' &&
                str_contains($job->message, 'VERIFIKASI GAGAL');
        });
    }



    public function test_kirim_chat_webhook_verifies_successfully_with_actual_payload(): void
    {
        Queue::fake();

        $token = 'V-54321';
        \Illuminate\Support\Facades\Cache::put("wa_verification:{$token}", [
            'phone' => '081341770730',
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'user_id' => null,
        ], now()->addMinutes(15));

        $payload = [
            'event_type' => 'message.received',
            'event_id' => '68601d9e-5f48-4254-a2cd-a5f9bf484eb6',
            'timestamp' => '2026-06-09T16:22:20.836Z',
            'data' => [
                'message_id' => 'cmq6ulj1au7sg3su41171ui21',
                'customer_id' => 'cmq6ulj10u7sa3su44niidonb',
                'customer_phone' => '6281341770730',
                'direction' => 'inbound',
                'message_type' => 'text',
                'content' => "Verifikasi WhatsApp SPPD Kendari:\n📱 *Nomor:* 081341770730\n🔑 *Kode:* {$token}\n\n_Jangan ubah isi pesan ini. Silakan kirim, lalu cek status secara berkala di halaman browser Anda._",
                'channel' => 'whatsapp',
                'customer_name' => 'ilmifaizan',
                'wam_id' => 'wamid.HBgNNjI4MTM0MTc3MDczMBUCABIYIEFDMTJFMDRCMkE3REJFRDY4Rjg3QUY1NzAxNUU2QkU2AA==',
                'raw' => [
                    'message' => [
                        'from' => '6281341770730',
                        'from_user_id' => 'ID.1018928637196383',
                        'id' => 'wamid.HBgNNjI4MTM0MTc3MDczMBUCABIYIEFDMTJFMDRCMkE3REJFRDY4Rjg3QUY1NzAxNUU2QkU2AA==',
                        'timestamp' => '1781022139',
                        'text' => [
                            'body' => "Verifikasi WhatsApp SPPD Kendari:\n📱 *Nomor:* 081341770730\n🔑 *Kode:* {$token}\n\n_Jangan ubah isi pesan ini. Silakan kirim, lalu cek status secara berkala di halaman browser Anda._",
                        ],
                        'type' => 'text',
                    ],
                ],
            ],
        ];

        $response = $this->postJson(route('webhook.kirimchat'), $payload);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $status = \Illuminate\Support\Facades\Cache::get("wa_verified_status:{$token}");
        $this->assertNotNull($status);
        $this->assertTrue($status['verified']);
        $this->assertEquals('081341770730', $status['phone']);

        Queue::assertPushed(SendWhatsAppNotificationJob::class, function ($job) {
            return $job->phone === '6281341770730' &&
                str_contains($job->message, 'VERIFIKASI BERHASIL');
        });
    }
}
