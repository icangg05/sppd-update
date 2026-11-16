<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\RoleForm;
use App\Livewire\RoleIndex;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Catatan role: keterangan untuk pengguna (mis. Kepala OPD = Direktur di Rumah Sakit),
 * diatur di form role dan tampil di daftar role.
 */
class RoleNoteTest extends TestCase
{
    use RefreshDatabase;

    private const DIREKTUR = 'Di unit kerja Rumah Sakit, role ini disebut Direktur (Direktur RSUD memakai role ini).';

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $department = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $this->superAdmin = User::factory()->create(['department_id' => $department->id]);
        $this->superAdmin->assignRole('super_admin');
    }

    public function test_migrasi_mengisi_catatan_awal_tanpa_menimpa_catatan_yang_sudah_diubah(): void
    {
        Role::where('name', 'kepala_opd')->update(['note' => null]);
        Role::where('name', 'sekretaris_opd')->update(['note' => 'Catatan buatan Super Admin']);

        $migration = require database_path('migrations/2026_11_16_000001_add_note_to_roles_table.php');
        $migration->up();

        $this->assertSame(self::DIREKTUR, Role::where('name', 'kepala_opd')->value('note'));
        $this->assertSame('Catatan buatan Super Admin', Role::where('name', 'sekretaris_opd')->value('note'));
        $this->assertNull(Role::where('name', 'staf')->value('note'));
    }

    public function test_catatan_diatur_di_form_role_dan_tampil_di_daftar_role(): void
    {
        $kepala = Role::where('name', 'kepala_opd')->first();

        Livewire::actingAs($this->superAdmin)->test(RoleForm::class, ['role' => $kepala])
            ->assertSet('note', self::DIREKTUR)
            ->set('note', '  Juga dipakai Direktur RSUD.  ')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('Juga dipakai Direktur RSUD.', $kepala->fresh()->note);

        // Dikosongkan = tanpa catatan.
        Livewire::actingAs($this->superAdmin)->test(RoleForm::class, ['role' => $kepala])->set('note', ' ')->call('save');
        $this->assertNull($kepala->fresh()->note);

        Livewire::actingAs($this->superAdmin)->test(RoleForm::class)
            ->set('name', 'kepala_bagian')
            ->set('label', 'Kepala Bagian')
            ->set('note', 'Khusus Setda')
            ->call('save');
        $this->assertSame('Khusus Setda', Role::where('name', 'kepala_bagian')->value('note'));

        Livewire::actingAs($this->superAdmin)->test(RoleIndex::class)
            ->set('search', 'kepala')
            ->assertSeeInOrder(['Kepala Bagian', 'Khusus Setda']);
    }
}
