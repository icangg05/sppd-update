<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * NIP/NIK di form pegawai hanya angka dengan panjang baku (NIP 18, NIK 16);
 * spasi dari NIP berformat "19810807 200502 2 004" dibuang sebelum disimpan.
 */
class UserIdentityNumberTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'staf'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        SppdWorkflow::create([
            'name' => 'Staf',
            'department_type' => ['opd'],
            'applicant_role' => ['staf'],
            'steps' => [['role' => 'admin_opd']],
            'is_active' => true,
        ]);

        $this->department = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->admin = User::factory()->create(['department_id' => $this->department->id]);
        $this->admin->assignRole('admin_opd');
    }

    private function form(string $nip, string $nik)
    {
        return Livewire::actingAs($this->admin)
            ->test(UserForm::class)
            ->set('name', 'Budi Santoso')
            ->set('username', 'budi')
            ->set('department_id', $this->department->id)
            ->set('nip', $nip)
            ->set('nik', $nik)
            ->call('save');
    }

    public function test_spasi_nip_dan_nik_dibuang_sebelum_disimpan(): void
    {
        $this->form('19810807 200502 2 004', '7471 0123 4567 8901')->assertHasNoErrors();

        $budi = User::where('username', 'budi')->firstOrFail();
        $this->assertSame('198108072005022004', $budi->nip);
        $this->assertSame('7471012345678901', $budi->nik);
    }

    public function test_nip_dan_nik_kosong_tetap_boleh(): void
    {
        $this->form('', '')->assertHasNoErrors();

        $budi = User::where('username', 'budi')->firstOrFail();
        $this->assertNull($budi->nip);
        $this->assertNull($budi->nik);
    }

    public function test_nip_dan_nik_berhuruf_atau_kurang_digit_ditolak(): void
    {
        $this->form('19810807-200502-2-004', '747101234567890A')->assertHasErrors(['nip' => 'digits', 'nik' => 'digits']);
        $this->form('0000', '1234')->assertHasErrors(['nip' => 'digits', 'nik' => 'digits']);
        $this->form(str_repeat('0', 18), str_repeat('0', 16))->assertHasErrors(['nip' => 'not_regex', 'nik' => 'not_regex']);

        $this->assertFalse(User::where('username', 'budi')->exists());
    }
}
