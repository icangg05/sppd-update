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

    public function test_token_ditolak_mereset_widget(): void
    {
        $this->fakeCloudflare(false);

        $this->attemptLogin('token-palsu', 'salah')
            ->assertSet('turnstileToken', '')
            ->assertDispatched('turnstile-reset');
    }

    public function test_salah_password_tidak_mereset_widget_dan_tidak_meminta_token_baru(): void
    {
        $this->fakeCloudflare(true);

        $this->attemptLogin('token-sah', 'salah')
            ->assertSet('turnstileToken', '')
            ->assertNotDispatched('turnstile-reset');

        $this->attemptLogin('', 'salah')
            ->assertNotDispatched('turnstile-reset');

        Http::assertSentCount(1);
        $this->assertSame(2, $this->user->refresh()->failed_login_attempts);
    }

    public function test_setelah_salah_password_bisa_login_tanpa_verifikasi_ulang(): void
    {
        $this->fakeCloudflare(true);

        $this->attemptLogin('token-sah', 'salah');
        $this->attemptLogin('', 'password')->assertRedirect();

        $this->assertAuthenticatedAs($this->user);
        Http::assertSentCount(1);
    }

    public function test_verifikasi_kedaluwarsa_setelah_batas_waktu(): void
    {
        $this->fakeCloudflare(true);

        $this->attemptLogin('token-sah', 'salah');
        $this->travel(Login::TURNSTILE_PASS_SECONDS + 1)->seconds();

        $this->attemptLogin('', 'password')
            ->assertNoRedirect()
            ->assertDispatched('turnstile-reset');

        $this->assertGuest();
    }

    public function test_verifikasi_habis_setelah_batas_percobaan(): void
    {
        $this->fakeCloudflare(true);

        foreach (range(1, Login::TURNSTILE_PASS_ATTEMPTS) as $attempt) {
            $last = $this->attemptLogin($attempt === 1 ? 'token-sah' : '', 'salah');
        }

        $last->assertDispatched('turnstile-reset');

        $this->attemptLogin('', 'password')->assertNoRedirect();

        $this->assertGuest();
        Http::assertSentCount(1);
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
