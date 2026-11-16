<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Profile;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Services\UsernameGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Username dipakai untuk login: hanya huruf kecil, angka, dan titik (titik tidak di
 * awal/akhir/berurutan). Huruf besar dipaksa kecil. Username lama yang terlanjur
 * bersimbol lain (mis. "@Israfil", "DPRD_01") dibiarkan selama tidak diganti.
 */
class UsernameFormatTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = UsernameGenerator::INVALID_MESSAGE;

    private Department $department;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'staf'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        SppdWorkflow::create([
            'name' => 'Staf',
            'department_type' => ['opd'],
            'applicant_role' => ['staf'],
            'steps' => [['role' => 'admin_opd']],
            'is_active' => true,
        ]);

        $this->department = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->admin = User::factory()->create(['department_id' => $this->department->id]);
        $this->admin->assignRole('admin_opd');
    }

    public static function invalidUsernames(): array
    {
        return [
            'spasi di tengah' => ['budi santoso'],
            'tab' => ["budi\tsantoso"],
            'titik di awal' => ['.budi'],
            'titik di akhir' => ['budi.'],
            'titik berurutan' => ['budi..santoso'],
            'garis bawah' => ['budi_santoso'],
            'tanda hubung' => ['budi-santoso'],
            'at' => ['@budi'],
            'huruf beraksen' => ['budí'],
        ];
    }

    private function createPegawai(string $username)
    {
        return Livewire::actingAs($this->admin)
            ->test(UserForm::class)
            ->set('name', 'Budi Santoso')
            ->set('username', $username)
            ->set('department_id', $this->department->id)
            ->call('save');
    }

    #[DataProvider('invalidUsernames')]
    public function test_tambah_pegawai_dengan_username_bersimbol_ditolak(string $username): void
    {
        $component = $this->createPegawai($username);

        $this->assertContains(self::MESSAGE, $component->errors()->get('username'));
        $this->assertFalse(User::where('name', 'Budi Santoso')->exists());
    }

    public function test_tambah_pegawai_dengan_titik_dan_angka_diterima(): void
    {
        $this->createPegawai('budi.santoso2')->assertHasNoErrors();

        $this->assertSame('budi.santoso2', User::where('name', 'Budi Santoso')->value('username'));
    }

    public function test_spasi_di_awal_dan_akhir_dibuang_sebelum_disimpan(): void
    {
        Livewire::actingAs($this->admin)
            ->test(UserForm::class)
            ->set('name', '  Budi Santoso ')
            ->set('username', ' budi.santoso ')
            ->set('email', ' budi@example.com ')
            ->set('department_id', $this->department->id)
            ->call('save')
            ->assertHasNoErrors()
            // Isian di form ikut dirapikan.
            ->assertSet('name', 'Budi Santoso');

        $user = User::where('username', 'budi.santoso')->firstOrFail();
        $this->assertSame('Budi Santoso', $user->name);
        $this->assertSame('budi@example.com', $user->email);
    }

    public function test_edit_pegawai_merapikan_nama_lama_berspasi(): void
    {
        $pegawai = User::factory()->create(['username' => 'kaisar.pelangko', 'department_id' => $this->department->id]);
        $pegawai->assignRole('staf');
        // Data lama yang terlanjur tersimpan dengan spasi di depan (sebelum ada perapian).
        User::whereKey($pegawai->id)->toBase()->update(['name' => ' Kaisar Pelangko']);

        Livewire::actingAs($this->admin)
            ->test(UserForm::class, ['user' => $pegawai->fresh()])
            ->assertSet('name', ' Kaisar Pelangko')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Kaisar Pelangko', $pegawai->fresh()->name);
    }

    public function test_tambah_pegawai_huruf_besar_dipaksa_kecil(): void
    {
        $this->createPegawai('Budi.Santoso')->assertHasNoErrors();

        $this->assertSame('budi.santoso', User::where('name', 'Budi Santoso')->value('username'));
    }

    public function test_edit_pegawai_dengan_username_lama_bersimbol_tetap_bisa_disimpan(): void
    {
        $pegawai = User::factory()->create(['username' => 'DPRD_01', 'department_id' => $this->department->id]);
        $pegawai->assignRole('staf');

        Livewire::actingAs($this->admin)
            ->test(UserForm::class, ['user' => $pegawai])
            ->set('name', 'Aan Adam Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('DPRD_01', $pegawai->fresh()->username);
        $this->assertSame('Aan Adam Baru', $pegawai->fresh()->name);
    }

    public function test_edit_pegawai_ganti_username_bersimbol_ditolak(): void
    {
        $pegawai = User::factory()->create(['username' => 'DPRD_01', 'department_id' => $this->department->id]);
        $pegawai->assignRole('staf');

        $component = Livewire::actingAs($this->admin)
            ->test(UserForm::class, ['user' => $pegawai])
            ->set('username', 'DPRD_02')
            ->call('save');

        $this->assertContains(self::MESSAGE, $component->errors()->get('username'));
        $this->assertSame('DPRD_01', $pegawai->fresh()->username);
    }

    public function test_profil_username_lama_bersimbol_tetap_bisa_disimpan(): void
    {
        $user = User::factory()->create(['username' => '@Israfil']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'Aan Adam Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('@Israfil', $user->fresh()->username);
    }

    public function test_profil_ganti_username_bersimbol_ditolak(): void
    {
        $user = User::factory()->create(['username' => 'pegawai']);

        $component = Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('username', 'pegawai_baru')
            ->call('save');

        $this->assertContains(self::MESSAGE, $component->errors()->get('username'));
        $this->assertSame('pegawai', $user->fresh()->username);
    }

    public function test_profil_spasi_di_awal_dan_akhir_dibuang(): void
    {
        $user = User::factory()->create(['username' => 'pegawai']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', ' Aan Adam ')
            ->set('username', ' pegawai.baru ')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Aan Adam', $user->fresh()->name);
        $this->assertSame('pegawai.baru', $user->fresh()->username);
    }

    public function test_profil_ganti_username_huruf_besar_dipaksa_kecil(): void
    {
        $user = User::factory()->create(['username' => 'pegawai']);

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('username', 'Pegawai.Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('pegawai.baru', $user->fresh()->username);
    }

    public function test_generator_membuat_username_sesuai_aturan(): void
    {
        $generator = app(UsernameGenerator::class);

        $this->assertSame('ikrar.sakra', $generator->generate('Ikrar Anggara Sakra, S.T.'));

        User::factory()->create(['username' => 'ikrar.sakra']);
        $this->assertSame('ikrar.sakra2', $generator->generate('Ikrar Anggara Sakra'));
        $this->assertMatchesRegularExpression(UsernameGenerator::PATTERN, $generator->generate("Dr. Andi O'Neil-Putra, M.M."));
    }
}
