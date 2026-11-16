<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\RoleForm;
use App\Livewire\Sppd\CostDetails;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\VerifyDocument;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pembuatan SPPD & pengelolaan biaya (menu Selanjutnya) diatur lewat permission:
 * Kasubid/Kasubag memegangnya, Admin OPD bisa dicabut Super Admin di Role & Permission.
 */
class KasubagOperatorPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $admin;
    private User $kasubag;
    private User $staf;

    protected function setUp(): void
    {
        parent::setUp();

        // Filter tahun Daftar SPPD memakai YEAR() MySQL; padanannya untuk SQLite pengujian.
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::connection()->getPdo()->sqliteCreateFunction('YEAR', fn ($date) => $date ? (int) substr($date, 0, 4) : null, 1);
        }

        $this->seed(RoleAndPermissionSeeder::class);
        $this->withoutVite();

        $this->dinas = Department::create(['name' => 'Badan Penanggulangan Bencana Daerah', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->admin = $this->user('admin_opd');
        $this->kasubag = $this->user('kasubid_kasubag');
        $this->staf = $this->user('staf');
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['department_id' => $this->dinas->id]);
        $user->assignRole($role);

        return $user;
    }

    private function sppd(SppdStatus $status = SppdStatus::APPROVED): SppdRequest
    {
        $budget = new Budget(['department_id' => $this->dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $this->staf->id,
            'creator_id' => $this->kasubag->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => $status,
        ]);
    }

    private function revokeFromAdmin(array $permissions): void
    {
        Role::findByName('admin_opd', 'web')->revokePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin->unsetRelation('roles')->unsetRelation('permissions');
    }

    public function test_kasubag_membuat_sppd_sedangkan_staf_tidak(): void
    {
        $this->actingAs($this->kasubag)->get(route('sppd.create'))->assertOk();
        $this->actingAs($this->staf)->get(route('sppd.create'))->assertForbidden();

        Livewire::actingAs($this->kasubag)->test(SppdIndex::class)->assertSee('Buat SPPD');
        Livewire::actingAs($this->staf)->test(SppdIndex::class)->assertDontSee('Buat SPPD');
    }

    public function test_kasubag_mengelola_biaya_di_menu_selanjutnya(): void
    {
        $sppd = $this->sppd();

        Livewire::actingAs($this->kasubag)->test(SppdIndex::class)->assertSeeHtml(route('sppd.next', $sppd));
        $this->actingAs($this->kasubag)->get(route('sppd.next', $sppd))->assertOk();
        $this->assertTrue(Livewire::actingAs($this->kasubag)->test(CostDetails::class, ['sppd' => $sppd])->instance()->canManage());

        // Staf tetap bisa melihat menu Selanjutnya, tetapi tidak mengubah biaya.
        $this->actingAs($this->staf)->get(route('sppd.next', $sppd))->assertOk();
        $this->assertFalse(Livewire::actingAs($this->staf)->test(CostDetails::class, ['sppd' => $sppd])->instance()->canManage());
    }

    public function test_admin_yang_dicabut_haknya_tidak_bisa_membuat_sppd_atau_masuk_menu_selanjutnya(): void
    {
        $sppd = $this->sppd();

        // Masa peralihan: Admin OPD masih memegang hak bawaan.
        $this->actingAs($this->admin)->get(route('sppd.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('sppd.next', $sppd))->assertOk();

        $this->revokeFromAdmin(['sppd.create', 'sppd.delete', 'cost.view', 'cost.manage']);

        $this->actingAs($this->admin)->get(route('sppd.create'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('sppd.next', $sppd))->assertForbidden();
        $this->actingAs($this->admin)->get(route('sppd.receipts', $sppd))->assertForbidden();
        $this->actingAs($this->admin)->get(route('sppd.final-costs', $sppd))->assertForbidden();
        $this->actingAs($this->admin)->get(route('sppd.stream.rincian-biaya', $sppd))->assertForbidden();

        Livewire::actingAs($this->admin)->test(SppdIndex::class)
            ->assertDontSee('Buat SPPD')
            ->assertDontSeeHtml(route('sppd.next', $sppd))
            ->assertDontSeeHtml("confirmDelete({$sppd->id})");

        // Data master tetap miliknya.
        $this->actingAs($this->admin)->get(route('master.budgets.index'))->assertOk();
    }

    public function test_kelola_biaya_tanpa_buka_menu_selanjutnya_tidak_membuka_menu(): void
    {
        $sppd = $this->sppd();

        // Hanya "Kelola biaya perjalanan": menu Selanjutnya tetap tertutup.
        $this->revokeFromAdmin(['cost.view']);
        $this->assertFalse($this->admin->canOpenCostMenu());
        $this->assertFalse($this->admin->canManageCosts());

        Livewire::actingAs($this->admin)->test(SppdIndex::class)->assertDontSeeHtml(route('sppd.next', $sppd));
        $this->actingAs($this->admin)->get(route('sppd.next', $sppd))->assertForbidden();
        $this->actingAs($this->admin)->get(route('sppd.receipts', $sppd))->assertForbidden();
        $this->actingAs($this->admin)->put(route('sppd.update-pptk', $sppd), ['pptk_id' => $this->kasubag->id])->assertForbidden();

        // Hanya "Buka menu Selanjutnya": bisa melihat, tidak bisa mengubah biaya.
        Role::findByName('admin_opd', 'web')->givePermissionTo('cost.view');
        $this->revokeFromAdmin(['cost.manage']);
        $this->actingAs($this->admin)->get(route('sppd.next', $sppd))->assertOk();
        $this->assertFalse(Livewire::actingAs($this->admin)->test(CostDetails::class, ['sppd' => $sppd])->instance()->canManage());
    }

    public function test_form_role_memperingatkan_kelola_biaya_tanpa_buka_menu(): void
    {
        $superAdmin = User::factory()->create(['department_id' => $this->dinas->id]);
        $superAdmin->assignRole('super_admin');

        Livewire::actingAs($superAdmin)->test(RoleForm::class, ['role' => Role::findByName('kasubid_kasubag', 'web')])
            ->assertSee('Kelola biaya perjalanan tidak berfungsi tanpa')
            ->assertSee('Hanya berlaku bila Buka menu Selanjutnya juga dicentang.');
    }

    public function test_menu_kasubag_daftar_sppd_kalender_dan_cek_dokumen_tte(): void
    {
        $page = $this->actingAs($this->kasubag)->get(route('sppd.calendar'))->assertOk();

        $page->assertSee('Daftar SPPD')
            ->assertSee('Kalender')
            ->assertSee('Cek Dokumen TTE')
            ->assertDontSee('Menunggu Persetujuan')
            ->assertDontSee('Data Master')
            ->assertDontSee('Logs Aktivitas');

        $this->actingAs($this->kasubag)->get(route('verify.index'))->assertOk();
        $this->actingAs($this->staf)->get(route('verify.index'))->assertForbidden();
    }

    public function test_cek_dokumen_lain_langsung_membuka_pemilih_berkas(): void
    {
        Livewire::actingAs($this->kasubag)->test(VerifyDocument::class)
            ->assertDontSee('Cek dokumen lain')
            ->set('checked', true)
            ->assertSee('Cek dokumen lain')
            ->assertSeeHtml("document.getElementById('verify-file').click()")
            ->assertDontSeeHtml('resetUpload');
    }

    public function test_halaman_role_menampilkan_keterangan_permission(): void
    {
        $superAdmin = $this->user('super_admin');

        Livewire::actingAs($superAdmin)->test(RoleForm::class, ['role' => Role::findByName('kasubid_kasubag', 'web')])
            ->assertSee('Menu Selanjutnya (Biaya Perjalanan)')
            ->assertSee('Kelola biaya perjalanan')
            ->assertSee('Cek Dokumen TTE')
            ->assertSet('selectedPermissions', fn (array $selected) => in_array('cost.manage', $selected, true)
                && in_array('sppd.create', $selected, true)
                && ! in_array('sppd.approve', $selected, true))
            // Permission yang belum dipakai pemeriksaan akses tidak ditampilkan.
            ->assertDontSee('report.view')
            ->assertDontSee('department.create')
            ->assertDontSee('Lainnya')
            ->assertDontSee('Antrean TTE');
    }

    public function test_permission_menu_persetujuan_mengatur_menu_menunggu_persetujuan(): void
    {
        $kepala = $this->user('kepala_opd');

        $this->actingAs($kepala)->get(route('sppd.calendar'))->assertSee('Menunggu Persetujuan');
        $this->actingAs($kepala)->get(route('sppd.index', ['filter' => 'approval']))->assertOk();

        Role::findByName('kepala_opd', 'web')->revokePermissionTo('sppd.approve');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $kepala->unsetRelation('roles')->unsetRelation('permissions');

        $this->actingAs($kepala)->get(route('sppd.calendar'))->assertDontSee('Menunggu Persetujuan');
        // Menu tersembunyi saja tidak cukup: URL-nya juga ditolak.
        $this->actingAs($kepala)->get(route('sppd.index', ['filter' => 'approval']))->assertForbidden();
        $this->actingAs($kepala)->get(route('sppd.index'))->assertOk();
    }

    public function test_halaman_persetujuan_tidak_bisa_dibuka_dengan_mengubah_filter(): void
    {
        // Kasubag tidak memegang sppd.approve; filter adalah properti #[Url] yang bisa diubah klien.
        Livewire::actingAs($this->kasubag)->test(SppdIndex::class)
            ->assertOk()
            ->set('filter', 'approval')
            ->assertForbidden();
    }

    public function test_permission_dpa_ditegakkan_di_server(): void
    {
        $budget = new Budget(['department_id' => $this->dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $this->actingAs($this->admin)->get(route('master.budgets.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('master.budgets.edit', $budget->id))->assertOk();

        // DPA instansi lain tidak bisa diubah atau dihapus lewat URL.
        $lain = Department::create(['name' => 'Dinas Lain', 'type' => DepartmentType::OPD, 'level' => 1]);
        $budgetLain = new Budget(['department_id' => $lain->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budgetLain->save();
        $this->actingAs($this->admin)->get(route('master.budgets.edit', $budgetLain->id))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('master.budgets.destroy', $budgetLain->id))->assertForbidden();
        $this->assertNotNull(Budget::find($budgetLain->id));

        $this->revokeFromAdmin(['budget.create', 'budget.edit', 'budget.delete']);

        $this->actingAs($this->admin)->get(route('master.budgets.create'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('master.budgets.edit', $budget->id))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('master.budgets.destroy', $budget->id))->assertForbidden();

        Livewire::actingAs($this->admin)->test(\App\Livewire\Budgets\BudgetIndex::class)
            ->call('confirmDelete', $budget->id)
            ->assertSet('showDeleteModal', false)
            ->set('deletingId', $budget->id)
            ->call('delete');

        $this->assertNotNull(Budget::find($budget->id));
        // Daftar DPA tetap bisa dibuka.
        $this->actingAs($this->admin)->get(route('master.budgets.index'))->assertOk();
    }

    public function test_permission_tersembunyi_tetap_utuh_saat_hapus_semua_lalu_simpan(): void
    {
        $superAdmin = $this->user('super_admin');
        $role = Role::findByName('kasubid_kasubag', 'web');

        Livewire::actingAs($superAdmin)->test(RoleForm::class, ['role' => $role])
            ->call('toggleAll', false)
            ->assertSet('selectedPermissions', fn (array $selected) => collect($selected)->sort()->values()->all() === ['report.view', 'sppd.view', 'sppd.view_all'])
            ->call('toggleAll', true)
            ->assertSet('selectedPermissions', fn (array $selected) => in_array('report.view', $selected, true)
                && in_array('budget.delete', $selected, true)
                && ! in_array('user.create', $selected, true))
            ->call('toggleAll', false)
            ->call('save');

        $this->assertSame(['report.view', 'sppd.view', 'sppd.view_all'], $role->fresh()->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_simpan_role_tetap_di_halaman_form(): void
    {
        $superAdmin = $this->user('super_admin');
        $role = Role::findByName('kasubid_kasubag', 'web');

        Livewire::actingAs($superAdmin)->test(RoleForm::class, ['role' => $role])
            ->set('label', 'Kasubag Baru')
            ->call('save')
            ->assertNoRedirect()
            ->assertDispatched('toast', type: 'success');

        $this->assertSame('Kasubag Baru', $role->fresh()->label);

        // Role baru: lanjut ke halaman edit role itu, bukan kembali ke daftar.
        Livewire::actingAs($superAdmin)->test(RoleForm::class)
            ->set('name', 'role_uji')
            ->set('label', 'Role Uji')
            ->call('save')
            ->assertRedirect(route('master.roles.edit', Role::findByName('role_uji', 'web')));
    }

    public function test_migrasi_memindahkan_hak_tanpa_mengubah_akses_yang_berlaku(): void
    {
        // Keadaan database sebelum migrasi (permission lama per role).
        $old = [
            'admin_opd' => ['sppd.create', 'sppd.delete', 'sppd.view'],
            'kasubid_kasubag' => ['sppd.view', 'sppd.approve'],
            'staf' => ['sppd.create', 'sppd.edit', 'sppd.view'],
            'anggota_dprd' => ['sppd.create', 'sppd.view'],
            'kepala_opd' => ['sppd.view', 'sppd.approve', 'sppd.sign'],
        ];
        foreach ($old as $name => $permissions) {
            Role::findByName($name, 'web')->syncPermissions($permissions);
        }
        Permission::whereIn('name', ['cost.view', 'cost.manage', 'document.verify'])->delete();

        (require database_path('migrations/2026_11_12_000001_add_sppd_operator_permissions.php'))->up();

        $perms = fn (string $role) => Role::findByName($role, 'web')->permissions()->pluck('name')->sort()->values()->all();

        $this->assertSame(['cost.manage', 'cost.view', 'document.verify', 'sppd.create', 'sppd.delete', 'sppd.view'], $perms('admin_opd'));
        $this->assertSame(['cost.manage', 'cost.view', 'document.verify', 'sppd.create', 'sppd.delete', 'sppd.view'], $perms('kasubid_kasubag'));
        $this->assertSame(['cost.view', 'sppd.edit', 'sppd.view'], $perms('staf'));
        $this->assertSame(['cost.view', 'sppd.view'], $perms('anggota_dprd'));
        $this->assertSame(['cost.view', 'sppd.approve', 'sppd.sign', 'sppd.view'], $perms('kepala_opd'));
        $this->assertTrue(Role::findByName('super_admin', 'web')->hasPermissionTo('cost.manage'));
    }
}
