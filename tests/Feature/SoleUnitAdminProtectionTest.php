<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\UsersIndex;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin OPD yang tidak punya rekan Admin OPD aktif di unit kerjanya tidak bisa
 * dihapus atau dinonaktifkan dari Data Pegawai, agar unit itu tetap punya admin.
 */
class SoleUnitAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Urutan Data Pegawai memakai FIELD() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('FIELD', function ($value, ...$list) {
                $index = array_search($value, $list);

                return $index === false ? 0 : $index + 1;
            });
        }

        foreach (['super_admin', 'admin_opd', 'staf'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $this->dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->superAdmin = $this->user('super_admin');
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create(['department_id' => $this->dinas->id, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_tunggal_tanpa_tombol_hapus_dan_nonaktifkan(): void
    {
        $admin = $this->user('admin_opd');
        $staf = $this->user('staf');

        Livewire::actingAs($this->superAdmin)
            ->test(UsersIndex::class)
            ->assertDontSeeHtml("confirmDelete({$admin->id})")
            ->assertDontSeeHtml("confirmToggleActive({$admin->id})")
            ->assertSeeHtml("confirmDelete({$staf->id})")
            ->assertSeeHtml("confirmToggleActive({$staf->id})");
    }

    public function test_dua_admin_aktif_di_unit_yang_sama_tetap_bisa_dihapus(): void
    {
        $admin = $this->user('admin_opd');
        $rekan = $this->user('admin_opd');

        Livewire::actingAs($this->superAdmin)
            ->test(UsersIndex::class)
            ->assertSeeHtml("confirmDelete({$admin->id})")
            ->assertSeeHtml("confirmToggleActive({$rekan->id})");
    }

    public function test_rekan_admin_yang_nonaktif_tidak_dihitung(): void
    {
        $admin = $this->user('admin_opd');
        $nonaktif = $this->user('admin_opd', ['is_active' => false]);

        Livewire::actingAs($this->superAdmin)
            ->test(UsersIndex::class)
            ->assertDontSeeHtml("confirmDelete({$admin->id})")
            ->assertDontSeeHtml("confirmToggleActive({$admin->id})")
            // Admin nonaktif masih punya rekan aktif, jadi boleh dihapus atau diaktifkan lagi.
            ->assertSeeHtml("confirmDelete({$nonaktif->id})")
            ->assertSeeHtml("confirmToggleActive({$nonaktif->id})");
    }

    public function test_admin_tunggal_yang_nonaktif_masih_bisa_diaktifkan(): void
    {
        $admin = $this->user('admin_opd', ['is_active' => false]);

        Livewire::actingAs($this->superAdmin)
            ->test(UsersIndex::class)
            ->assertDontSeeHtml("confirmDelete({$admin->id})")
            ->assertSeeHtml("confirmToggleActive({$admin->id})");
    }

    public function test_server_menolak_hapus_dan_nonaktifkan_admin_tunggal(): void
    {
        $admin = $this->user('admin_opd');

        Livewire::actingAs($this->superAdmin)
            ->test(UsersIndex::class)
            ->call('confirmDelete', $admin->id)
            ->assertSet('showDeleteModal', false)
            ->call('deleteUser', $admin->id)
            ->call('confirmToggleActive', $admin->id)
            ->assertSet('showToggleModal', false)
            ->call('toggleActive', $admin->id);

        $this->assertNotNull($admin->fresh());
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_hapus_pegawai_lewat_modal_konfirmasi(): void
    {
        $staf = $this->user('staf', ['name' => 'Budi Santoso']);

        $component = Livewire::actingAs($this->superAdmin)
            ->test(UsersIndex::class)
            ->call('confirmDelete', $staf->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('deletingId', $staf->id)
            ->assertSee('Budi Santoso')
            // Batal menutup modal tanpa menghapus.
            ->call('closeDeleteModal')
            ->assertSet('showDeleteModal', false)
            ->assertSet('deletingId', null);

        $this->assertNotNull($staf->fresh());

        $component->call('confirmDelete', $staf->id)
            ->call('deleteUser', $staf->id)
            ->assertSet('showDeleteModal', false);

        $this->assertNull($staf->fresh());
    }
}
