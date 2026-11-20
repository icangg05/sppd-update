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
 * Filter jabatan Camat & Sekcam, Lurah, dan Kepala Puskesmas di Daftar SPPD: role khusus unitnya,
 * atau data lama ber-role Kepala/Sekretaris OPD yang disaring menurut tipe unit. Pegawai
 * Kelurahan (anak Kecamatan) dan Puskesmas (anak Dinkes) mendapat tab unitnya sendiri.
 */
class SppdJabatanFilterTest extends TestCase
{
    use RefreshDatabase;

    private Department $kecamatan;

    private Department $kelurahan;

    private Department $puskesmas;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar SPPD memakai YEAR() MySQL (pilihan filter tahun); padanannya untuk SQLite.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        foreach (['super_admin', 'admin_opd', 'kepala_opd', 'sekretaris_opd', 'kasubid_kasubag', 'kabid_irban_kabag', 'staf', 'camat', 'sekcam', 'lurah', 'seklur', 'kapus'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $dinkes = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::DINKES, 'level' => 1]);
        $this->puskesmas = Department::create(['name' => 'Puskesmas Puuwatu', 'type' => DepartmentType::PUSKESMAS, 'level' => 2, 'parent_id' => $dinkes->id]);
        $this->kecamatan = Department::create(['name' => 'Kecamatan Puuwatu', 'type' => DepartmentType::KECAMATAN, 'level' => 1]);
        $this->kelurahan = Department::create(['name' => 'Kelurahan Watulondo', 'type' => DepartmentType::KELURAHAN, 'level' => 2, 'parent_id' => $this->kecamatan->id]);

        $this->sppd($dinkes, 'kepala_opd', 'Kadis');
        $this->sppd($this->puskesmas, 'kapus', 'Kapus');
        $this->sppd($this->puskesmas, 'staf', 'Staf Puskesmas');
        $this->sppd($this->kecamatan, 'camat', 'Camat');
        $this->sppd($this->kecamatan, 'sekcam', 'Sekcam');
        // Data lama sebelum role khusus: Sekcam masih memegang Sekretaris OPD.
        $this->sppd($this->kecamatan, 'sekretaris_opd', 'Sekcam Lama');
        $this->sppd($this->kecamatan, 'staf', 'Staf Kecamatan');
        $this->sppd($this->kelurahan, 'lurah', 'Lurah');
        $this->sppd($this->kelurahan, 'seklur', 'Seklur');
    }

    private function sppd(Department $department, string $role, string $name): void
    {
        $pelaksana = User::factory()->create(['name' => $name, 'department_id' => $department->id]);
        $pelaksana->assignRole($role);
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::firstOrCreate(['name' => 'Koordinasi'])->id,
            'purpose' => 'Perjalanan ' . $name,
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
        ]);
    }

    private function admin(Department $department): User
    {
        $admin = User::factory()->create(['department_id' => $department->id]);
        $admin->assignRole('admin_opd');

        return $admin;
    }

    /** @return list<string> Nama pelaksana yang tampil di daftar dengan filter jabatan $jabatan. */
    private function pelaksana(User $user, string $jabatan): array
    {
        return Livewire::actingAs($user)->test(SppdIndex::class)->call('filterByJabatan', $jabatan)
            ->viewData('sppds')->getCollection()->pluck('user.name')->sort()->values()->all();
    }

    public function test_super_admin_hanya_mendapat_jabatan_yang_dipilih(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->assertSame(['Camat', 'Sekcam', 'Sekcam Lama'], $this->pelaksana($superAdmin, 'camat'));
        $this->assertSame(['Lurah'], $this->pelaksana($superAdmin, 'lurah'));
        $this->assertSame(['Kapus'], $this->pelaksana($superAdmin, 'kapus'));
    }

    public function test_kecamatan_sekcam_masuk_tab_camat_bukan_eselon_staf(): void
    {
        $admin = $this->admin($this->kecamatan);

        Livewire::actingAs($admin)->test(SppdIndex::class)->assertViewHas('jabatanTabs', ['camat', 'eselon_staf']);
        $this->assertSame(['Camat', 'Sekcam', 'Sekcam Lama'], $this->pelaksana($admin, 'camat'));
        $this->assertSame(['Staf Kecamatan'], $this->pelaksana($admin, 'eselon_staf'));
    }

    public function test_kelurahan_memakai_tab_lurah_bukan_tab_kecamatan_induknya(): void
    {
        $admin = $this->admin($this->kelurahan);

        Livewire::actingAs($admin)->test(SppdIndex::class)->assertViewHas('jabatanTabs', ['lurah', 'eselon_staf']);
        $this->assertSame(['Lurah'], $this->pelaksana($admin, 'lurah'));
        $this->assertSame(['Seklur'], $this->pelaksana($admin, 'eselon_staf'));
    }

    public function test_puskesmas_memakai_tab_kepala_puskesmas(): void
    {
        $admin = $this->admin($this->puskesmas);

        Livewire::actingAs($admin)->test(SppdIndex::class)->assertViewHas('jabatanTabs', ['kapus', 'eselon_staf']);
        $this->assertSame(['Kapus'], $this->pelaksana($admin, 'kapus'));
        $this->assertSame(['Staf Puskesmas'], $this->pelaksana($admin, 'eselon_staf'));
    }
}
