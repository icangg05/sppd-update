<?php

namespace Tests\Feature;

use App\Jobs\SendTelegramNotificationJob;
use App\Livewire\Profile;
use App\Models\User;
use App\Services\TelegramService;
use App\Services\TelegramUpdateHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class TelegramNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const API = 'https://api.telegram.org/bottest-token';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'telegram.api_url' => 'https://api.telegram.org',
            'telegram.bot_token' => 'test-token',
            'telegram.bot_username' => 'SppdTestBot',
            'telegram.enabled' => true,
            'telegram.webhook_secret' => null,
        ]);
    }

    /** Menyiapkan token pending seperti InteractsWithTelegramLink::openLinkModal(). */
    private function pendingLink(string $token, ?int $userId = null): void
    {
        TelegramUpdateHandler::cache()->put("tg_link:{$token}", [
            'user_id' => $userId,
            'name' => 'Budi',
        ], now()->addMinutes(15));
    }

    /** Update Telegram berisi pesan teks dari pengguna (chat_id pribadi = 555). */
    private function messageUpdate(string $text, string $chatType = 'private'): array
    {
        return [
            'update_id' => 1001,
            'message' => [
                'message_id' => 10,
                'from' => ['id' => 555, 'is_bot' => false, 'first_name' => 'Budi', 'username' => 'budi_k'],
                'chat' => ['id' => $chatType === 'private' ? 555 : -100123, 'type' => $chatType],
                'date' => 1760000000,
                'text' => $text,
            ],
        ];
    }

    // ── TelegramService ──

    public function test_service_sends_html_converted_from_whatsapp_style_markup(): void
    {
        Http::fake([self::API.'/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        $sent = app(TelegramService::class)->send('12345', '*Halo* <Budi> & _tim_ https://x.test/a_b_c');

        $this->assertTrue($sent);
        Http::assertSent(fn ($request) => $request['chat_id'] === '12345'
            && $request['parse_mode'] === 'HTML'
            && $request['text'] === '<b>Halo</b> &lt;Budi&gt; &amp; <i>tim</i> https://x.test/a_b_c');
    }

    public function test_service_falls_back_to_plain_text_when_markup_rejected(): void
    {
        Http::fake([self::API.'/sendMessage' => Http::sequence()
            ->push(['ok' => false, 'description' => "Bad Request: can't parse entities: unexpected end tag"], 400)
            ->push(['ok' => true, 'result' => []], 200)]);

        $this->assertTrue(app(TelegramService::class)->send('12345', '_a *b_ c*'));

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => ! isset($request['parse_mode']) && $request['text'] === '_a *b_ c*');
    }

    public function test_service_unlinks_user_when_bot_is_blocked(): void
    {
        $user = User::factory()->create([
            'username' => 'budi',
            'telegram_chat_id' => '777',
            'telegram_username' => 'budi_k',
            'telegram_linked_at' => now(),
        ]);

        Http::fake([self::API.'/sendMessage' => Http::response([
            'ok' => false,
            'error_code' => 403,
            'description' => 'Forbidden: bot was blocked by the user',
        ], 403)]);

        $this->assertFalse(app(TelegramService::class)->send('777', 'Halo'));

        $user->refresh();
        $this->assertNull($user->telegram_chat_id);
        $this->assertFalse($user->canReceiveTelegram());
    }

    public function test_service_returns_false_if_disabled(): void
    {
        config(['telegram.enabled' => false]);
        Http::fake();

        $this->assertFalse(app(TelegramService::class)->send('12345', 'Halo'));
        Http::assertNothingSent();
    }

    public function test_service_returns_false_if_no_token(): void
    {
        config(['telegram.bot_token' => '']);
        Http::fake();

        $this->assertFalse(app(TelegramService::class)->send('12345', 'Halo'));
        Http::assertNothingSent();
    }

    public function test_get_me_reports_bot_username(): void
    {
        Http::fake([self::API.'/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'SppdTestBot']], 200)]);

        $result = app(TelegramService::class)->getMe();

        $this->assertTrue($result['success']);
        $this->assertSame('SppdTestBot', $result['username']);
    }

    // ── Job & model ──

    public function test_notification_job_is_dispatched(): void
    {
        Queue::fake();

        SendTelegramNotificationJob::dispatch('12345', 'Hello World');

        Queue::assertPushed(SendTelegramNotificationJob::class, fn ($job) => $job->chatId === '12345'
            && $job->message === 'Hello World');
    }

    public function test_notification_job_calls_telegram_service(): void
    {
        Http::fake([self::API.'/sendMessage' => Http::response(['ok' => true, 'result' => []], 200)]);

        (new SendTelegramNotificationJob('12345', 'Job Message'))->handle(app(TelegramService::class));

        Http::assertSent(fn ($request) => $request['chat_id'] === '12345' && $request['text'] === 'Job Message');
    }

    public function test_can_receive_telegram_requires_link_and_enabled_notify(): void
    {
        $this->assertFalse(User::factory()->make(['telegram_chat_id' => null, 'telegram_notify' => true])->canReceiveTelegram());
        $this->assertFalse(User::factory()->make(['telegram_chat_id' => '555', 'telegram_notify' => false])->canReceiveTelegram());
        $this->assertTrue(User::factory()->make(['telegram_chat_id' => '555', 'telegram_notify' => true])->canReceiveTelegram());
    }

    // ── Webhook / TelegramUpdateHandler ──

    public function test_webhook_links_account_with_valid_token(): void
    {
        Queue::fake();
        $user = User::factory()->create(['username' => 'budi']);
        $token = str_repeat('a', 32);
        $this->pendingLink($token, $user->id);

        $this->postJson(route('webhook.telegram'), $this->messageUpdate("/start {$token}"))->assertOk();

        $status = TelegramUpdateHandler::cache()->get("tg_link_status:{$token}");
        $this->assertTrue($status['verified']);
        $this->assertSame('555', $status['chat_id']);

        $user->refresh();
        $this->assertSame('555', $user->telegram_chat_id);
        $this->assertSame('budi_k', $user->telegram_username);
        $this->assertNotNull($user->telegram_linked_at);

        // Token sekali pakai.
        $this->assertNull(TelegramUpdateHandler::cache()->get("tg_link:{$token}"));

        Queue::assertPushed(SendTelegramNotificationJob::class, fn ($job) => $job->chatId === '555'
            && str_contains($job->message, 'AKUN TERHUBUNG'));
    }

    public function test_webhook_replies_expired_when_token_unknown(): void
    {
        Queue::fake();
        $token = str_repeat('b', 32);

        $this->postJson(route('webhook.telegram'), $this->messageUpdate("/start {$token}"))->assertOk();

        $this->assertNull(TelegramUpdateHandler::cache()->get("tg_link_status:{$token}"));
        Queue::assertPushed(SendTelegramNotificationJob::class, fn ($job) => str_contains($job->message, 'KEDALUWARSA'));
    }

    public function test_webhook_rejects_link_from_group_chat(): void
    {
        Queue::fake();
        $user = User::factory()->create(['username' => 'budi']);
        $token = str_repeat('c', 32);
        $this->pendingLink($token, $user->id);

        $this->postJson(route('webhook.telegram'), $this->messageUpdate("/start@SppdTestBot {$token}", 'group'))->assertOk();

        $this->assertNull(TelegramUpdateHandler::cache()->get("tg_link_status:{$token}"));
        $this->assertNotNull(TelegramUpdateHandler::cache()->get("tg_link_failed:{$token}"));
        $this->assertNull($user->fresh()->telegram_chat_id);
        Queue::assertNotPushed(SendTelegramNotificationJob::class);
    }

    public function test_webhook_replies_help_for_plain_message(): void
    {
        Queue::fake();

        $this->postJson(route('webhook.telegram'), $this->messageUpdate('halo'))->assertOk();

        Queue::assertPushed(SendTelegramNotificationJob::class, fn ($job) => $job->chatId === '555'
            && str_contains($job->message, 'Hubungkan Telegram'));
    }

    public function test_webhook_requires_valid_secret_when_configured(): void
    {
        Queue::fake();
        config(['telegram.webhook_secret' => 'rahasia']);
        $token = str_repeat('d', 32);
        $this->pendingLink($token);

        $this->postJson(route('webhook.telegram'), $this->messageUpdate("/start {$token}"))->assertStatus(401);
        $this->assertNull(TelegramUpdateHandler::cache()->get("tg_link_status:{$token}"));

        $this->postJson(route('webhook.telegram'), $this->messageUpdate("/start {$token}"), [
            'X-Telegram-Bot-Api-Secret-Token' => 'rahasia',
        ])->assertOk();
        $this->assertNotNull(TelegramUpdateHandler::cache()->get("tg_link_status:{$token}"));
    }

    // ── Alur lengkap dari halaman Profil ──

    public function test_profile_link_flow_saves_chat_id_and_unlink_clears_it(): void
    {
        Queue::fake();
        $user = User::factory()->create(['username' => 'budi', 'name' => 'Budi']);
        $this->actingAs($user);

        $component = Livewire::test(Profile::class)
            ->call('openLinkModal')
            ->assertSet('showLinkModal', true)
            ->assertSet('isPolling', true);

        $token = $component->get('linkToken');
        $this->assertSame("https://t.me/SppdTestBot?start={$token}", $component->get('deeplinkUrl'));

        // Pengguna menekan Start di bot.
        app(TelegramUpdateHandler::class)->handle($this->messageUpdate("/start {$token}"));

        $component->call('checkLinkStatus')
            ->assertSet('isVerified', true)
            ->assertSet('isPolling', false)
            ->assertSet('telegramLinked', true)
            ->assertSet('telegramChatId', '555')
            ->assertSet('telegramUsername', 'budi_k');

        $this->assertSame('555', $user->fresh()->telegram_chat_id);

        $component->call('unlinkTelegram')->assertSet('telegramLinked', false);
        $this->assertNull($user->fresh()->telegram_chat_id);
    }

    public function test_profile_test_message_is_dispatched_to_linked_chat(): void
    {
        Queue::fake();
        $user = User::factory()->create(['username' => 'budi', 'telegram_chat_id' => '555', 'telegram_username' => 'budi_k']);
        // fresh(): muat default kolom telegram_notify (true) dari DB.
        $this->actingAs($user->fresh());

        Livewire::test(Profile::class)->call('sendTestMessage');

        Queue::assertPushed(SendTelegramNotificationJob::class, fn ($job) => $job->chatId === '555'
            && str_contains($job->message, 'Tes Notifikasi'));
    }
}
