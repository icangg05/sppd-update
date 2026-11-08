<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\ReplaceKepalaDaerahModal;
use App\Livewire\UserForm;
use App\Livewire\UsersIndex;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Menu Walikota & Wakil Walikota: kedua role itu dikelola di daftar tersendiri
 * (?type=kepala_daerah) dan tidak lagi muncul di daftar Pegawai.
 */
class KepalaDaerahMenuTest extends TestCase
{
    use RefreshDatabase;

    private Department $setda;
    private User $adminSetda;
    private User $walikota;
    private User $wakil;
    private User $staf;

    protected function setUp(): void
    {
        parent::setUp();

        // Urutan Data Pegawai memakai FIELD() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('FIELD', function ($value, ...$list) {
                $index = array_search($value, $list);

                return $index === false ? 0 : $index + 1;
            });
        }

        foreach (['super_admin', 'admin_opd', 'staf', 'walikota', 'wakil_walikota'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => ucwords(str_replace('_', ' ', $role))]);
        }

        $this->setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $this->adminSetda = $this->user('admin_opd', ['name' => 'Admin Setda']);
        $this->walikota = $this->user('walikota', ['name' => 'Ibu Walikota']);
        $this->wakil = $this->user('wakil_walikota', ['name' => 'Bapak Wakil']);
        $this->staf = $this->user('staf', ['name' => 'Staf Setda']);
    }

    private function user(string $role, array $attributes = [], ?Department $department = null): User
    {
        $user = User::factory()->create(['department_id' => ($department ?? $this->setda)->id, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    public function test_daftar_pegawai_tidak_memuat_walikota_dan_wakil(): void
    {
        $page = Livewire::actingAs($this->adminSetda)->test(UsersIndex::class)
            ->assertSee('Staf Setda')
            ->assertDontSee('Ibu Walikota')
            ->assertDontSee('Bapak Wakil');

        // Role keduanya juga tidak muncul di pilihan filter role daftar Pegawai.
        $roles = $page->viewData('availableRoles')->pluck('name');
        $this->assertNotContains('walikota', $roles);
        $this->assertNotContains('wakil_walikota', $roles);
    }

    public function test_menu_walikota_hanya_memuat_walikota_dan_wakil(): void
    {
        Livewire::withQueryParams(['type' => 'kepala_daerah'])
            ->actingAs($this->adminSetda)->test(UsersIndex::class)
            ->assertSee('Data Walikota &amp; Wakil Walikota', false)
            ->assertSee('Ibu Walikota')
            ->assertSee('Bapak Wakil')
            ->assertDontSee('Staf Setda')
            ->assertDontSee('Admin Setda')
            // Tanpa tombol tambah dan tanpa filter role.
            ->assertDontSee(route('master.users.create', ['type' => 'kepala_daerah']), false)
            ->assertDontSee('Semua Role');
    }

    public function test_super_admin_di_menu_walikota_tanpa_filter_instansi(): void
    {
        $superAdmin = $this->user('super_admin');
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);

        // Nilai filter instansi yang terbawa di URL diabaikan, Walikota tetap tampil.
        Livewire::withQueryParams(['type' => 'kepala_daerah', 'department_id' => (string) $dinas->id])
            ->actingAs($superAdmin)->test(UsersIndex::class)
            ->assertDontSee('Semua Instansi')
            ->assertSee('Ibu Walikota');

        // Daftar Pegawai tetap punya tombol tambah dan filter (withQueryParams([]): parameter sebelumnya terbawa).
        Livewire::withQueryParams([])->actingAs($superAdmin)->test(UsersIndex::class)
            ->assertSee(route('master.users.create'), false)
            ->assertSee('Semua Instansi')
            ->assertSee('Semua Role');
    }

    public function test_menu_tampil_di_sidebar_admin_setda_saja(): void
    {
        $this->withoutVite();
        $url = route('master.users.index', ['type' => 'kepala_daerah']);

        $this->actingAs($this->adminSetda)->get(route('master.users.index'))
            ->assertOk()->assertSee($url, false);

        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $adminDinas = $this->user('admin_opd', [], $dinas);
        $this->actingAs($adminDinas)->get(route('master.users.index'))
            ->assertOk()->assertDontSee($url, false);
    }

    public function test_form_dari_menu_walikota_membatasi_jabatan_dan_mengingat_konteks(): void
    {
        $posWalikota = Position::create(['name' => 'Walikota', 'default_role' => 'walikota']);
        $posWakil = Position::create(['name' => 'Wakil Walikota', 'default_role' => 'wakil_walikota']);
        $posStaf = Position::create(['name' => 'Pengadministrasi Umum']);

        // Tambah dari menu Walikota: hanya jabatan Walikota/Wakil, unit kerja langsung Setda.
        $form = Livewire::withQueryParams(['type' => 'kepala_daerah'])
            ->actingAs($this->adminSetda)->test(UserForm::class)
            ->assertSet('listType', 'kepala_daerah')
            ->assertSet('department_id', $this->setda->id);
        $this->assertEqualsCanonicalizing([$posWalikota->id, $posWakil->id], $form->viewData('positions')->pluck('id')->all());

        // Tambah dari menu Pegawai: jabatan Walikota/Wakil disembunyikan.
        $form = Livewire::withQueryParams([])->actingAs($this->adminSetda)->test(UserForm::class)
            ->assertSet('listType', '');
        $this->assertSame([$posStaf->id], $form->viewData('positions')->pluck('id')->all());

        // Edit Walikota tanpa parameter URL tetap kembali ke menu Walikota.
        Livewire::withQueryParams([])->actingAs($this->adminSetda)
            ->test(UserForm::class, ['user' => $this->walikota])
            ->assertSet('listType', 'kepala_daerah');
    }

    public function test_ganti_walikota_dari_menu_dan_akun_lama_tetap_utuh(): void
    {
        // Role jabatan hanya berlaku bila dipakai di workflow aktif (Position::roleForDepartment).
        $this->seed(\Database\Seeders\SppdWorkflowSeeder::class);
        $posWalikota = Position::create(['name' => 'Walikota', 'default_role' => 'walikota', 'uniqueness_scope' => 'system']);
        $this->walikota->update(['position_id' => $posWalikota->id]);
        $calon = $this->user('staf', ['name' => 'Calon Walikota']);

        // Tombol Ganti tampil di baris daftar Walikota & Wakil Walikota.
        Livewire::withQueryParams(['type' => 'kepala_daerah'])->actingAs($this->adminSetda)
            ->test(UsersIndex::class)
            ->assertSeeHtml("replace-kepala-daerah', { userId: {$this->walikota->id} }");

        Livewire::actingAs($this->adminSetda)->test(ReplaceKepalaDaerahModal::class)
            ->call('open', $this->walikota->id)
            ->assertSet('showOfficialModal', true)
            ->assertSee('Siapa yang menggantikan')
            ->assertSee('Calon Walikota')
            ->call('pickOfficialCandidate', $calon->id)
            // Jabatan Walikota pejabat lama langsung disarankan untuk dialihkan.
            ->assertSet('officialStep', 2)
            ->assertSet('officialPositionId', $posWalikota->id)
            ->call('officialNext')
            ->assertSee('Pejabat lama')
            ->call('confirmOfficial')
            ->assertSet('showOfficialModal', false)
            ->assertDispatched('kepala-daerah-replaced');

        $calon->refresh();
        $this->assertTrue($calon->hasRole('walikota'));
        $this->assertSame($posWalikota->id, $calon->position_id);

        // Akun lama tidak diganti namanya: menjadi Staf tanpa jabatan, riwayatnya tetap atas namanya.
        $lama = $this->walikota->fresh();
        $this->assertSame('Ibu Walikota', $lama->name);
        $this->assertTrue($lama->hasRole('staf'));
        $this->assertNull($lama->position_id);
    }

    public function test_admin_opd_lain_tidak_bisa_mengganti_walikota(): void
    {
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);

        Livewire::actingAs($this->user('admin_opd', [], $dinas))->test(ReplaceKepalaDaerahModal::class)
            ->call('open', $this->walikota->id)
            ->assertSet('showOfficialModal', false);

        // Panggilan langsung ke langkah akhir pun ditolak.
        Livewire::actingAs($this->user('admin_opd', [], $dinas))->test(ReplaceKepalaDaerahModal::class)
            ->set('officialCandidateId', $this->staf->id)
            ->call('confirmOfficial');
        $this->assertTrue($this->walikota->fresh()->hasRole('walikota'));
    }
}
