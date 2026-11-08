<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTurnstileTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.turnstile.site_key' => 'kunci-situs',
            'services.turnstile.secret_key' => 'kunci-rahasia',
        ]);

        $this->user = User::factory()->create(['username' => 'pegawai']);
    }

    private function fakeCloudflare(bool $success): void
    {
        Http::fake([self::VERIFY_URL => Http::response([
            'success' => $success,
            'error-codes' => $success ? [] : ['invalid-input-response'],
        ])]);
    }

    private function attemptLogin(string $token, string $password = 'password')
    {
        return Livewire::test(Login::class)
            ->set('username', 'pegawai')
            ->set('password', $password)
            ->set('turnstileToken', $token)
            ->call('login');
    }

    public function test_token_sah_diteruskan_ke_cloudflare_dan_login_berhasil(): void
    {
        $this->fakeCloudflare(true);

        $this->attemptLogin('token-sah')->assertRedirect();

        $this->assertAuthenticatedAs($this->user);
        Http::assertSent(fn ($request) => $request->url() === self::VERIFY_URL
            && $request['secret'] === 'kunci-rahasia'
            && $request['response'] === 'token-sah');
    }

    public function test_tanpa_token_ditolak_tanpa_memanggil_cloudflare(): void
    {
        $this->fakeCloudflare(true);

        $this->attemptLogin('')->assertNoRedirect();

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_token_ditolak_cloudflare_tidak_bisa_login_walau_password_benar(): void
    {
        $this->fakeCloudflare(false);

        $this->attemptLogin('token-palsu')->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_gagal_turnstile_tidak_menambah_hitungan_salah_password(): void
    {
        $this->fakeCloudflare(false);

        foreach (range(1, Login::BLOCK_AFTER_ATTEMPTS) as $_) {
            $this->attemptLogin('token-palsu', 'salah');
        }

        $this->user->refresh();
        $this->assertSame(0, $this->user->failed_login_attempts);
        $this->assertNull($this->user->login_locked_until);
        $this->assertFalse($this->user->isLoginBlocked());
    }

    public function test_cloudflare_tidak_terjangkau_berarti_login_ditolak(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->attemptLogin('token-sah')->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_token_dikosongkan_dan_widget_direset_setiap_percobaan(): void
    {
        $this->fakeCloudflare(true);

        $this->attemptLogin('token-sah', 'salah')
            ->assertSet('turnstileToken', '')
            ->assertDispatched('turnstile-reset');
    }

    public function test_tanpa_kunci_turnstile_nonaktif(): void
    {
        config(['services.turnstile.secret_key' => '']);
        $this->fakeCloudflare(false);

        Livewire::test(Login::class)
            ->assertDontSee('challenges.cloudflare.com')
            ->set('username', 'pegawai')
            ->set('password', 'password')
            ->call('login')
            ->assertRedirect();

        $this->assertAuthenticatedAs($this->user);
        Http::assertNothingSent();
    }
}
