<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\EmployeeType;
use App\Livewire\DepartmentIndex;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman struktur unit kerja: pindah unit lewat seret-lepas (dikonfirmasi
 * modal) dan pemilihan pimpinan langsung dari pohon.
 */
class DepartmentStructureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'kepala_opd', 'staf'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
    }

    private function dept(string $name, ?Department $parent = null, DepartmentType $type = DepartmentType::OPD): Department
    {
        return Department::create([
            'name' => $name,
            'type' => $type,
            'parent_id' => $parent?->id,
            'level' => $parent ? $parent->level + 1 : 1,
            'can_view_children_data' => true,
        ]);
    }

    private function user(string $role, ?Department $department = null): User
    {
        $user = User::factory()->create(['department_id' => $department?->id]);
        $user->assignRole($role);

        return $user;
    }

    /** Payload saveStructure: [id => [induk, urutan]]. */
    private function nodes(array $map): array
    {
        return collect($map)->map(fn($v, $id) => ['id' => $id, 'parent' => $v[0], 'order' => $v[1]])->values()->all();
    }

    public function test_simpan_struktur_memindah_mengurutkan_dan_menyesuaikan_level(): void
    {
        $dinasA  = $this->dept('Dinas A');
        $dinasB  = $this->dept('Dinas B');
        $bidang  = $this->dept('Bidang Program', $dinasA);
        $bidang2 = $this->dept('Bidang Umum', $dinasA);
        $seksi   = $this->dept('Seksi Data', $bidang);

        Livewire::actingAs($this->user('super_admin'))
            ->test(DepartmentIndex::class)
            ->call('saveStructure', $this->nodes([
                $dinasA->id  => [null, 1],
                $dinasB->id  => [null, 0],
                $bidang2->id => [$dinasA->id, 0],
                $bidang->id  => [$dinasB->id, 0],
                $seksi->id   => [$bidang->id, 0],
            ]))
            ->assertDispatched('department-tree-updated');

        $this->assertSame($dinasB->id, $bidang->fresh()->parent_id);
        $this->assertSame(2, $bidang->fresh()->level);
        $this->assertSame(3, $seksi->fresh()->level);
        $this->assertSame(1, $dinasA->fresh()->sort_order);
        $this->assertSame(0, $dinasB->fresh()->sort_order);

        // Urutan pohon mengikuti sort_order.
        $rows = Livewire::actingAs($this->user('super_admin'))->test(DepartmentIndex::class)->viewData('rows');
        $this->assertSame([$dinasB->id, $bidang->id, $seksi->id, $dinasA->id, $bidang2->id], array_column($rows, 'id'));

        // Geser keluar sampai menjadi instansi induk.
        Livewire::actingAs($this->user('super_admin'))
            ->test(DepartmentIndex::class)
            ->call('saveStructure', $this->nodes([$bidang->id => [null, 2]]));

        $this->assertNull($bidang->fresh()->parent_id);
        $this->assertSame(1, $bidang->fresh()->level);
        $this->assertSame(2, $seksi->fresh()->level);
    }

    public function test_susunan_ditolak_seluruhnya_bila_ada_siklus_atau_nama_ganda(): void
    {
        $dinas   = $this->dept('Dinas A');
        $bidang  = $this->dept('Bidang Program', $dinas);
        $seksi   = $this->dept('Seksi Data', $bidang);
        $dinasB  = $this->dept('Dinas B');
        $bidangB = $this->dept('Bidang Umum', $dinasB);
        $this->dept('Seksi Data', $dinasB);

        $page = Livewire::actingAs($this->user('super_admin'))->test(DepartmentIndex::class);

        // Masuk ke sub-unitnya sendiri.
        $page->call('saveStructure', $this->nodes([$bidang->id => [$seksi->id, 0], $seksi->id => [$bidang->id, 0]]))
            ->assertNotDispatched('department-tree-updated');

        // Satu pindahan sah + satu nama ganda → tidak ada yang tersimpan.
        $page->call('saveStructure', $this->nodes([$bidangB->id => [$dinas->id, 1], $seksi->id => [$dinasB->id, 1]]))
            ->assertNotDispatched('department-tree-updated');

        $this->assertSame($dinas->id, $bidang->fresh()->parent_id);
        $this->assertSame($bidang->id, $seksi->fresh()->parent_id);
        $this->assertSame($dinasB->id, $bidangB->fresh()->parent_id);
    }

    public function test_admin_opd_hanya_menyusun_di_dalam_lingkupnya(): void
    {
        $dinas   = $this->dept('Dinas A');
        $bidang  = $this->dept('Bidang Program', $dinas);
        $bidang2 = $this->dept('Bidang Umum', $dinas);
        $lain    = $this->dept('Dinas Lain');
        $admin   = $this->user('admin_opd', $dinas);

        $page = Livewire::actingAs($admin)->test(DepartmentIndex::class);

        // Keluar lingkup atau menjadi instansi induk ditolak.
        $page->call('saveStructure', $this->nodes([$bidang->id => [$lain->id, 0]]))->assertNotDispatched('department-tree-updated');
        $page->call('saveStructure', $this->nodes([$bidang->id => [null, 0]]))->assertNotDispatched('department-tree-updated');
        $this->assertSame($dinas->id, $bidang->fresh()->parent_id);

        // Di dalam lingkup boleh; entri instansinya sendiri diabaikan (tidak ikut dipindah).
        $page->call('saveStructure', $this->nodes([
            $dinas->id   => [$lain->id, 0],
            $bidang2->id => [$dinas->id, 0],
            $bidang->id  => [$bidang2->id, 0],
        ]))->assertDispatched('department-tree-updated');

        $this->assertSame($bidang2->id, $bidang->fresh()->parent_id);
        $this->assertNull($dinas->fresh()->parent_id);
    }

    public function test_pohon_admin_opd_hanya_berisi_lingkupnya(): void
    {
        $dinas  = $this->dept('Dinas A');
        $bidang = $this->dept('Bidang Program', $dinas);
        $this->dept('Dinas Lain');

        $rows = Livewire::actingAs($this->user('admin_opd', $dinas))
            ->test(DepartmentIndex::class)
            ->viewData('rows');

        $this->assertSame([$dinas->id, $bidang->id], array_column($rows, 'id'));
        $this->assertFalse($rows[0]['canDrag'], 'instansi sendiri tidak bisa diseret');
        $this->assertTrue($rows[1]['canDrag']);
    }

    public function test_pimpinan_bidang_dipilih_dari_pegawai_lingkup_instansi(): void
    {
        $dinas    = $this->dept('Dinas A');
        $bidang   = $this->dept('Bidang Program', $dinas);
        $lain     = $this->dept('Dinas B');
        // Pegawai bidang sering masih terdaftar di instansi induk.
        $staf     = $this->user('staf', $dinas);
        $kepala   = $this->user('kepala_opd', $dinas);
        $diBidang = $this->user('staf', $bidang);
        $luar     = $this->user('kepala_opd', $lain);
        $operator = $this->user('admin_opd', $dinas);

        $page = Livewire::actingAs($this->user('super_admin'))
            ->test(DepartmentIndex::class)
            ->call('openHeadPicker', $bidang->id)
            ->assertSet('showHeadModal', true)
            // Urutan: pegawai unit ini, pemegang role pimpinan, lalu sisanya — tanpa instansi lain & akun admin.
            ->assertViewHas('headCandidates', fn($c) => $c->pluck('id')->all() === [$diBidang->id, $kepala->id, $staf->id]);

        $page->call('setHead', $luar->id)->call('setHead', $operator->id);
        $this->assertNull($bidang->fresh()->head_id);

        $page->call('setHead', $staf->id)->assertSet('showHeadModal', false);
        $this->assertSame($staf->id, $bidang->fresh()->head_id);
        // Pimpinan ditempatkan di unit yang dipimpinnya.
        $this->assertSame($bidang->id, $staf->fresh()->department_id);

        $page->call('openHeadPicker', $bidang->id)->call('setHead', null);
        $this->assertNull($bidang->fresh()->head_id);
    }

    public function test_unit_paling_atas_tidak_punya_pemilih_pimpinan(): void
    {
        $dinas  = $this->dept('Dinas A');
        $bidang = $this->dept('Bidang Program', $dinas);
        $this->user('kepala_opd', $dinas);

        $page = Livewire::actingAs($this->user('super_admin'))->test(DepartmentIndex::class);
        $page->call('openHeadPicker', $dinas->id)->assertSet('showHeadModal', false);

        $rows = collect($page->viewData('rows'))->keyBy('id');
        $this->assertFalse($rows[$dinas->id]['canHead']);
        $this->assertTrue($rows[$bidang->id]['canHead']);

        // Admin OPD: instansinya sendiri adalah puncak pohonnya.
        Livewire::actingAs($this->user('admin_opd', $dinas))
            ->test(DepartmentIndex::class)
            ->call('openHeadPicker', $dinas->id)
            ->assertSet('showHeadModal', false)
            ->call('openHeadPicker', $bidang->id)
            ->assertSet('showHeadModal', true);
    }

    public function test_admin_opd_tidak_bisa_mengatur_pimpinan_di_luar_lingkup(): void
    {
        $dinas      = $this->dept('Dinas A');
        $lain       = $this->dept('Dinas B');
        $bidangLain = $this->dept('Bidang Lain', $lain);
        $this->user('kepala_opd', $lain);

        Livewire::actingAs($this->user('admin_opd', $dinas))
            ->test(DepartmentIndex::class)
            ->call('openHeadPicker', $bidangLain->id)
            ->assertSet('showHeadModal', false);
    }

    public function test_satu_pegawai_hanya_memimpin_satu_unit(): void
    {
        $dinas   = $this->dept('Dinas A');
        $bidang  = $this->dept('Bidang Program', $dinas);
        $bidang2 = $this->dept('Bidang Umum', $dinas);
        $staf    = $this->user('staf', $dinas);

        $page = Livewire::actingAs($this->user('super_admin'))->test(DepartmentIndex::class);
        $page->call('openHeadPicker', $bidang->id)->call('setHead', $staf->id);
        $page->call('openHeadPicker', $bidang2->id)->call('setHead', $staf->id);

        $this->assertSame($bidang2->id, $staf->fresh()->department_id);
        $this->assertSame($staf->id, $bidang2->fresh()->head_id);
        $this->assertNull($bidang->fresh()->head_id);
    }

    public function test_pimpinan_tidak_dipindah_bila_role_tunggal_bentrok_di_unit_tujuan(): void
    {
        $dinas  = $this->dept('Dinas A');
        $bidang = $this->dept('Bidang Program', $dinas);
        $this->user('kepala_opd', $bidang);
        $kepala = $this->user('kepala_opd', $dinas);

        Livewire::actingAs($this->user('super_admin'))
            ->test(DepartmentIndex::class)
            ->call('openHeadPicker', $bidang->id)
            ->call('setHead', $kepala->id)
            ->assertSet('showHeadModal', true);

        $this->assertSame($dinas->id, $kepala->fresh()->department_id);
        $this->assertNull($bidang->fresh()->head_id);
    }

    public function test_pindah_unit_di_form_pegawai_melepas_posisi_pimpinan(): void
    {
        $dinas  = $this->dept('Dinas A');
        $bidang = $this->dept('Bidang Program', $dinas);
        $staf   = $this->user('staf', $bidang);
        $staf->update(['username' => 'staf.satu']);
        $bidang->update(['head_id' => $staf->id]);

        Livewire::actingAs($this->user('super_admin'))
            ->test(UserForm::class, ['user' => $staf])
            ->assertViewHas('headOfUnits', fn($units) => $units->all() === ['Bidang Program'])
            ->set('employee_type', EmployeeType::cases()[0]->value)
            ->set('department_id', $dinas->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($dinas->id, $staf->fresh()->department_id);
        $this->assertNull($bidang->fresh()->head_id);
    }
}
