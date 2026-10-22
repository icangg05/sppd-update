<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\Sppd\SppdShow;
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
 * Nomor dokumen hanya dipakai Inspektorat (diinput di form SPPD): keterangan
 * "belum bernomor" tidak tampil untuk SPPD instansi lain.
 */
class DocumentNumberDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Filter tahun Daftar SPPD memakai YEAR() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        foreach (['staf', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
    }

    private function sppd(string $instansi): SppdRequest
    {
        $department = Department::create(['name' => $instansi, 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $department->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
        ]);
    }

    public function test_keterangan_belum_bernomor_hanya_untuk_inspektorat(): void
    {
        $dinas = $this->sppd('Dinas Perhubungan');

        Livewire::actingAs($this->superAdmin)->test(SppdIndex::class)->assertDontSee('Belum bernomor');
        Livewire::actingAs($this->superAdmin)->test(SppdShow::class, ['sppd' => $dinas])->assertDontSee('Belum memiliki nomor dokumen');

        $inspektorat = $this->sppd('Inspektorat');

        Livewire::actingAs($this->superAdmin)->test(SppdIndex::class)->assertSee('Belum bernomor');
        Livewire::actingAs($this->superAdmin)->test(SppdShow::class, ['sppd' => $inspektorat])->assertSee('Belum memiliki nomor dokumen');

        // Nomor yang sudah terisi tetap tampil.
        $inspektorat->update(['document_number' => '090/12/ST/INSP./2026']);
        Livewire::actingAs($this->superAdmin)->test(SppdShow::class, ['sppd' => $inspektorat])->assertSee('090/12/ST/INSP./2026');
    }
}
