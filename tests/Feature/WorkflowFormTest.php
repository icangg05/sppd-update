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
}
