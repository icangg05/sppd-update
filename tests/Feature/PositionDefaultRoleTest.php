<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\PositionScope;
use App\Livewire\PositionFormModal;
use App\Livewire\PositionIndex;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionDefaultRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'kepala_opd', 'kabid_irban_kabag', 'staf'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
    }

    private function makeDepartment(string $name): Department
    {
        return Department::create([
            'name' => $name,
            'type' => DepartmentType::cases()[0],
            'level' => 1,
        ]);
    }

    private function makeUser(string $role, ?Department $department = null): User
    {
        $user = User::factory()->create(['department_id' => $department?->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_role_default_ditebak_dari_nama_jabatan(): void
    {
        $cases = [
            'Kepala Dinas Kesehatan' => 'kepala_opd',
            'Inspektur' => 'kepala_opd',
            'Sekretaris Dinas' => 'sekretaris_opd',
            'Kepala Bidang Anggaran' => 'kabid_irban_kabag',
            'Kepala Sub Bagian Umum dan Kepegawaian' => 'kasubid_kasubag',
            'Kepala Seksi Irigasi' => 'kasubid_kasubag',
            'Camat' => 'camat',
            'Kepala UPTD Puskesmas' => 'kapus',
            'Asisten Administrasi Umum' => 'asisten',
            'Asisten Apoteker' => 'staf',
            'Pranata Komputer' => 'staf',
            'Kepala UPTD Terminal' => null,
        ];

        foreach ($cases as $name => $expected) {
            $this->assertSame($expected, Position::guessDefaultRole($name), $name);
        }
    }

    public function test_admin_opd_menambah_jabatan_dengan_tingkat_tertebak(): void
    {
        $admin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Kesehatan'));

        Livewire::actingAs($admin)
            ->test(PositionFormModal::class)
            ->call('open')
            ->set('name', 'Kepala Bidang Pengawasan')
            ->assertSet('tier', 'kabid')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('position-saved');

        $position = Position::where('name', 'Kepala Bidang Pengawasan')->firstOrFail();
        $this->assertSame('kabid_irban_kabag', $position->default_role);
        $this->assertSame(PositionScope::DEPARTMENT, $position->uniqueness_scope);
        $this->assertSame($admin->id, $position->created_by);
    }

    public function test_nama_jabatan_duplikat_ditolak(): void
    {
        Position::create(['name' => 'Staf']);
        $admin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Sosial'));

        Livewire::actingAs($admin)
            ->test(PositionFormModal::class)
            ->call('open')
            ->set('name', 'staf')
            ->call('save')
            ->assertHasErrors('name');

        $this->assertSame(1, Position::count());
    }

    public function test_admin_opd_tidak_bisa_mengubah_jabatan_opd_lain_atau_bawaan(): void
    {
        $other = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Pangan'));
        $admin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Sosial'));
        $colleague = $this->makeUser('staf', $admin->department);

        $foreign = Position::create(['name' => 'Analis Pangan', 'created_by' => $other->id]);
        $system = Position::create(['name' => 'Kepala Dinas']);
        $own = Position::create(['name' => 'Analis Sosial', 'created_by' => $colleague->id]);

        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', $foreign->id)->assertForbidden();
        Livewire::actingAs($admin)->test(PositionIndex::class)
            ->call('confirmDelete', $system->id)->assertForbidden();

        // Jabatan tambahan rekan satu OPD tetap bisa dikelola.
        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', $own->id)
            ->assertSet('show', true);
    }

    public function test_memilih_jabatan_mengisi_role_tanpa_menimpa_admin_opd(): void
    {
        $admin = $this->makeUser('super_admin');
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);
        $staf = Position::create(['name' => 'Staf', 'default_role' => 'staf']);

        $form = Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->call('selectPosition', $kadis->id)
            ->assertSet('role', 'kepala_opd');

        // Role operasional tidak ditimpa otomatis, hanya lewat tombol "Gunakan role ini".
        $form->set('role', 'admin_opd')
            ->call('selectPosition', $staf->id)
            ->assertSet('role', 'admin_opd')
            ->call('applyPositionRole')
            ->assertSet('role', 'staf');
    }

    public function test_admin_opd_tidak_bisa_memilih_role_sendiri(): void
    {
        $department = $this->makeDepartment('Dinas Kesehatan');
        $admin = $this->makeUser('admin_opd', $department);
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->assertSet('role', 'staf')
            ->set('name', 'Budi Santoso')
            ->set('username', 'budi')
            ->set('department_id', $department->id)
            ->call('selectPosition', $kadis->id)
            ->assertSet('role', 'kepala_opd')
            // Manipulasi request: role bebas diabaikan, tetap mengikuti jabatan.
            ->set('role', 'super_admin')
            ->assertSet('role', 'kepala_opd')
            ->call('save')
            ->assertHasNoErrors();

        $budi = User::where('username', 'budi')->firstOrFail();
        $this->assertSame(['kepala_opd'], $budi->getRoleNames()->all());
    }

    public function test_admin_opd_tidak_mengubah_role_admin_opd_lain(): void
    {
        $department = $this->makeDepartment('Dinas Sosial');
        $admin = $this->makeUser('admin_opd', $department);
        $colleague = $this->makeUser('admin_opd', $department);
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => $colleague])
            ->call('selectPosition', $kadis->id)
            ->assertSet('role', 'admin_opd')
            ->call('applyPositionRole')
            ->assertSet('role', 'admin_opd');
    }

    public function test_halaman_data_jabatan_terbuka_untuk_admin_opd_saja(): void
    {
        $department = $this->makeDepartment('Dinas Perikanan');

        $this->actingAs($this->makeUser('admin_opd', $department))
            ->get(route('master.positions.index'))
            ->assertOk();

        $this->actingAs($this->makeUser('staf', $department))
            ->get(route('master.positions.index'))
            ->assertForbidden();
    }
}
