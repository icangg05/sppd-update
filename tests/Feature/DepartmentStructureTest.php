<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\DepartmentForm;
use App\Livewire\DepartmentIndex;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman struktur unit kerja (Super Admin): pindah unit lewat seret-lepas
 * (dikonfirmasi modal). Admin OPD hanya melihat profil instansinya dan sub-unitnya.
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

    public function test_admin_opd_diarahkan_ke_profil_instansinya_dan_tidak_bisa_menyusun(): void
    {
        $dinas  = $this->dept('Dinas A');
        $bidang = $this->dept('Bidang Program', $dinas);
        $admin  = $this->user('admin_opd', $dinas);

        Livewire::actingAs($admin)->test(DepartmentIndex::class)
            ->assertRedirect(route('master.departments.show', $dinas->id));

        // Menambah unit hanya oleh Super Admin.
        $this->actingAs($admin)->get(route('master.departments.create'))->assertForbidden();

        // Menyusun juga, walau aksinya dipanggil langsung dari halaman yang sudah terbuka.
        $page = Livewire::actingAs($this->user('super_admin'))->test(DepartmentIndex::class);
        $this->actingAs($admin);
        $page->call('saveStructure', $this->nodes([$bidang->id => [null, 0]]))->assertForbidden();
        $this->assertSame($dinas->id, $bidang->fresh()->parent_id);
    }

    public function test_profil_instansi_menampilkan_sub_unit_dalam_lingkup_admin(): void
    {
        $setda   = $this->dept('Sekretariat Daerah', null, DepartmentType::SETDA);
        $asisten = $this->dept('Asisten Administrasi Umum', $setda, DepartmentType::ASISTEN);
        $bagian  = $this->dept('Bagian Umum', $asisten, DepartmentType::BAGIAN);
        $sub     = $this->dept('Subbagian Rumah Tangga', $bagian, DepartmentType::SUBBAGIAN);
        $kecamatan = $this->dept('Kecamatan Kadia', null, DepartmentType::KECAMATAN);
        $kelurahan = $this->dept('Kelurahan Kadia', $kecamatan, DepartmentType::KELURAHAN);

        // Admin Setda menjangkau Asisten sampai Subbagian; tombol Kembali ke unit induk.
        $this->actingAs($this->user('admin_opd', $setda));
        $this->get(route('master.departments.show', $setda->id))->assertOk()->assertSee('Asisten Administrasi Umum');
        $this->get(route('master.departments.show', $sub->id))->assertOk()
            ->assertSee(route('master.departments.show', $bagian->id), false)
            ->assertSee('Kode Unit Kerja');
        $this->get(route('master.departments.show', $kecamatan->id))->assertForbidden();

        // Admin Bagian menjangkau Subbagian-nya, tidak ke atas.
        $this->actingAs($this->user('admin_opd', $bagian));
        $this->get(route('master.departments.show', $sub->id))->assertOk();
        $this->get(route('master.departments.show', $asisten->id))->assertForbidden();

        // Kelurahan tampil di profil Kecamatan, tetapi dikelola admin kelurahannya sendiri.
        $this->actingAs($this->user('admin_opd', $kecamatan));
        $this->get(route('master.departments.show', $kecamatan->id))->assertOk()
            ->assertSee('Kelurahan Kadia')->assertSee('Dikelola admin unit ini');
        $this->get(route('master.departments.show', $kelurahan->id))->assertForbidden();
    }

    public function test_admin_opd_tidak_bisa_memindah_atau_mengganti_nama_unit(): void
    {
        $dinas  = $this->dept('Dinas A');
        $lain   = $this->dept('Dinas Lain');
        $bidang = $this->dept('Bidang Program', $dinas);

        Livewire::actingAs($this->user('admin_opd', $dinas))
            ->test(DepartmentForm::class, ['department' => $bidang])
            ->set('name', 'Bidang Baru')
            ->set('parent_id', $lain->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Bidang Program', $bidang->fresh()->name);
        $this->assertSame($dinas->id, $bidang->fresh()->parent_id);
    }

    public function test_hapus_sub_unit_memindah_pegawai_ke_unit_induk(): void
    {
        $dinas  = $this->dept('Dinas A');
        $bidang = $this->dept('Bidang Program', $dinas);
        $staf   = $this->user('staf', $bidang);
        $admin  = $this->user('admin_opd', $this->dept('Bidang Umum', $dinas));

        Livewire::actingAs($this->user('super_admin'))
            ->test(DepartmentIndex::class)
            ->call('confirmDelete', $bidang->id)
            ->call('delete');

        $this->assertNull(Department::find($bidang->id));
        $this->assertSame($dinas->id, $staf->fresh()->department_id);

        // Unit yang punya akun Admin OPD ditolak: lingkup admin akan melebar ke unit induk.
        $this->assertStringContainsString('Admin OPD', $admin->department->deletionError());
    }
}
