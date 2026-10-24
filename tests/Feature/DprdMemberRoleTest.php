<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\DprdJabatan;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tanpa kolom tipe pegawai: Anggota DPRD dikenali dari role anggota/pimpinan DPRD.
 */
class DprdMemberRoleTest extends TestCase
{
    use RefreshDatabase;

    private Department $setwan;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'staf', 'anggota_dprd', 'pimpinan_dprd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->setwan = Department::create(['name' => 'Sekretariat DPRD', 'type' => DepartmentType::DPRD, 'level' => 1]);
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(['department_id' => $this->setwan->id, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    public function test_kolom_tipe_pegawai_sudah_dihapus(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'employee_type'));
    }

    public function test_anggota_dprd_dikenali_dari_role(): void
    {
        $this->assertTrue($this->user('anggota_dprd')->isDprdMember());
        $this->assertTrue($this->user('pimpinan_dprd')->isDprdMember());
        $this->assertFalse($this->user('staf')->isDprdMember());
    }

    public function test_form_menu_dprd_tanpa_bagian_kewenangan_dan_role_dari_jabatan(): void
    {
        Department::create(['name' => 'Dinas Pendidikan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->actingAs($this->user('super_admin'));

        // Super Admin pun tidak memilih unit kerja/role: Sekretariat DPRD & role dari Jabatan DPRD.
        Livewire::withQueryParams(['type' => 'dprd'])->test(UserForm::class)
            ->assertSet('department_id', $this->setwan->id)
            ->assertDontSeeHtml('Penempatan & Kewenangan')
            ->assertSet('role', 'anggota_dprd')
            ->set('dprd_jabatan', DprdJabatan::KETUA->value)
            ->assertSet('role', 'pimpinan_dprd')
            // Manipulasi request: role tetap mengikuti Jabatan DPRD.
            ->set('role', 'anggota_dprd')
            ->assertSet('role', 'pimpinan_dprd')
            ->set('dprd_jabatan', DprdJabatan::WAKIL_1->value)
            ->assertSet('role', 'anggota_dprd');

        // Menu pegawai biasa tetap menampilkan unit kerja & role.
        Livewire::withQueryParams([])->test(UserForm::class)
            ->assertSet('department_id', '')
            ->assertSeeHtml('Penempatan & Kewenangan');
    }

    public function test_form_menu_dprd_menyimpan_anggota_dengan_role_dprd(): void
    {
        $this->actingAs($this->user('super_admin'));

        // Dari menu Anggota DPRD: field khusus DPRD tampil tanpa memilih tipe pegawai.
        Livewire::withQueryParams(['type' => 'dprd'])->test(UserForm::class)
            ->assertSet('listType', 'dprd')
            ->set('name', 'Anggota Komisi Uji')
            ->set('username', 'anggota.komisi')
            ->set('department_id', $this->setwan->id)
            ->set('dprd_jabatan', DprdJabatan::KOMISI_1->value)
            ->assertSet('role', 'anggota_dprd')
            ->call('save')
            ->assertHasNoErrors();

        $anggota = User::where('username', 'anggota.komisi')->firstOrFail();
        $this->assertTrue($anggota->isDprdMember());
        $this->assertSame(DprdJabatan::KOMISI_1->value, $anggota->dprd_jabatan);
    }
}
