<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\WorkflowForm;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SppdWorkflowResolutionTest extends TestCase
{
    use RefreshDatabase;

    private SppdWorkflowService $service;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'super_admin', 'staf', 'kasubid_kasubag', 'kabid_irban_kabag', 'sekretaris_opd', 'kepala_opd',
            'asisten', 'sekda', 'walikota', 'sekwan',
        ] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $this->seed(SppdWorkflowSeeder::class);
        $this->service = app(SppdWorkflowService::class);
    }

    private function dept(string $name, DepartmentType $type, ?Department $parent = null): Department
    {
        return Department::create(['name' => $name, 'type' => $type, 'level' => 1, 'parent_id' => $parent?->id]);
    }

    private function user(string $role, Department $department): User
    {
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole($role);

        return $user;
    }

    private function sppd(User $pelaksana, string $domain = 'dalam_daerah'): SppdRequest
    {
        $budget = new Budget(['department_id' => $pelaksana->department_id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => $domain,
        ]);
    }

    /** @return array<int, int> id approver berurutan */
    private function approverIds(SppdRequest $sppd): array
    {
        return $sppd->approvals()->orderBy('step_order')->pluck('approver_id')->all();
    }

    public function test_opd_datar_dengan_beberapa_kabid_pembuat_sppd_memilih_atasan(): void
    {
        $dinas = $this->dept('Dinas Perhubungan', DepartmentType::OPD);
        $kepala = $this->user('kepala_opd', $dinas);
        $sekretaris = $this->user('sekretaris_opd', $dinas);
        $kabidA = $this->user('kabid_irban_kabag', $dinas);
        $this->user('kabid_irban_kabag', $dinas);
        $staf = $this->user('staf', $dinas);

        $steps = $this->service->simulateApprovals($staf, 'dalam_daerah');
        $this->assertSame(['choose', 'found', 'found'], array_column($steps, 'status'));
        $this->assertTrue($steps[0]['optional']);
        $this->assertCount(2, $steps[0]['candidates']);

        // Tanpa pilihan, pengajuan ditolak.
        try {
            $this->service->generateApprovals($this->sppd($staf));
            $this->fail('Pengajuan tanpa memilih Kabid seharusnya ditolak.');
        } catch (\Exception $e) {
            $this->assertStringContainsString('Pilih pejabat', $e->getMessage());
        }

        $sppd = $this->sppd($staf);
        $this->assertTrue($this->service->generateApprovals($sppd, [$steps[0]['index'] => $kabidA->id]));
        $this->assertSame([$kabidA->id, $sekretaris->id, $kepala->id], $this->approverIds($sppd));
    }

    public function test_staf_sekretariat_dapat_melewati_kabid(): void
    {
        $dinas = $this->dept('Dinas Pertanian', DepartmentType::OPD);
        $kepala = $this->user('kepala_opd', $dinas);
        $sekretaris = $this->user('sekretaris_opd', $dinas);
        // Kabid tunggal di OPD induk belum tentu atasan staf, jadi tetap dikonfirmasi.
        $this->user('kabid_irban_kabag', $dinas);
        $staf = $this->user('staf', $dinas);

        $steps = $this->service->simulateApprovals($staf, 'dalam_daerah');
        $this->assertSame('choose', $steps[0]['status']);

        $sppd = $this->sppd($staf);
        $this->service->generateApprovals($sppd, [$steps[0]['index'] => SppdWorkflowService::SKIP]);
        $this->assertSame([$sekretaris->id, $kepala->id], $this->approverIds($sppd));
    }

    public function test_tanpa_kabid_di_instansi_tahap_kabid_dilewati_otomatis(): void
    {
        $dinas = $this->dept('Badan Kepegawaian', DepartmentType::OPD);
        $kepala = $this->user('kepala_opd', $dinas);
        $sekretaris = $this->user('sekretaris_opd', $dinas);
        $staf = $this->user('staf', $dinas);

        $this->assertSame(['sekretaris_opd', 'kepala_opd'], array_column($this->service->simulateApprovals($staf, 'lddp'), 'role'));

        $sppd = $this->sppd($staf, 'lddp');
        $this->service->generateApprovals($sppd);
        $this->assertSame([$sekretaris->id, $kepala->id], $this->approverIds($sppd));
    }

    public function test_kabid_di_sub_unit_bidang_dipakai_otomatis(): void
    {
        $dinas = $this->dept('Dinas Kominfo', DepartmentType::OPD);
        $bidang = $this->dept('Bidang Aplikasi Informatika', DepartmentType::OPD, $dinas);
        $kepala = $this->user('kepala_opd', $dinas);
        $sekretaris = $this->user('sekretaris_opd', $dinas);
        $kabid = $this->user('kabid_irban_kabag', $bidang);
        $this->user('kabid_irban_kabag', $this->dept('Bidang Statistik', DepartmentType::OPD, $dinas));
        $staf = $this->user('staf', $bidang);

        $sppd = $this->sppd($staf);
        $this->service->generateApprovals($sppd);
        $this->assertSame([$kabid->id, $sekretaris->id, $kepala->id], $this->approverIds($sppd));
    }

    public function test_staf_setda_memilih_kabag_dan_asisten(): void
    {
        $setda = $this->dept('Sekretariat Daerah', DepartmentType::SETDA);
        $asistenUnit = $this->dept('Asisten Administrasi Umum', DepartmentType::ASISTEN, $setda);
        $bagian = $this->dept('Bagian Umum', DepartmentType::BAGIAN, $asistenUnit);
        $sekda = $this->user('sekda', $setda);
        $asisten = $this->user('asisten', $setda);
        $this->user('asisten', $setda);
        $kabag = $this->user('kabid_irban_kabag', $bagian);
        // Staf tercatat langsung di Setda, bukan di Bagian-nya.
        $staf = $this->user('staf', $setda);

        $steps = $this->service->simulateApprovals($staf, 'dalam_daerah');
        $this->assertSame(['choose', 'choose', 'found'], array_column($steps, 'status'));
        $this->assertTrue($steps[0]['optional']);
        $this->assertFalse($steps[1]['optional']);

        $sppd = $this->sppd($staf);
        $this->service->generateApprovals($sppd, [$steps[0]['index'] => $kabag->id, $steps[1]['index'] => $asisten->id]);
        $this->assertSame([$kabag->id, $asisten->id, $sekda->id], $this->approverIds($sppd));

        // Tahap Asisten wajib: tidak bisa dilewati.
        $this->expectException(\Exception::class);
        $this->service->generateApprovals($this->sppd($staf), [$steps[0]['index'] => $kabag->id, $steps[1]['index'] => SppdWorkflowService::SKIP]);
    }

    public function test_staf_puskesmas_langsung_ke_kepala_puskesmas(): void
    {
        $dinkes = $this->dept('Dinas Kesehatan', DepartmentType::DINKES);
        $this->user('kepala_opd', $dinkes);
        $puskesmas = $this->dept('Puskesmas Mokoau', DepartmentType::PUSKESMAS, $dinkes);
        $kapus = $this->user('kepala_opd', $puskesmas);
        $staf = $this->user('staf', $puskesmas);

        $sppd = $this->sppd($staf);
        $this->service->generateApprovals($sppd);
        $this->assertSame([$kapus->id], $this->approverIds($sppd));
    }

    public function test_staf_luar_negeri_diteruskan_ke_sekda_dan_walikota(): void
    {
        $setda = $this->dept('Sekretariat Daerah', DepartmentType::SETDA);
        $sekda = $this->user('sekda', $setda);
        $walikota = $this->user('walikota', $setda);
        $dinas = $this->dept('Dinas Pariwisata', DepartmentType::OPD);
        $kepala = $this->user('kepala_opd', $dinas);
        $sekretaris = $this->user('sekretaris_opd', $dinas);
        $staf = $this->user('staf', $dinas);

        $sppd = $this->sppd($staf, 'luar_negeri');
        $this->service->generateApprovals($sppd);
        $this->assertSame([$sekretaris->id, $kepala->id, $sekda->id, $walikota->id], $this->approverIds($sppd));

        $spt = $sppd->approvals()->where('signs_spt', true)->sole();
        $this->assertSame($walikota->id, $spt->approver_id);
    }

    public function test_form_alur_menolak_kasubag_sebagai_penyetuju_dan_tahap_opsional_bertanda_tangan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $form = fn (array $steps) => Livewire::test(WorkflowForm::class)
            ->set('name', 'Uji')
            ->set('department_type', ['opd'])
            ->set('applicant_role', ['staf'])
            ->set('destination', ['dalam_daerah'])
            ->set('steps', $steps)
            ->call('save');

        $form([
            ['role' => 'kasubid_kasubag', 'signs_spt' => false, 'signs_sppd' => false, 'optional' => false],
            ['role' => 'kepala_opd', 'signs_spt' => true, 'signs_sppd' => true, 'optional' => false],
        ])->assertHasErrors(['steps.0.role' => 'in']);

        $form([
            ['role' => 'kabid_irban_kabag', 'signs_spt' => true, 'signs_sppd' => false, 'optional' => true],
            ['role' => 'kepala_opd', 'signs_spt' => false, 'signs_sppd' => true, 'optional' => false],
        ])->assertNoRedirect();

        $this->assertDatabaseMissing('sppd_workflows', ['name' => 'Uji']);
    }
}
