<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Daftar Pengeluaran Riil selalu memakai kop instansi pelaksana (diwarisi dari unit induk bila
 * unitnya tak punya kop), siapa pun penyetujunya. Kop Garuda tidak pernah dipakai di dokumen ini.
 */
class PengeluaranRiilKopTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'staf', 'walikota'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->grantDefaultPermissions();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super_admin');
    }

    /** SPPD disetujui penuh dengan Walikota sebagai penyetuju tahap terakhir. */
    private function sppdDisetujuiWalikota(Department $unit): SppdRequest
    {
        $staf = User::factory()->create(['department_id' => $unit->id, 'name' => 'Staf Pelaksana']);
        $staf->assignRole('staf');
        $walikota = User::factory()->create(['department_id' => $unit->getRootDepartment()->id, 'name' => 'Walikota']);
        $walikota->assignRole('walikota');

        $budget = new Budget(['department_id' => $unit->getRootDepartment()->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'domain' => 'lddp',
            'status' => SppdStatus::APPROVED,
        ]);
        SppdApproval::create(['sppd_request_id' => $sppd->id, 'approver_id' => $walikota->id, 'role_label' => 'Walikota',
            'step_order' => 1, 'status' => 'approved']);

        return $sppd;
    }

    /** Stream PDF lalu kembalikan HTML kop (blok header) dan pdfData yang dipakai. */
    private function renderHeader(SppdRequest $sppd): array
    {
        $captured = null;
        View::composer('exports.pengeluaran_riil', function ($view) use (&$captured) {
            $captured ??= $view->getData();
        });

        $this->actingAs($this->admin)->get(route('sppd.stream.pengeluaran-riil', $sppd))->assertOk();
        $this->assertNotNull($captured);

        $html = view('exports.pengeluaran_riil', $captured)->render();
        $header = substr($html, strpos($html, '<div class="header"'), strpos($html, 'DAFTAR PENGELUARAN RIIL') - strpos($html, '<div class="header"'));

        return [$header, $captured['pdfData']];
    }

    public function test_unit_tanpa_kop_memakai_kop_induk_meski_penyetujunya_walikota(): void
    {
        $dinas = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::OPD, 'level' => 1,
            'letterhead' => 'kop-surat/3_primary.webp']);
        $bagian = Department::create(['name' => 'Bagian Umum', 'type' => DepartmentType::OPD, 'level' => 2,
            'parent_id' => $dinas->id]);

        [$header, $pdfData] = $this->renderHeader($this->sppdDisetujuiWalikota($bagian));

        $this->assertSame('kop-surat/3_primary.webp', $pdfData['letterhead_url']);
        $this->assertArrayNotHasKey('is_walikota', $pdfData);
        $this->assertStringContainsString('kop-surat/3_primary.webp', $header);
        $this->assertStringNotContainsString('garuda', $header);
        $this->assertStringNotContainsString('WALIKOTA KENDARI', $header);
    }

    public function test_instansi_tanpa_kop_sama_sekali_memakai_kop_teks_instansi(): void
    {
        $sekolah = Department::create(['name' => 'SMKN 1 Kendari', 'type' => DepartmentType::OPD, 'level' => 1]);

        [$header, $pdfData] = $this->renderHeader($this->sppdDisetujuiWalikota($sekolah));

        $this->assertNull($pdfData['letterhead_url']);
        $this->assertStringContainsString('PEMERINTAH KOTA KENDARI', $header);
        $this->assertStringContainsString('SMKN 1 Kendari', $header);
        $this->assertStringNotContainsString('garuda', $header);
        $this->assertStringNotContainsString('WALIKOTA KENDARI', $header);
    }
}
