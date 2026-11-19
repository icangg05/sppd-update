<?php

namespace Tests\Feature;

use App\Livewire\WorkflowForm;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkflowFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'staf', 'sekretaris_opd', 'kepala_opd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin);
    }

    private function filledForm()
    {
        return Livewire::test(WorkflowForm::class)
            ->set('name', 'Staf OPD')
            ->set('steps', [
                ['role' => 'sekretaris_opd', 'signs_spt' => false, 'signs_sppd' => false],
                ['role' => 'kepala_opd', 'signs_spt' => true, 'signs_sppd' => true],
            ]);
    }

    public function test_cakupan_wajib_dicentang_tidak_ada_makna_kosong_berarti_semua(): void
    {
        $component = $this->filledForm()
            ->call('save')
            ->assertHasErrors([
                'department_type' => 'required',
                'applicant_role' => 'required',
                'destination' => 'required',
            ]);

        $this->assertSame(0, SppdWorkflow::count());

        // Pesan tampil sekali per grup, bukan di bawah setiap checkbox.
        $this->assertSame(1, substr_count($component->html(), 'Centang minimal satu tipe instansi.'));
    }

    public function test_nilai_cakupan_di_luar_pilihan_ditolak(): void
    {
        $this->filledForm()
            ->set('department_type', ['bukan_tipe'])
            ->set('applicant_role', ['role_fiktif'])
            ->set('destination', ['bulan'])
            ->call('save')
            ->assertHasErrors(['department_type.0', 'applicant_role.0', 'destination.0']);
    }

    public function test_cakupan_yang_dicentang_tersimpan_apa_adanya(): void
    {
        $this->filledForm()
            ->set('department_type', ['opd', 'kecamatan'])
            ->set('applicant_role', ['staf'])
            ->set('destination', ['dalam_daerah', 'lddp'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('master.workflows.index'));

        $workflow = SppdWorkflow::sole();
        $this->assertSame(['opd', 'kecamatan'], $workflow->department_type);
        $this->assertSame(['staf'], $workflow->applicant_role);
        $this->assertSame(['dalam_daerah', 'lddp'], $workflow->destination);
    }

    public function test_alur_tidak_lagi_dibedakan_per_atasan_langsung(): void
    {
        $this->filledForm()
            ->assertDontSee('Atasan Langsung Pemohon')
            ->assertDontSee('Hanya untuk atasan tertentu')
            ->set('department_type', ['opd'])
            ->set('applicant_role', ['staf'])
            ->set('destination', ['dalam_daerah'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertArrayNotHasKey('supervisor_role', SppdWorkflow::sole()->getAttributes());
    }

    /** Alur aktif yang sudah ada: OPD × Staf × Dalam Daerah & LDDP. */
    private function existingWorkflow(array $overrides = []): SppdWorkflow
    {
        return SppdWorkflow::create($overrides + [
            'name' => 'Staf OPD Lama',
            'department_type' => ['opd'],
            'applicant_role' => ['staf'],
            'destination' => ['dalam_daerah', 'lddp'],
            'steps' => [['role' => 'kepala_opd', 'signs_spt' => true, 'signs_sppd' => true]],
            'is_active' => true,
        ]);
    }

    public function test_cakupan_beririsan_dengan_alur_aktif_lain_ditolak_beserta_irisannya(): void
    {
        $this->existingWorkflow();

        // Irisan sebagian pun bentrok: OPD × Staf × LDDP sudah dipakai alur lama.
        $this->filledForm()
            ->set('department_type', ['opd', 'kecamatan'])
            ->set('applicant_role', ['staf', 'sekretaris_opd'])
            ->set('destination', ['lddp', 'ldlp'])
            ->assertSee('Cakupan ini bentrok dengan alur aktif lain')
            ->assertSee('Staf OPD Lama')
            ->assertSee('Tipe: OPD · Peran: staf · Wilayah: LDDP')
            ->call('save')
            ->assertHasErrors('scope')
            ->assertNoRedirect();

        $this->assertSame(1, SppdWorkflow::count());
    }

    public function test_cakupan_tanpa_irisan_atau_alur_nonaktif_tidak_bentrok(): void
    {
        $existing = $this->existingWorkflow();
        $this->existingWorkflow(['name' => 'Alur Nonaktif', 'destination' => ['ldlp'], 'is_active' => false]);

        // Wilayah berbeda (alur nonaktif tidak dihitung): tidak beririsan.
        $this->filledForm()
            ->set('department_type', ['opd'])
            ->set('applicant_role', ['staf'])
            ->set('destination', ['ldlp'])
            ->assertDontSee('Cakupan ini bentrok')
            ->call('save')
            ->assertHasNoErrors();

        // Mengedit alur tidak bentrok dengan dirinya sendiri.
        Livewire::test(WorkflowForm::class, ['workflow' => $existing])
            ->set('name', 'Staf OPD Lama (revisi)')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_alur_pengganti_disiapkan_nonaktif_lalu_ditukar(): void
    {
        $lama = $this->existingWorkflow();

        // Cakupan sama tetapi disimpan nonaktif: boleh, sebagai alur pengganti.
        $this->filledForm()
            ->set('department_type', ['opd'])
            ->set('applicant_role', ['staf'])
            ->set('destination', ['dalam_daerah', 'lddp'])
            ->set('is_active', false)
            ->call('save')
            ->assertHasNoErrors();
        $baru = SppdWorkflow::where('name', 'Staf OPD')->sole();

        // Diaktifkan selagi alur lama masih aktif: ditolak.
        Livewire::test(WorkflowForm::class, ['workflow' => $baru])
            ->set('is_active', true)
            ->assertSee('Staf OPD Lama')
            ->call('save')
            ->assertHasErrors('scope');
        $this->assertFalse($baru->fresh()->is_active);

        // Alur lama dinonaktifkan dulu, lalu pengganti bisa diaktifkan.
        $lama->update(['is_active' => false]);
        Livewire::test(WorkflowForm::class, ['workflow' => $baru])
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors();
        $this->assertTrue($baru->fresh()->is_active);
    }

    public function test_seret_lepas_memindah_tahap_beserta_tanda_tangannya(): void
    {
        $step = fn (string $role, bool $spt = false, bool $sppd = false) => ['role' => $role, 'signs_spt' => $spt, 'signs_sppd' => $sppd];
        $roles = fn ($component) => array_column($component->get('steps'), 'role');

        $component = Livewire::test(WorkflowForm::class)->set('steps', [
            $step('kabid'), $step('sekretaris_opd'), $step('kepala_opd', true, true),
        ]);

        // Kunci item mulai 1 (tahap ke-1 = kunci 1), posisi tujuan mulai 0.
        $component->call('sortStep', 3, 0);
        $this->assertSame(['kepala_opd', 'kabid', 'sekretaris_opd'], $roles($component));
        $this->assertTrue($component->get('steps')[0]['signs_spt']);

        $component->call('sortStep', 1, 2);
        $this->assertSame(['kabid', 'sekretaris_opd', 'kepala_opd'], $roles($component));

        // Kunci yang tidak ada diabaikan; posisi di luar batas dijepit ke ujung daftar.
        $component->call('sortStep', 9, 0)->call('sortStep', 1, 99);
        $this->assertSame(['sekretaris_opd', 'kepala_opd', 'kabid'], $roles($component));
    }
}
