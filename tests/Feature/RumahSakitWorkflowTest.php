<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdCreate;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\UserForm;
use App\Livewire\UsersIndex;
use App\Livewire\WorkflowForm;
use App\Livewire\WorkflowIndex;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alur Rumah Sakit (tipe unit kerja rumah_sakit): Sekretaris → Direktur; Direktur ke luar
 * daerah lanjut ke Kepala Dinas Kesehatan (tahap eksternal kepala_opd@dinkes) yang
 * menandatangani SPT, sedangkan Direktur menandatangani SPPD.
 */
class RumahSakitWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private SppdWorkflowService $service;
    private Department $dinkes;
    private Department $rsud;
    private User $kadis;
    private User $direktur;
    private User $sekretaris;
    private User $staf;

    protected function setUp(): void
    {
        parent::setUp();

        // Daftar SPPD & Data Pegawai memakai YEAR()/FIELD() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
            $pdo->sqliteCreateFunction('FIELD', function ($value, ...$list) {
                $index = array_search($value, $list);

                return $index === false ? 0 : $index + 1;
            });
        }

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SppdWorkflowSeeder::class);
        $this->service = app(SppdWorkflowService::class);

        $this->dinkes = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::DINKES, 'level' => 1]);
        $this->rsud = Department::create(['name' => 'Rumah Sakit Umum Daerah Kota Kendari', 'type' => DepartmentType::RUMAH_SAKIT, 'level' => 1]);

        $this->kadis = $this->user('kepala_opd', $this->dinkes);
        $this->direktur = $this->user('kepala_opd', $this->rsud);
        $this->sekretaris = $this->user('sekretaris_opd', $this->rsud);
        $this->staf = $this->user('staf', $this->rsud);
    }

    private function user(string $role, Department $department): User
    {
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole($role);

        return $user;
    }

    private function sppd(User $pelaksana, string $domain): SppdRequest
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

    /** @return array<int, array{0: ?int, 1: bool, 2: bool}> [approver_id, signs_spt, signs_sppd] per tahap */
    private function steps(User $pelaksana, string $domain): array
    {
        return array_map(fn (array $s) => [$s['approver_id'], $s['signs_spt'], $s['signs_sppd']], $this->service->simulateApprovals($pelaksana, $domain));
    }

    public function test_staf_rumah_sakit_dalam_dan_luar_daerah_melalui_sekretaris_lalu_direktur(): void
    {
        foreach (['dalam_daerah', 'lddp', 'ldlp'] as $domain) {
            $this->assertSame([
                [$this->sekretaris->id, false, false],
                [$this->direktur->id, true, true],
            ], $this->steps($this->staf, $domain), $domain);
        }
    }

    public function test_direktur_dalam_daerah_melalui_sekretaris_lalu_direktur(): void
    {
        $this->assertSame([
            [$this->sekretaris->id, false, false],
            [$this->direktur->id, true, true],
        ], $this->steps($this->direktur, 'dalam_daerah'));
    }

    public function test_direktur_luar_daerah_diakhiri_kadis_kesehatan_yang_menandatangani_spt(): void
    {
        $sppd = $this->sppd($this->direktur, 'lddp');
        $this->assertTrue($this->service->generateApprovals($sppd));

        $approvals = $sppd->approvals()->orderBy('step_order')->get();
        $this->assertSame([$this->sekretaris->id, $this->direktur->id, $this->kadis->id], $approvals->pluck('approver_id')->all());
        $this->assertSame(['Sekretaris', 'Direktur', 'Kepala Dinas Kesehatan'], $approvals->pluck('role_label')->all());
        $this->assertTrue((bool) $approvals[2]->signs_spt);
        $this->assertFalse((bool) $approvals[2]->signs_sppd);
        $this->assertTrue((bool) $approvals[1]->signs_sppd);
    }

    public function test_direktur_luar_negeri_lanjut_ke_sekda_dan_wali_kota(): void
    {
        $setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $sekda = $this->user('sekda', $setda);
        $walikota = $this->user('walikota', $setda);

        $this->assertSame([
            [$this->sekretaris->id, false, false],
            [$this->direktur->id, false, true],
            [$this->kadis->id, false, false],
            [$sekda->id, false, false],
            [$walikota->id, true, false],
        ], $this->steps($this->direktur, 'luar_negeri'));
    }

    public function test_pejabat_rumah_sakit_disebut_direktur_dan_sekretaris(): void
    {
        $labels = fn (User $pelaksana, string $domain) => array_column($this->service->simulateApprovals($pelaksana, $domain), 'role_label');

        $this->assertSame(['Sekretaris', 'Direktur'], $labels($this->staf, 'lddp'));
        $this->assertSame(['Sekretaris', 'Direktur', 'Kepala Dinas Kesehatan'], $labels($this->direktur, 'ldlp'));
        $this->assertSame('Direktur', $this->direktur->primaryRoleLabel());

        // Instansi lain tetap memakai label role.
        $this->assertSame('Kepala OPD', $this->kadis->primaryRoleLabel());
        $this->assertSame('Kepala OPD', $this->service->roleLabelIn('kepala_opd', $this->dinkes->id));

        // Tahap tanpa pejabat memakai sebutan instansi pemohon.
        $this->direktur->delete();
        $this->assertSame(['Sekretaris', 'Direktur'], $labels($this->staf, 'dalam_daerah'));
    }

    public function test_pratinjau_dan_tab_daftar_sppd_rumah_sakit_memakai_sebutan_direktur(): void
    {
        $admin = $this->user('admin_opd', $this->rsud);

        Livewire::actingAs($admin)->test(SppdCreate::class)
            ->set('domain', 'lddp')
            ->call('selectUser', $this->staf->id)
            ->assertSee('Direktur')
            ->assertDontSee('Kepala OPD')
            ->assertDontSee('Sekretaris OPD');

        Livewire::actingAs($admin)->test(SppdIndex::class)
            ->assertViewHas('jabatanLabels', fn (array $labels) => $labels['kepala_opd'] === 'Direktur');

        $adminDinkes = $this->user('admin_opd', $this->dinkes);
        Livewire::actingAs($adminDinkes)->test(SppdIndex::class)
            ->assertViewHas('jabatanLabels', fn (array $labels) => $labels['kepala_opd'] === 'Kepala OPD');
    }

    public function test_role_kepala_opd_di_rumah_sakit_tampil_direktur_di_halaman_pegawai(): void
    {
        $admin = $this->user('admin_opd', $this->rsud);

        // Label role pegawai (sidebar, profil, detail pegawai).
        $this->assertSame('Direktur', $this->direktur->primaryRoleLabel());
        $this->assertSame('Sekretaris', $this->sekretaris->primaryRoleLabel());
        $this->actingAs($this->direktur)->get(route('dashboard'))->assertSeeInOrder([$this->direktur->name, 'Direktur']);

        // Daftar pegawai: badge role & pilihan filter role.
        Livewire::actingAs($admin)->test(UsersIndex::class)
            ->assertSeeInOrder([$this->direktur->name, 'Direktur'])
            ->assertDontSee('Kepala OPD')
            ->assertDontSee('Sekretaris OPD');

        // Form pegawai: pilihan role mengikuti unit kerja terpilih.
        $hasDirektur = fn (array $options) => collect($options)->firstWhere('value', 'kepala_opd')['label'] === 'Direktur';
        Livewire::actingAs($admin)->test(UserForm::class, ['user' => $this->direktur])
            ->assertViewHas('roleOptions', $hasDirektur);

        // Instansi lain tetap "Kepala OPD".
        $adminDinkes = $this->user('admin_opd', $this->dinkes);
        Livewire::actingAs($adminDinkes)->test(UsersIndex::class)->assertSee('Kepala OPD')->assertDontSee('Direktur');
        Livewire::actingAs($adminDinkes)->test(UserForm::class, ['user' => $this->kadis])
            ->assertViewHas('roleOptions', fn (array $options) => collect($options)->firstWhere('value', 'kepala_opd')['label'] === 'Kepala OPD');
    }

    public function test_alur_persetujuan_rumah_sakit_memakai_sebutan_direktur(): void
    {
        $superAdmin = $this->user('super_admin', $this->rsud);

        // Form alur: sebutan berubah saat hanya Rumah Sakit yang dicentang.
        Livewire::actingAs($superAdmin)->test(WorkflowForm::class)
            ->set('department_type', ['rumah_sakit'])
            ->assertViewHas('roleLabelOf', fn ($label) => $label((object) ['name' => 'kepala_opd', 'label' => 'Kepala OPD']) === 'Direktur')
            ->set('department_type', ['rumah_sakit', 'opd'])
            ->assertViewHas('roleLabelOf', fn ($label) => $label((object) ['name' => 'kepala_opd', 'label' => 'Kepala OPD']) === 'Kepala OPD');

        // Daftar alur: tahap alur khusus Rumah Sakit.
        SppdWorkflow::whereNotIn('name', ['Direktur Rumah Sakit — Luar Daerah'])->delete();
        Livewire::actingAs($superAdmin)->test(WorkflowIndex::class)
            ->assertSeeInOrder(['Direktur Rumah Sakit — Luar Daerah', 'Direktur', '1.', 'Sekretaris', '2.', 'Direktur', '3.', 'Kepala Dinas Kesehatan'])
            ->assertDontSee('Kepala OPD');

    }

    public function test_migrasi_menyamakan_label_persetujuan_lama_rumah_sakit(): void
    {
        $rsud = $this->sppd($this->staf, 'lddp');
        $rsud->approvals()->createMany([
            ['approver_id' => $this->sekretaris->id, 'role_label' => 'Sekretaris OPD', 'step_order' => 1, 'status' => 'approved'],
            ['approver_id' => $this->direktur->id, 'role_label' => 'Kepala OPD', 'step_order' => 2, 'status' => 'approved'],
        ]);
        $dinkesStaf = $this->user('staf', $this->dinkes);
        $dinkes = $this->sppd($dinkesStaf, 'lddp');
        $dinkes->approvals()->create(['approver_id' => $this->kadis->id, 'role_label' => 'Kepala OPD', 'step_order' => 1, 'status' => 'approved']);

        $labels = fn (SppdRequest $sppd) => $sppd->approvals()->orderBy('step_order')->pluck('role_label')->all();
        $migration = require database_path('migrations/2026_11_15_000002_rename_rumah_sakit_approval_labels.php');

        $migration->up();
        $this->assertSame(['Sekretaris', 'Direktur'], $labels($rsud));
        $this->assertSame(['Kepala OPD'], $labels($dinkes));

        $migration->down();
        $this->assertSame(['Sekretaris OPD', 'Kepala OPD'], $labels($rsud));
        $this->assertSame(['Kepala OPD'], $labels($dinkes));
    }

    public function test_kadis_belum_terdaftar_tidak_bisa_dilengkapi_dari_form_buat_sppd(): void
    {
        $this->kadis->delete();

        $kadisStep = collect($this->service->simulateApprovals($this->direktur, 'lddp'))->last();
        $this->assertSame('kepala_opd@dinkes', $kadisStep['role']);
        $this->assertSame('not_found', $kadisStep['status']);

        $admin = $this->user('admin_opd', $this->rsud);
        Livewire::actingAs($admin)->test(SppdCreate::class)
            ->set('domain', 'lddp')
            ->call('selectUser', $this->direktur->id)
            ->assertSee('Diatur Admin Dinas Kesehatan / Super Admin')
            ->assertSee('Kepala Dinas Kesehatan belum terdaftar di sistem')
            ->assertDontSeeHtml("openOfficial('kepala_opd@dinkes')");

        $this->expectExceptionMessage('Kepala Dinas Kesehatan belum terdaftar');
        $this->service->generateApprovals($this->sppd($this->direktur, 'lddp'));
    }

    public function test_kadis_tidak_bisa_dipilih_ulang_atau_diganti_dari_form_buat_sppd(): void
    {
        $this->assertFalse($this->service->isReassignable('kepala_opd@dinkes'));

        $admin = $this->user('admin_opd', $this->rsud);
        Livewire::actingAs($admin)->test(SppdCreate::class)
            ->set('domain', 'lddp')
            ->call('selectUser', $this->direktur->id)
            ->assertSee('Kepala Dinas Kesehatan')
            ->assertDontSeeHtml("chooseStep('kepala_opd@dinkes')")
            ->assertDontSeeHtml("replaceOfficial('kepala_opd@dinkes')");
    }

    public function test_form_alur_menyimpan_tahap_kepala_dinas_kesehatan(): void
    {
        $superAdmin = $this->user('super_admin', $this->rsud);

        Livewire::actingAs($superAdmin)->test(WorkflowForm::class)
            ->assertSee('Kepala Dinas Kesehatan')
            ->set('name', 'Uji Rumah Sakit')
            ->set('department_type', ['rumah_sakit'])
            ->set('applicant_role', ['kepala_opd'])
            ->set('destination', ['lddp'])
            // Cakupannya sama dengan alur bawaan Direktur Rumah Sakit, jadi disimpan nonaktif.
            ->set('is_active', false)
            ->set('steps', [
                ['role' => 'kepala_opd', 'signs_spt' => false, 'signs_sppd' => true],
                ['role' => 'kepala_opd@dinkes', 'signs_spt' => true, 'signs_sppd' => false],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('kepala_opd@dinkes', SppdWorkflow::where('name', 'Uji Rumah Sakit')->first()->steps[1]['role']);
    }

    public function test_migrasi_mengubah_tipe_dan_menyisipkan_alur_tanpa_duplikasi(): void
    {
        // Keadaan produksi sebelum migrasi: RSUD & Dinas Kesehatan bertipe OPD, belum ada alur Rumah Sakit.
        $this->dinkes->update(['type' => DepartmentType::OPD]);
        $this->rsud->update(['type' => DepartmentType::OPD]);
        $anteroHamra = Department::create(['name' => 'RSUD Antero Hamra', 'type' => DepartmentType::OPD, 'level' => 1]);
        $bukanRs = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);
        SppdWorkflow::whereJsonContains('department_type', 'rumah_sakit')->delete();

        $migration = require database_path('migrations/2026_11_15_000001_add_rumah_sakit_workflows.php');
        $migration->up();
        $migration->up();

        $this->assertSame(DepartmentType::DINKES, $this->dinkes->fresh()->type);
        $this->assertSame(DepartmentType::RUMAH_SAKIT, $this->rsud->fresh()->type);
        $this->assertSame(DepartmentType::RUMAH_SAKIT, $anteroHamra->fresh()->type);
        $this->assertSame(DepartmentType::OPD, $bukanRs->fresh()->type);
        $this->assertSame(7, SppdWorkflow::whereJsonContains('department_type', 'rumah_sakit')->count());

        $this->direktur->refresh();
        $this->assertSame($this->kadis->id, collect($this->service->simulateApprovals($this->direktur->load('department'), 'ldlp'))->last()['approver_id']);

        $migration->down();
        $this->assertSame(DepartmentType::OPD, $this->rsud->fresh()->type);
        $this->assertSame(DepartmentType::OPD, $anteroHamra->fresh()->type);
        $this->assertSame(0, SppdWorkflow::whereJsonContains('department_type', 'rumah_sakit')->count());
    }
}
