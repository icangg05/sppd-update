<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tautan login (impersonasi) dari halaman Detail Pegawai harus menghasilkan sesi yang sama
 * dengan login asli: dimulai dari sesi kosong, bukan meneruskan sesi pengguna sebelumnya.
 */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private User $sekda;
    private User $sekwan;

    protected function setUp(): void
    {
        parent::setUp();

        // Beranda & Daftar SPPD memakai YEAR() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        $this->seed(RoleAndPermissionSeeder::class);
        $this->withoutVite();

        $setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $dprd = Department::create(['name' => 'Sekretariat DPRD', 'type' => DepartmentType::DPRD, 'level' => 1]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');

        $this->sekda = User::factory()->create(['department_id' => $setda->id]);
        $this->sekda->assignRole('sekda');

        $this->sekwan = User::factory()->create(['department_id' => $dprd->id]);
        $this->sekwan->assignRole('sekwan');
    }

    private function link(User $user): string
    {
        return URL::temporarySignedRoute('users.impersonate', now()->addMinutes(5), [
            'user' => $user,
            'by' => $this->superAdmin->id,
            'nonce' => (string) Str::uuid(),
        ]);
    }

    public function test_tautan_membuang_sesi_pengguna_sebelumnya(): void
    {
        // Browser yang sama baru saja dipakai sebagai Sekwan, lengkap dengan filter Daftar SPPD-nya.
        $recaller = Auth::guard()->getRecallerName();

        $this->actingAs($this->sekwan)
            ->withSession([
                'sppd.index.filters' => ['jabatan' => 'anggota_dprd', 'year' => '2025'],
                'url.intended' => route('master.users.index'),
            ])
            ->withCookie($recaller, $this->sekwan->id . '|token|hash')
            ->get($this->link($this->sekda))
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('sppd.index.filters')
            ->assertSessionMissing('url.intended')
            ->assertCookieExpired($recaller);

        $this->assertAuthenticatedAs($this->sekda);

        // Menu Daftar SPPD Sekda tanpa filter Jabatan/Tahun peninggalan Sekwan.
        $this->get(route('dashboard'))->assertOk()
            ->assertSee(route('sppd.index'), false)
            ->assertDontSee('jabatan=anggota_dprd', false);
    }

    public function test_sesi_hasil_tautan_mendapat_hak_akses_pengguna(): void
    {
        $this->get($this->link($this->sekda))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->sekda);
        $this->get(route('sppd.index', ['filter' => 'approval']))->assertOk();
        $this->get(route('master.users.index'))->assertForbidden();
    }

    public function test_akun_diblokir_tidak_bisa_dimasuki_lewat_tautan(): void
    {
        $this->sekda->forceFill(['login_blocked_at' => now()])->save();

        $this->get($this->link($this->sekda))->assertForbidden();
        $this->assertGuest();
    }

    public function test_tombol_salin_tautan_nonaktif_untuk_akun_diblokir_atau_nonaktif(): void
    {
        $detail = fn () => $this->actingAs($this->superAdmin)->get(route('master.users.show', $this->sekda))->assertOk();

        $detail()->assertSee('navigator.clipboard.writeText', false);

        $this->sekda->forceFill(['login_blocked_at' => now()])->save();

        $detail()
            ->assertSee('Akun ini sedang diblokir. Buka blokirnya terlebih dahulu')
            ->assertSee('Salin Tautan Login')
            ->assertDontSee('navigator.clipboard.writeText', false);

        $this->sekda->forceFill(['login_blocked_at' => null, 'is_active' => false])->save();

        $detail()
            ->assertSee('Akun ini nonaktif. Aktifkan terlebih dahulu')
            ->assertSee('Salin Tautan Login')
            ->assertDontSee('navigator.clipboard.writeText', false);
    }

    public function test_keluar_dari_sesi_tautan_tidak_mengeluarkan_perangkat_pemilik_akun(): void
    {
        $this->sekda->forceFill(['remember_token' => 'token-perangkat-sekda'])->save();

        $this->get($this->link($this->sekda));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame('token-perangkat-sekda', $this->sekda->fresh()->remember_token);
    }
}
