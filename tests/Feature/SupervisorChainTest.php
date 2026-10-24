<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Livewire\UserForm;
use App\Livewire\WorkflowForm;
use App\Models\Department;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Atasan langsung berantai (Staf → Kabag → Asisten): memilih workflow dan mengisi
 * tahap Kabid/Asisten tanpa harus menyusun sub-unit kerja lebih dulu.
 */
class SupervisorChainTest extends TestCase
{
    use RefreshDatabase;

    private SppdWorkflowService $service;
    private Department $setda;
    private User $kabag;
    private User $asisten;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'staf', 'kasubid_kasubag', 'kabid_irban_kabag', 'sekretaris_opd', 'kepala_opd', 'asisten', 'sekda', 'walikota', 'sekwan'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->seed(SppdWorkflowSeeder::class);
        $this->service = app(SppdWorkflowService::class);

        $this->setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $bagian = Department::create(['name' => 'Bagian Umum', 'type' => DepartmentType::BAGIAN, 'level' => 2, 'parent_id' => $this->setda->id]);
        $this->user('sekda', $this->setda);
        $this->asisten = $this->user('asisten', $this->setda);
        $this->user('asisten', $this->setda);
        $this->kabag = $this->user('kabid_irban_kabag', $bagian);
    }

    private function user(string $role, Department $department): User
    {
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole($role);

        return $user;
    }

    private function adminSetda(): User
    {
        return $this->user('admin_opd', $this->setda);
    }

    public function test_buat_sppd_meminta_atasan_lalu_pilih_asisten_berurutan(): void
    {
        $staf = $this->user('staf', $this->setda);
        $rekan = $this->user('staf', $this->setda);
        $this->actingAs($this->adminSetda());

        // Atasan belum pasti: alur lengkap tetap tampil, tahap Kabag belum dipilih.
        $component = Livewire::test(SppdCreate::class)
            ->call('selectUser', $staf->id)
            ->assertSet('isComplete', false)
            ->assertSee('Pejabat alur belum dipilih')
            ->assertDontSee('Atasan langsung:')
            ->assertSeeHtml("chooseStep('kabid_irban_kabag')");
        $this->assertSame(['kabid_irban_kabag', 'asisten', 'sekda'], array_column($component->get('steps'), 'role'));
        $this->assertSame(['choose', 'choose', 'found'], array_column($component->get('steps'), 'status'));

        $component->call('chooseStep', 'kabid_irban_kabag')
            ->assertSet('supervisorMode', 'supervisor')
            ->assertSet('showSupervisorModal', true);

        // Kabag di Bagian ikut jadi calon walau staf tercatat langsung di Setda, dan tampil paling atas.
        $candidates = $component->viewData('supervisorModal')['candidates'];
        $this->assertSame($this->kabag->id, $candidates[0]['id']);
        // Asisten juga calon (alur untuk staf langsung di bawah Asisten); Sekda bukan.
        $this->assertContains($this->asisten->id, array_column($candidates, 'id'));
        $this->assertNotContains('sekda', array_column($candidates, 'role_label'));

        $component->call('pickSupervisor', $this->kabag->id)
            ->set('supervisorApplyColleagues', true)
            ->call('saveSupervisor')
            ->assertSet('isComplete', false)
            ->assertSee('Pejabat alur belum dipilih')
            ->assertSeeHtml("chooseStep('asisten')");

        $this->assertSame($this->kabag->id, $staf->fresh()->atasan_id);
        $this->assertSame($this->kabag->id, $rekan->fresh()->atasan_id);

        $component->call('chooseStep', 'asisten')
            ->assertSet('showSupervisorModal', true)
            ->call('pickSupervisor', $this->asisten->id)
            ->call('saveSupervisor')
            ->assertSet('isComplete', false); // kop surat belum ada, tetapi alurnya lengkap

        $this->assertSame($this->asisten->id, $this->kabag->fresh()->atasan_id);
        $this->assertTrue(SppdWorkflowService::isComplete($component->get('steps')));
    }

    public function test_tahap_atasan_bisa_memilih_asisten_untuk_alur_tanpa_kabag(): void
    {
        $staf = $this->user('staf', $this->setda);
        $this->actingAs($this->adminSetda());

        $component = Livewire::test(SppdCreate::class)
            ->call('selectUser', $staf->id)
            ->call('chooseStep', 'kabid_irban_kabag')
            ->assertSee('langsung di bawah asisten')
            ->call('pickSupervisor', $this->asisten->id)
            ->call('saveSupervisor');

        $this->assertSame($this->asisten->id, $staf->fresh()->atasan_id);
        $this->assertSame(['asisten', 'sekda'], array_column($component->get('steps'), 'role'));
        $this->assertTrue(SppdWorkflowService::isComplete($component->get('steps')));
    }

    public function test_calon_atasan_pegawai_opd_tanpa_kabag_setda(): void
    {
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $kabid = $this->user('kabid_irban_kabag', $dinas);
        $staf = $this->user('staf', $dinas);

        $ids = array_column($this->service->supervisorCandidates($staf), 'id');
        $this->assertContains($kabid->id, $ids);
        $this->assertContains($this->asisten->id, $ids);
        $this->assertNotContains($this->kabag->id, $ids);
    }

    public function test_ikon_edit_sekretaris_langsung_ganti_pejabat(): void
    {
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->user('kepala_opd', $dinas);
        $this->user('sekretaris_opd', $dinas);
        $staf = $this->user('staf', $dinas);
        $this->actingAs($this->user('admin_opd', $dinas));

        // Sekretaris satu per OPD: atasan pasti, tidak perlu dipilih; ikon edit = Ganti pejabat.
        Livewire::test(SppdCreate::class)
            ->call('selectUser', $staf->id)
            ->assertSeeHtml("replaceOfficial('sekretaris_opd')")
            ->assertDontSeeHtml("chooseStep('sekretaris_opd')")
            ->call('chooseStep', 'sekretaris_opd')
            ->assertSet('showSupervisorModal', false);
    }

    public function test_tahap_kabid_terisi_bisa_dipilih_ulang(): void
    {
        $kabagLain = $this->user('kabid_irban_kabag', $this->setda);
        $staf = $this->user('staf', $this->setda);
        $staf->update(['atasan_id' => $this->kabag->id]);
        $this->actingAs($this->adminSetda());

        Livewire::test(SppdCreate::class)
            ->call('selectUser', $staf->id)
            ->assertSeeHtml("chooseStep('kabid_irban_kabag')")
            ->call('chooseStep', 'kabid_irban_kabag')
            ->call('pickSupervisor', $kabagLain->id)
            ->call('saveSupervisor');

        $this->assertSame($kabagLain->id, $staf->fresh()->atasan_id);
    }

    public function test_atasan_ditolak_bila_diri_sendiri_lebih_rendah_atau_berputar(): void
    {
        $staf = $this->user('staf', $this->setda);
        $this->kabag->update(['atasan_id' => $this->asisten->id]);

        $this->assertNotNull($this->service->supervisorError($staf, $staf));
        // Staf tidak berada di atas Kabag.
        $this->assertNotNull($this->service->supervisorError($this->kabag, $staf));
        // Asisten → Kabag padahal Kabag → Asisten: berputar (dicek walau tingkatnya dipaksa).
        $this->assertStringContainsString('berputar', $this->service->supervisorError($this->asisten, $this->kabag, 2000));

        $this->expectException(\RuntimeException::class);
        $this->service->setSupervisor($this->kabag, $staf);
    }

    public function test_form_detail_menolak_alur_yang_belum_lengkap(): void
    {
        $staf = $this->user('staf', $this->setda);
        $this->actingAs($this->adminSetda());

        Livewire::withQueryParams(['user_id' => $staf->id, 'domain' => 'dalam_daerah'])
            ->test(SppdCreateDetails::class)
            ->assertRedirect(route('sppd.create', ['user_id' => $staf->getRouteKey(), 'domain' => 'dalam_daerah']));
    }

    public function test_ganti_pejabat_memindahkan_bawahan_ke_pengganti(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $staf = $this->user('staf', $this->setda);
        $pengganti = $this->user('staf', $this->setda);
        $staf->update(['atasan_id' => $this->kabag->id]);
        $this->kabag->update(['atasan_id' => $this->asisten->id]);

        $this->service->appointOfficial($superAdmin, $staf->fresh(), 'dalam_daerah', 'kabid_irban_kabag', $pengganti->id, null, $this->kabag->id);

        $this->assertSame($pengganti->id, $staf->fresh()->atasan_id);
        $this->assertSame($this->asisten->id, $pengganti->fresh()->atasan_id);
        $this->assertNull($this->kabag->fresh()->atasan_id);
        $this->assertTrue($this->kabag->fresh()->hasRole('staf'));
    }

    public function test_form_alur_kriteria_atasan_wajib_dipilih_eksplisit(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);

        $form = fn () => Livewire::test(WorkflowForm::class)
            ->set('name', 'Staf beratasan Sekretaris')
            ->set('department_type', ['opd'])
            ->set('applicant_role', ['staf'])
            ->set('destination', ['dalam_daerah'])
            ->set('steps', [
                ['role' => 'sekretaris_opd', 'signs_spt' => false, 'signs_sppd' => false],
                ['role' => 'kepala_opd', 'signs_spt' => true, 'signs_sppd' => true],
            ]);

        $form()->set('supervisor_mode', 'specific')->call('save')->assertHasErrors(['supervisor_role' => 'required']);

        $form()->set('supervisor_mode', 'specific')->set('supervisor_role', ['sekretaris_opd'])->call('save')->assertHasNoErrors();
        $this->assertSame(['sekretaris_opd'], SppdWorkflow::where('name', 'Staf beratasan Sekretaris')->sole()->supervisor_role);

        SppdWorkflow::where('name', 'Staf beratasan Sekretaris')->delete();
        $form()->call('save')->assertHasNoErrors();
        $this->assertNull(SppdWorkflow::where('name', 'Staf beratasan Sekretaris')->sole()->supervisor_role);
    }

    public function test_form_pegawai_tidak_mengatur_atasan_dan_menjaga_atasan_tersimpan(): void
    {
        $staf = $this->user('staf', $this->setda);
        $staf->update(['atasan_id' => $this->kabag->id, 'username' => 'staf.uji']);
        $this->actingAs($this->user('super_admin', $this->setda));

        // Atasan hanya dipilih saat membuat SPPD: form pegawai tidak menampilkannya,
        // dan menyimpan perubahan lain tidak menghapus atasan yang sudah dipilih.
        Livewire::test(UserForm::class, ['user' => $staf->fresh()])
            ->assertDontSee('Atasan Langsung')
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($this->kabag->id, $staf->fresh()->atasan_id);
    }

    public function test_pindah_instansi_mengosongkan_atasan_yang_tidak_lagi_sah(): void
    {
        $staf = $this->user('staf', $this->setda);
        $staf->update(['atasan_id' => $this->kabag->id, 'username' => 'staf.pindah']);
        $dinas = Department::create(['name' => 'Dinas Pendidikan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->actingAs($this->user('super_admin', $this->setda));

        // Kabag Setda bukan atasan pegawai Dinas: dikosongkan agar dipilih ulang saat membuat SPPD.
        Livewire::test(UserForm::class, ['user' => $staf->fresh()])
            ->set('department_id', $dinas->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($dinas->id, $staf->fresh()->department_id);
        $this->assertNull($staf->fresh()->atasan_id);
    }
}
