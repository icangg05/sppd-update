<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdIndex;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Filter Instansi di Daftar SPPD: menyaring menurut unit kerja pelaksana beserta sub-unitnya,
 * kecuali zona mandiri (Puskesmas dst.) yang punya pilihan sendiri. Super Admin memilih dari
 * semua instansi; selain itu hanya unit dalam zona datanya.
 */
class SppdInstansiFilterTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinkes;

    private Department $bidang;

    private Department $puskesmas;

    private Department $dinsos;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar SPPD memakai YEAR() MySQL (pilihan filter tahun); padanannya untuk SQLite.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        foreach (['staf', 'admin_opd', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $this->dinkes = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::DINKES, 'level' => 1, 'can_view_children_data' => true]);
        $this->bidang = Department::create(['name' => 'Bidang P2P', 'type' => DepartmentType::OPD, 'level' => 2, 'parent_id' => $this->dinkes->id]);
        $this->puskesmas = Department::create(['name' => 'Puskesmas Puuwatu', 'type' => DepartmentType::PUSKESMAS, 'level' => 2, 'parent_id' => $this->dinkes->id]);
        $this->dinsos = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);

        foreach ([$this->dinkes, $this->bidang, $this->puskesmas, $this->dinsos] as $department) {
            $this->sppd($department);
        }
    }

    private function sppd(Department $department): SppdRequest
    {
        $staf = User::factory()->create(['department_id' => $department->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::firstOrCreate(['name' => 'Koordinasi'])->id,
            'purpose' => 'Perjalanan ' . $department->name,
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
        ]);
    }

    /** @return list<string> Maksud perjalanan yang tampil di daftar. */
    private function purposes($component): array
    {
        return $component->viewData('sppds')->getCollection()->pluck('purpose')->sort()->values()->all();
    }

    public function test_super_admin_menyaring_per_instansi_beserta_sub_unit_tanpa_zona_mandiri(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $component = Livewire::actingAs($superAdmin)->test(SppdIndex::class);
        $this->assertCount(4, $this->purposes($component));

        $labels = collect($component->viewData('instansiOptions'))->pluck('label')->all();
        $this->assertSame('Semua Instansi', $labels[0]);
        $this->assertContains('— Bidang P2P', $labels);
        $this->assertContains('Dinas Sosial', $labels);

        $component->set('instansi', (string) $this->dinkes->id);
        $this->assertSame(['Perjalanan Bidang P2P', 'Perjalanan Dinas Kesehatan'], $this->purposes($component));

        $component->set('instansi', (string) $this->puskesmas->id);
        $this->assertSame(['Perjalanan Puskesmas Puuwatu'], $this->purposes($component));

        $component->call('resetFilters')->assertSet('instansi', '');
        $this->assertCount(4, $this->purposes($component));
    }

    public function test_hanya_instansi_yang_punya_data_perjalanan_yang_ditawarkan(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Tanpa SPPD sama sekali: tidak ditawarkan.
        Department::create(['name' => 'Dinas Pariwisata', 'type' => DepartmentType::OPD, 'level' => 1]);
        // Induk tanpa SPPD sendiri tetap ditawarkan bila sub-unitnya punya, karena ikut tersaring.
        $dinasPu = Department::create(['name' => 'Dinas PU', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->sppd(Department::create(['name' => 'Bidang Jalan', 'type' => DepartmentType::OPD, 'level' => 2, 'parent_id' => $dinasPu->id]));
        // Kecamatan tanpa SPPD tidak ditawarkan; Kelurahannya tampil tanpa menjorok.
        $kecamatan = Department::create(['name' => 'Kecamatan Puuwatu', 'type' => DepartmentType::KECAMATAN, 'level' => 1]);
        $this->sppd(Department::create(['name' => 'Kelurahan Watulondo', 'type' => DepartmentType::KELURAHAN, 'level' => 2, 'parent_id' => $kecamatan->id]));

        $labels = collect(Livewire::actingAs($superAdmin)->test(SppdIndex::class)->viewData('instansiOptions'))->pluck('label')->all();

        $this->assertNotContains('Dinas Pariwisata', $labels);
        $this->assertContains('Dinas PU', $labels);
        $this->assertContains('— Bidang Jalan', $labels);
        $this->assertNotContains('Kecamatan Puuwatu', $labels);
        $this->assertContains('Kelurahan Watulondo', $labels);

        // Unit terpilih (mis. dari URL) tetap tampil walau tanpa data, agar pilihannya terlihat.
        $pariwisata = Department::where('name', 'Dinas Pariwisata')->first();
        $labels = collect(Livewire::withQueryParams(['instansi' => (string) $pariwisata->id])->actingAs($superAdmin)
            ->test(SppdIndex::class)->viewData('instansiOptions'))->pluck('label')->all();
        $this->assertContains('Dinas Pariwisata', $labels);
    }

    public function test_admin_opd_hanya_memilih_unit_dalam_zonanya(): void
    {
        $admin = User::factory()->create(['department_id' => $this->dinkes->id]);
        $admin->assignRole('admin_opd');

        $component = Livewire::actingAs($admin)->test(SppdIndex::class);
        $this->assertSame(
            ['Semua Instansi', 'Dinas Kesehatan', '— Bidang P2P'],
            collect($component->viewData('instansiOptions'))->pluck('label')->all(),
        );

        $component->set('instansi', (string) $this->bidang->id);
        $this->assertSame(['Perjalanan Bidang P2P'], $this->purposes($component));

        // Unit di luar zona lewat URL tidak membuka data instansi lain.
        $component->set('instansi', (string) $this->dinsos->id);
        $this->assertSame([], $this->purposes($component));
    }

    public function test_filter_disembunyikan_bila_hanya_satu_unit(): void
    {
        $admin = User::factory()->create(['department_id' => $this->dinsos->id]);
        $admin->assignRole('admin_opd');

        Livewire::actingAs($admin)->test(SppdIndex::class)
            ->assertViewHas('instansiOptions', [])
            ->assertDontSee('Semua Instansi');
    }
}
