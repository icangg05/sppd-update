<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $username, ?string $role = null): User
    {
        $user = User::factory()->create(['username' => $username]);

        if ($role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
            $user->assignRole($role);
        }

        return $user;
    }

    private function attempt(string $username, string $password = 'salah')
    {
        return Livewire::test(Login::class)
            ->set('username', $username)
            ->set('password', $password)
            ->call('login');
    }

    /** Habiskan satu ronde (3x salah) lalu tunggu sampai kunci berakhir. */
    private function failRoundAndWait(string $username): void
    {
        foreach (range(1, Login::MAX_ATTEMPTS) as $_) {
            $this->attempt($username);
        }

        $this->travel(Login::LOCKOUT_SECONDS + 1)->seconds();
    }

    public function test_tiga_kali_salah_mengunci_akun_satu_menit(): void
    {
        $user = $this->makeUser('pegawai');

        $this->attempt('pegawai')->assertSet('lockoutSeconds', 0);
        $this->attempt('pegawai')->assertSet('lockoutSeconds', 0);
        $this->attempt('pegawai')
            ->assertSet('lockoutSeconds', Login::LOCKOUT_SECONDS)
            ->assertSet('attemptsBeforeBlock', 3);

        $user->refresh();
        $this->assertSame(3, $user->failed_login_attempts);
        $this->assertTrue($user->login_locked_until->isFuture());
        $this->assertFalse($user->isLoginBlocked());

        // Selama terkunci, password benar pun ditolak.
        $this->attempt('pegawai', 'password')->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_tiga_kali_salah_lagi_setelah_dikunci_memblokir_akun(): void
    {
        $user = $this->makeUser('pegawai');
        $this->failRoundAndWait('pegawai');

        // Ronde kedua: dua kali salah pertama hanya peringatan, belum diblokir.
        $this->attempt('pegawai')->assertSet('blocked', false)->assertSet('lockoutSeconds', 0)->assertSet('attemptsBeforeBlock', 2);
        $this->attempt('pegawai')->assertSet('blocked', false)->assertSet('attemptsBeforeBlock', 1);
        $this->assertFalse($user->refresh()->isLoginBlocked());

        $this->attempt('pegawai')->assertSet('blocked', true);
        $this->assertTrue($user->refresh()->isLoginBlocked());

        // Akun terblokir tetap ditolak walau password benar dan waktu berlalu.
        $this->travel(1)->days();
        $this->attempt('pegawai', 'password')->assertSet('blocked', true)->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_super_admin_hanya_dikunci_per_ronde_tanpa_peringatan_blokir(): void
    {
        $admin = $this->makeUser('superadmin', 'super_admin');

        foreach (range(1, 3) as $_) {
            $this->attempt('superadmin')->assertSet('attemptsBeforeBlock', 0);
        }
        $this->travel(Login::LOCKOUT_SECONDS + 1)->seconds();

        // Ronde kedua & ketiga: tetap hanya dikunci, tak pernah diblokir/diperingatkan.
        foreach (range(1, 2) as $_) {
            $this->attempt('superadmin')->assertSet('lockoutSeconds', 0)->assertSet('attemptsBeforeBlock', 0);
            $this->attempt('superadmin')->assertSet('lockoutSeconds', 0)->assertSet('attemptsBeforeBlock', 0);
            $this->attempt('superadmin')
                ->assertSet('blocked', false)
                ->assertSet('attemptsBeforeBlock', 0)
                ->assertSet('lockoutSeconds', Login::LOCKOUT_SECONDS);

            $this->assertFalse($admin->refresh()->isLoginBlocked());
            $this->travel(Login::LOCKOUT_SECONDS + 1)->seconds();
        }

        $this->attempt('superadmin', 'password')->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->assertSame(0, $admin->refresh()->failed_login_attempts);
    }

    public function test_login_berhasil_mereset_hitungan_gagal(): void
    {
        $user = $this->makeUser('pegawai');

        $this->attempt('pegawai');
        $this->attempt('pegawai');
        $this->attempt('pegawai', 'password')->assertRedirect();

        $this->assertSame(0, $user->refresh()->failed_login_attempts);
    }

    public function test_super_admin_dapat_membuka_blokir(): void
    {
        $admin = $this->makeUser('superadmin', 'super_admin');
        $user = $this->makeUser('pegawai');
        $user->forceFill(['failed_login_attempts' => 6, 'login_blocked_at' => now()])->save();

        $this->actingAs($admin);
        Livewire::test(\App\Livewire\UsersIndex::class)->call('unblockLogin', $user->id);

        $user->refresh();
        $this->assertFalse($user->isLoginBlocked());
        $this->assertSame(0, $user->failed_login_attempts);
    }

    public function test_selain_super_admin_tidak_dapat_membuka_blokir(): void
    {
        $operator = $this->makeUser('operator', 'admin_opd');
        $user = $this->makeUser('pegawai');
        $user->forceFill(['login_blocked_at' => now()])->save();

        $this->actingAs($operator);
        Livewire::test(\App\Livewire\UsersIndex::class)
            ->call('unblockLogin', $user->id)
            ->assertForbidden();

        $this->assertTrue($user->refresh()->isLoginBlocked());
    }

    public function test_username_tidak_dikenal_mengikuti_pola_yang_sama(): void
    {
        $this->attempt('tidakada');
        $this->attempt('tidakada');
        $this->attempt('tidakada')->assertSet('lockoutSeconds', Login::LOCKOUT_SECONDS);
        $this->attempt('tidakada')->assertSet('lockoutSeconds', fn ($s) => $s > 0 && $s <= Login::LOCKOUT_SECONDS);

        $this->travel(Login::LOCKOUT_SECONDS + 1)->seconds();
        $this->attempt('tidakada')->assertSet('attemptsBeforeBlock', 2);
        $this->attempt('tidakada')->assertSet('attemptsBeforeBlock', 1);
        $this->attempt('tidakada')->assertSet('blocked', true);
    }
}
