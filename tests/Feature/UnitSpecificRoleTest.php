<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\PositionFormModal;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\Position;
use App\Models\SppdWorkflow;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Pimpinan/Sekretaris di Kecamatan, Kelurahan, dan Puskesmas memakai role khusus unitnya
 * (Camat/Sekcam, Lurah/Seklur, Kepala Puskesmas), bukan Kepala/Sekretaris OPD. Alur persetujuan
 * tetap menemukan mereka lewat sinonim role tahap.
 */
class UnitSpecificRoleTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'migrations/2026_11_20_000001_assign_unit_specific_leader_roles.php';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(SppdWorkflowSeeder::class);
    }

    private function unit(string $name, DepartmentType $type, ?Department $parent = null): Department
    {
        return Department::create(['name' => $name, 'type' => $type, 'level' => $parent ? 2 : 1, 'parent_id' => $parent?->id]);
    }

    private function user(string $role, Department $department): User
    {
        $user = User::factory()->create(['department_id' => $department->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_role_jabatan_pimpinan_dan_sekretaris_mengikuti_tipe_unit(): void
    {
        $pimpinan = Position::create(['name' => 'Pimpinan Unit', 'default_role' => 'kepala_opd']);
        $sekretaris = Position::create(['name' => 'Sekretaris Unit', 'default_role' => 'sekretaris_opd']);

        $expected = [
            [DepartmentType::KECAMATAN, 'camat', 'sekcam'],
            [DepartmentType::KELURAHAN, 'lurah', 'seklur'],
            [DepartmentType::OPD, 'kepala_opd', 'sekretaris_opd'],
            [DepartmentType::RUMAH_SAKIT, 'kepala_opd', 'sekretaris_opd'],
        ];
        foreach ($expected as [$type, $kepala, $sekretarisRole]) {
            $unit = $this->unit('Unit ' . $type->value, $type);
            $this->assertSame($kepala, $pimpinan->roleForDepartment($unit), $type->value);
            $this->assertSame($sekretarisRole, $sekretaris->roleForDepartment($unit), $type->value);
        }

        $this->assertSame('kapus', $pimpinan->roleForDepartment($this->unit('Puskesmas Puuwatu', DepartmentType::PUSKESMAS)));
    }

    public function test_admin_puskesmas_memilih_jabatan_pimpinan_mendapat_role_kepala_puskesmas(): void
    {
        $puskesmas = $this->unit('Puskesmas Puuwatu', DepartmentType::PUSKESMAS);
        $admin = $this->user('admin_opd', $puskesmas);
        $kapus = Position::create(['name' => 'Kepala Puskesmas', 'default_role' => 'kepala_opd']);

        Livewire::actingAs($admin)->test(UserForm::class)
            ->set('name', 'Dr. Sari')
            ->set('username', 'sari')
            ->set('department_id', $puskesmas->id)
            ->call('selectPosition', $kapus->id)
            ->assertSet('role', 'kapus')
            ->assertViewHas('roleOptions', function (array $options) {
                $values = array_column($options, 'value');

                // Puskesmas tidak punya role khusus Sekretaris, jadi hanya Kepala OPD yang diganti.
                return in_array('kapus', $values, true)
                    && ! array_intersect(['kepala_opd', 'camat', 'sekcam', 'lurah', 'seklur'], $values);
            })
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['kapus'], User::where('username', 'sari')->firstOrFail()->getRoleNames()->all());
    }

    public function test_role_dihitung_ulang_saat_unit_diganti_setelah_memilih_jabatan(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $dinas = $this->unit('Dinas Sosial', DepartmentType::OPD);
        $kecamatan = $this->unit('Kecamatan Puuwatu', DepartmentType::KECAMATAN);
        $kelurahan = $this->unit('Kelurahan Watulondo', DepartmentType::KELURAHAN, $kecamatan);
        $sekretaris = Position::create(['name' => 'Sekretaris Unit', 'default_role' => 'sekretaris_opd']);

        Livewire::actingAs($superAdmin)->test(UserForm::class)
            ->set('department_id', $dinas->id)
            ->call('selectPosition', $sekretaris->id)
            ->assertSet('role', 'sekretaris_opd')
            ->set('department_id', $kelurahan->id)
            ->assertSet('role', 'seklur')
            ->set('department_id', $kecamatan->id)
            ->assertSet('role', 'sekcam');
    }

    public function test_modal_tambah_jabatan_menyebut_role_sesuai_unit(): void
    {
        $puskesmas = $this->unit('Puskesmas Puuwatu', DepartmentType::PUSKESMAS);
        $kecamatan = $this->unit('Kecamatan Puuwatu', DepartmentType::KECAMATAN);
        $kelurahan = $this->unit('Kelurahan Watulondo', DepartmentType::KELURAHAN, $kecamatan);
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        // Admin OPD: unitnya sendiri.
        Livewire::actingAs($this->user('admin_opd', $puskesmas))->test(PositionFormModal::class)
            ->call('open', null, 'Pimpinan Unit')
            ->set('tier', 'pimpinan')
            ->assertViewHas('summaryRole', 'Kepala Puskesmas')
            ->assertViewHas('roleVariants', '');

        // Dari form pegawai: role khusus ditebak dari unit kerja terpilih, tanpa pilihan Role.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Sekretaris Unit', null, (string) $kelurahan->id)
            ->set('tier', 'sekretaris')
            ->assertSet('unitRole', 'seklur')
            ->assertViewHas('summaryRole', 'Seklur')
            ->assertViewHas('unitRoleOptions', [])
            ->assertDontSee('Khusus unit')
            ->set('tier', 'pimpinan')
            ->assertSet('unitRole', 'lurah')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('lurah', Position::where('name', 'Sekretaris Unit')->value('default_role'));

        // Super Admin di Data Jabatan: role umum plus variasinya per tipe unit.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Pimpinan Unit')
            ->set('tier', 'pimpinan')
            ->assertViewHas('summaryRole', 'Kepala OPD')
            ->assertViewHas('roleVariants', 'Camat di Kecamatan, Lurah di Kelurahan dan Kepala Puskesmas di Puskesmas')
            ->assertSee('Menyesuaikan unit kerja');

        // Jabatan kewilayahan selalu memakai tipe unitnya sendiri.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Camat')
            ->set('tier', 'pimpinan')
            ->assertViewHas('summaryRole', 'Camat')
            ->assertViewHas('roleVariants', '');
    }

    public function test_modal_jabatan_bisa_memilih_role_khusus(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $values = fn (array $options) => array_column($options, 'value');

        // Sekretaris: umum, Sekcam, Seklur. Role khusus Pimpinan yang tak cocok direset.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Pimpinan Unit')
            ->set('tier', 'pimpinan')
            ->set('unitRole', 'kapus')
            ->set('tier', 'sekretaris')
            ->assertSet('unitRole', '')
            ->assertViewHas('unitRoleOptions', fn (array $options) => $values($options) === ['', 'sekcam', 'seklur']);

        // Nama jabatan kewilayahan langsung memilih role khususnya.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Sekretaris Camat')
            ->assertSet('tier', 'sekretaris')
            ->assertSet('unitRole', 'sekcam');

        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Pimpinan Puskesmas')
            ->set('tier', 'pimpinan')
            ->assertViewHas('unitRoleOptions', fn (array $options) => $values($options) === ['', 'camat', 'lurah', 'kapus'])
            ->set('unitRole', 'kapus')
            ->assertViewHas('summaryRole', 'Kepala Puskesmas')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('kapus', Position::where('name', 'Pimpinan Puskesmas')->value('default_role'));

        // Role khusus di luar pilihan tingkat (manipulasi klien) kembali ke role tingkat.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', null, 'Analis Data')
            ->set('tier', 'staf')
            ->set('unitRole', 'camat')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('staf', Position::where('name', 'Analis Data')->value('default_role'));
    }

    public function test_edit_jabatan_role_khusus_dan_tabel_data_jabatan(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $position = Position::create(['name' => 'Pimpinan Puskesmas', 'default_role' => 'kapus', 'uniqueness_scope' => 'department', 'level' => 20]);

        Livewire::actingAs($superAdmin)->test(\App\Livewire\PositionIndex::class)->assertSee('Kepala Puskesmas');

        // Ganti nama saja: role khusus tetap.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', $position->id)
            ->assertSet('tier', 'pimpinan')
            ->assertSet('unitRole', 'kapus')
            ->set('name', 'Pimpinan Puskesmas Induk')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('kapus', $position->fresh()->default_role);

        // Kembali ke role umum.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', $position->id)
            ->set('unitRole', '')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('kepala_opd', $position->fresh()->default_role);
    }

    public function test_role_khusus_jabatan_mengunci_tipe_unit(): void
    {
        $kapus = Position::create(['name' => 'Pimpinan Puskesmas', 'default_role' => 'kapus']);
        $seklur = Position::create(['name' => 'Sekretaris Unit', 'default_role' => 'seklur']);
        $kecamatan = $this->unit('Kecamatan Puuwatu', DepartmentType::KECAMATAN);

        $this->assertSame('kapus', $kapus->roleForDepartment($this->unit('Puskesmas Puuwatu', DepartmentType::PUSKESMAS)));
        $this->assertSame('staf', $kapus->roleForDepartment($this->unit('Dinas Kesehatan', DepartmentType::DINKES)));
        $this->assertSame('seklur', $seklur->roleForDepartment($this->unit('Kelurahan Watulondo', DepartmentType::KELURAHAN, $kecamatan)));
        $this->assertSame('staf', $seklur->roleForDepartment($kecamatan));
        $this->assertSame(\App\Enums\PositionTier::PIMPINAN, \App\Enums\PositionTier::fromRole('camat'));
        $this->assertSame(\App\Enums\PositionTier::SEKRETARIS, \App\Enums\PositionTier::fromRole('seklur'));
    }

    public function test_tambah_jabatan_dari_form_pegawai_menunggu_unit_kerja(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->unit('Dinas Sosial', DepartmentType::OPD);
        $puskesmas = $this->unit('Puskesmas Puuwatu', DepartmentType::PUSKESMAS);

        Livewire::actingAs($superAdmin)->test(UserForm::class)
            ->assertSet('department_id', '')
            ->assertSee('pilih unit kerja terlebih dahulu')
            ->set('department_id', $puskesmas->id)
            ->assertDontSee('pilih unit kerja terlebih dahulu');
    }

    public function test_alur_kelurahan_menemukan_seklur_dan_lurah(): void
    {
        $kecamatan = $this->unit('Kecamatan Puuwatu', DepartmentType::KECAMATAN);
        $kelurahan = $this->unit('Kelurahan Watulondo', DepartmentType::KELURAHAN, $kecamatan);
        $seklur = $this->user('seklur', $kelurahan);
        $lurah = $this->user('lurah', $kelurahan);
        $staf = $this->user('staf', $kelurahan);
        $service = app(SppdWorkflowService::class);

        $this->assertSame($seklur->id, $service->resolveApprover('sekretaris_opd', $staf)?->id);
        $this->assertSame($lurah->id, $service->resolveApprover('kepala_opd', $staf)?->id);
        $this->assertSame('sekretaris_opd', $service->approverRoleOf($seklur));
        $this->assertTrue($seklur->can('sppd.approve'));
        $this->assertFalse($seklur->can('sppd.sign'), 'Seklur setara Sekretaris OPD: tanpa TTE');
    }

    public function test_migrasi_memindahkan_pemegang_role_umum_ke_role_khusus(): void
    {
        // Kondisi sebelum migrasi: tanpa role Seklur, izin role khusus berbeda dari role umumnya.
        Role::findByName('seklur')->delete();
        foreach (SppdWorkflow::all() as $workflow) {
            $workflow->update(['applicant_role' => array_values(array_diff($workflow->applicant_role ?? [], ['seklur'])) ?: null]);
        }
        Role::findByName('sekcam')->givePermissionTo('sppd.sign');
        Role::findByName('kepala_opd')->givePermissionTo('user.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $kecamatan = $this->unit('Kecamatan Puuwatu', DepartmentType::KECAMATAN);
        $kelurahan = $this->unit('Kelurahan Watulondo', DepartmentType::KELURAHAN, $kecamatan);
        $puskesmas = $this->unit('Puskesmas Puuwatu', DepartmentType::PUSKESMAS);
        $dinas = $this->unit('Dinas Sosial', DepartmentType::OPD);
        $people = [
            'camat' => [$this->user('kepala_opd', $kecamatan), 'camat'],
            'sekcam' => [$this->user('sekretaris_opd', $kecamatan), 'sekcam'],
            'lurah' => [$this->user('kepala_opd', $kelurahan), 'lurah'],
            'seklur' => [$this->user('sekretaris_opd', $kelurahan), 'seklur'],
            'kapus' => [$this->user('kepala_opd', $puskesmas), 'kapus'],
            'kadis' => [$this->user('kepala_opd', $dinas), 'kepala_opd'],
            'staf' => [$this->user('staf', $kelurahan), 'staf'],
        ];

        $migration = require database_path(self::MIGRATION);
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($people as $key => [$user, $role]) {
            $this->assertSame([$role], $user->fresh()->getRoleNames()->all(), $key);
        }

        $log = Activity::where('subject_type', User::class)->where('subject_id', $people['seklur'][0]->id)->latest('id')->first();
        $this->assertSame('sekretaris_opd', $log?->attribute_changes?->get('old')['role'] ?? null);
        $this->assertSame('seklur', $log?->attribute_changes?->get('attributes')['role'] ?? null);

        // Izin role khusus = izin role umum di database.
        $perms = fn (string $role) => Role::findByName($role)->permissions->pluck('name')->sort()->values()->all();
        $this->assertSame($perms('sekretaris_opd'), $perms('seklur'));
        $this->assertSame($perms('sekretaris_opd'), $perms('sekcam'));
        $this->assertSame($perms('kepala_opd'), $perms('camat'));
        $this->assertContains('user.view', $perms('kapus'));

        $sekcamWorkflows = SppdWorkflow::all()->filter(fn ($w) => in_array('sekcam', $w->applicant_role ?? [], true));
        $this->assertNotEmpty($sekcamWorkflows);
        $this->assertTrue($sekcamWorkflows->every(fn ($w) => in_array('seklur', $w->applicant_role, true)));

        // Dijalankan ulang: tidak ada perubahan atau log baru.
        $logCount = Activity::count();
        $migration->up();
        $this->assertSame($logCount, Activity::count());

        $migration->down();
        $this->assertSame(['kepala_opd'], $people['lurah'][0]->fresh()->getRoleNames()->all());
        $this->assertSame(['sekretaris_opd'], $people['seklur'][0]->fresh()->getRoleNames()->all());
        $this->assertSame(['kepala_opd'], $people['kadis'][0]->fresh()->getRoleNames()->all());
    }
}
