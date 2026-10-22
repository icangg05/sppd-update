<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
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
 * Hapus SPPD di Daftar SPPD: Admin OPD boleh menghapus SPPD apa pun statusnya di
 * lingkup unit kerjanya, setelah hitung mundur konfirmasi selesai.
 */
class SppdDeleteTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Filter tahun Daftar SPPD memakai YEAR() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        foreach (['staf', 'admin_opd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $this->dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->admin = User::factory()->create(['department_id' => $this->dinas->id]);
        $this->admin->assignRole('admin_opd');
    }

    private function sppd(Department $department, SppdStatus $status): SppdRequest
    {
        $staf = User::factory()->create(['department_id' => $department->id]);
        $staf->assignRole('staf');
        $budget = new Budget(['department_id' => $department->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => $status,
        ]);
    }

    public function test_admin_opd_menghapus_sppd_disetujui_setelah_hitung_mundur(): void
    {
        $sppd = $this->sppd($this->dinas, SppdStatus::APPROVED);

        $page = Livewire::actingAs($this->admin)->test(SppdIndex::class)
            ->assertSeeHtml("confirmDelete({$sppd->id})")
            ->call('confirmDelete', $sppd->id)
            ->assertSet('showDeleteModal', true)
            ->assertSee('Dokumen yang sudah dicetak tidak bisa diverifikasi lagi');

        // Sebelum hitung mundur selesai: ditolak, modal tetap terbuka.
        $page->call('deleteSppd')->assertSet('showDeleteModal', true);
        $this->assertNotNull(SppdRequest::find($sppd->id));

        $this->travel(SppdIndex::DELETE_WAIT)->seconds();
        $page->call('deleteSppd')->assertSet('showDeleteModal', false);
        $this->assertNull(SppdRequest::find($sppd->id));
    }

    public function test_admin_opd_tidak_bisa_menghapus_sppd_di_luar_lingkupnya(): void
    {
        $lain = Department::create(['name' => 'Dinas Lain', 'type' => DepartmentType::OPD, 'level' => 1]);
        $sppd = $this->sppd($lain, SppdStatus::APPROVED);

        Livewire::actingAs($this->admin)->test(SppdIndex::class)
            ->call('confirmDelete', $sppd->id)
            ->assertSet('showDeleteModal', false)
            ->assertSet('deleteId', null);

        $this->assertNotNull(SppdRequest::find($sppd->id));
    }
}
