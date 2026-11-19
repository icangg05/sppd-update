<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdShow;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Di bawah nama pelaksana pada detail SPPD hanya satu keterangan, dengan prioritas:
 * NIP → jabatan ("Anggota DPRD" bagi anggota DPRD) → label role.
 */
class SppdShowPelaksanaLabelTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['staf' => 'Staf', 'anggota_dprd' => 'Anggota DPRD', 'super_admin' => 'Super Admin'] as $name => $label) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['label' => $label]);
        }
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_nip_diutamakan_dan_jabatan_tidak_ditampilkan(): void
    {
        $department = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $position = Position::create(['name' => 'Pengelola Keuangan', 'default_role' => 'staf']);
        $staf = User::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => '199001012020121001',
        ]);
        $staf->assignRole('staf');

        Livewire::actingAs($this->superAdmin)
            ->test(SppdShow::class, ['sppd' => $this->makeSppd($staf, $department)])
            ->assertSeeInOrder([$staf->name, '199001012020121001', 'Tanggal pelaksanaan'])
            ->assertDontSee('Pengelola Keuangan');
    }

    public function test_anggota_dprd_tanpa_nip_diberi_keterangan_anggota_dprd(): void
    {
        $department = Department::create(['name' => 'Sekretariat DPRD', 'type' => DepartmentType::DPRD, 'level' => 1]);
        $anggota = User::factory()->create(['department_id' => $department->id, 'nip' => null]);
        $anggota->assignRole('anggota_dprd');

        Livewire::actingAs($this->superAdmin)
            ->test(SppdShow::class, ['sppd' => $this->makeSppd($anggota, $department)])
            ->assertSeeInOrder([$anggota->name, 'Anggota DPRD', 'Tanggal pelaksanaan']);
    }

    public function test_tanpa_nip_menampilkan_jabatan(): void
    {
        $department = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $position = Position::create(['name' => 'Pengelola Keuangan', 'default_role' => 'staf']);
        $staf = User::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'nip' => null,
        ]);
        $staf->assignRole('staf');

        Livewire::actingAs($this->superAdmin)
            ->test(SppdShow::class, ['sppd' => $this->makeSppd($staf, $department)])
            ->assertSeeInOrder([$staf->name, 'Pengelola Keuangan', 'Tanggal pelaksanaan']);
    }

    public function test_tanpa_nip_dan_jabatan_memakai_label_role(): void
    {
        $department = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $department->id, 'position_id' => null, 'nip' => null]);
        $staf->assignRole('staf');

        Livewire::actingAs($this->superAdmin)
            ->test(SppdShow::class, ['sppd' => $this->makeSppd($staf, $department)])
            ->assertSeeInOrder([$staf->name, $staf->primaryRoleLabel(), 'Tanggal pelaksanaan']);
    }

    private function makeSppd(User $pelaksana, Department $department): SppdRequest
    {
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();
        $start = today()->next('Monday');

        return SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => $start,
            'end_date' => $start->copy()->addDays(2),
            'domain' => 'lddp',
        ]);
    }
}
