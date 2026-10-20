<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\PositionScope;
use App\Livewire\PositionFormModal;
use App\Livewire\PositionIndex;
use App\Livewire\UserForm;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\SppdFollower;
use App\Models\SppdRequest;
use App\Models\SppdWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PositionDefaultRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'kepala_opd', 'sekretaris_opd', 'kabid_irban_kabag', 'staf'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        // Fixture alur (bukan salinan produksi): OPD & kewilayahan hanya mengenal Staf,
        // Sekretaris OPD, dan Kepala OPD; Kabid/Kabag hanya di alur Setda.
        SppdWorkflow::create([
            'name' => 'Staf',
            'department_type' => ['opd', 'kecamatan', 'kelurahan', 'puskesmas'],
            'applicant_role' => ['staf'],
            'steps' => [['role' => 'sekretaris_opd'], ['role' => 'kepala_opd']],
            'is_active' => true,
        ]);
        SppdWorkflow::create([
            'name' => 'Kasubag & Staf (Setda)',
            'department_type' => ['setda', 'bagian', 'subbagian'],
            'applicant_role' => ['kabid_irban_kabag', 'kasubid_kasubag', 'staf'],
            'steps' => [['role' => 'kabid_irban_kabag'], ['role' => 'sekda']],
            'is_active' => true,
        ]);
    }

    private function makeDepartment(string $name, DepartmentType $type = DepartmentType::OPD): Department
    {
        return Department::create([
            'name' => $name,
            'type' => $type,
            'level' => 1,
        ]);
    }

    private function makeUser(string $role, ?Department $department = null): User
    {
        $user = User::factory()->create(['department_id' => $department?->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_role_default_ditebak_dari_nama_jabatan(): void
    {
        $cases = [
            'Kepala Dinas Kesehatan' => 'kepala_opd',
            'Inspektur' => 'kepala_opd',
            'Sekretaris Dinas' => 'sekretaris_opd',
            'Kepala Bidang Anggaran' => 'kabid_irban_kabag',
            'Kepala Sub Bagian Umum dan Kepegawaian' => 'kasubid_kasubag',
            'Kepala Seksi Irigasi' => 'kasubid_kasubag',
            'Camat' => 'kepala_opd',
            'Lurah' => 'kepala_opd',
            'Sekretaris Lurah' => 'sekretaris_opd',
            'Sekretaris Camat' => 'sekretaris_opd',
            'Kepala UPTD Puskesmas' => 'kepala_opd',
            'Asisten Administrasi Umum' => 'asisten',
            'Asisten Apoteker' => 'staf',
            'Pranata Komputer' => 'staf',
            'Kepala UPTD Terminal' => null,
        ];

        foreach ($cases as $name => $expected) {
            $this->assertSame($expected, Position::guessDefaultRole($name), $name);
        }
    }

    public function test_admin_opd_menambah_jabatan_dengan_tingkat_tertebak(): void
    {
        $admin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Kesehatan'));

        Livewire::actingAs($admin)
            ->test(PositionFormModal::class)
            ->call('open')
            ->set('name', 'Kepala Bidang Pengawasan')
            ->assertSet('tier', 'kabid')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('position-saved');

        $position = Position::where('name', 'Kepala Bidang Pengawasan')->firstOrFail();
        $this->assertSame('kabid_irban_kabag', $position->default_role);
        $this->assertSame(40, $position->level);
        $this->assertSame(PositionScope::DEPARTMENT, $position->uniqueness_scope);
        $this->assertSame($admin->id, $position->created_by);
    }

    public function test_nama_jabatan_duplikat_ditolak(): void
    {
        Position::create(['name' => 'Staf']);
        $admin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Sosial'));

        Livewire::actingAs($admin)
            ->test(PositionFormModal::class)
            ->call('open')
            ->set('name', 'staf')
            ->call('save')
            ->assertHasErrors('name');

        $this->assertSame(1, Position::count());
    }

    public function test_admin_opd_tidak_bisa_mengubah_jabatan_opd_lain_atau_bawaan(): void
    {
        $other = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Pangan'));
        $admin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Sosial'));
        $colleague = $this->makeUser('staf', $admin->department);

        $foreign = Position::create(['name' => 'Analis Pangan', 'created_by' => $other->id]);
        $system = Position::create(['name' => 'Kepala Dinas']);
        $own = Position::create(['name' => 'Analis Sosial', 'created_by' => $colleague->id]);

        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', $foreign->id)->assertForbidden();
        Livewire::actingAs($admin)->test(PositionIndex::class)
            ->call('confirmDelete', $system->id)->assertForbidden();

        // Jabatan tambahan rekan satu OPD tetap bisa dikelola.
        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', $own->id)
            ->assertSet('show', true);
    }

    public function test_memilih_jabatan_mengisi_role_tanpa_menimpa_admin_opd(): void
    {
        $admin = $this->makeUser('super_admin');
        $department = $this->makeDepartment('Dinas Pangan');
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);
        $staf = Position::create(['name' => 'Staf', 'default_role' => 'staf']);

        $form = Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('department_id', $department->id)
            ->call('selectPosition', $kadis->id)
            ->assertSet('role', 'kepala_opd');

        // Role operasional tidak ditimpa otomatis, hanya lewat tombol "Gunakan role ini".
        $form->set('role', 'admin_opd')
            ->call('selectPosition', $staf->id)
            ->assertSet('role', 'admin_opd')
            ->call('applyPositionRole')
            ->assertSet('role', 'staf');
    }

    public function test_admin_opd_tidak_bisa_memilih_role_sendiri(): void
    {
        $department = $this->makeDepartment('Dinas Kesehatan');
        $admin = $this->makeUser('admin_opd', $department);
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->assertSet('role', 'staf')
            ->set('name', 'Budi Santoso')
            ->set('username', 'budi')
            ->set('department_id', $department->id)
            ->call('selectPosition', $kadis->id)
            ->assertSet('role', 'kepala_opd')
            // Manipulasi request: role bebas diabaikan, tetap mengikuti jabatan.
            ->set('role', 'super_admin')
            ->assertSet('role', 'kepala_opd')
            ->call('save')
            ->assertHasNoErrors();

        $budi = User::where('username', 'budi')->firstOrFail();
        $this->assertSame(['kepala_opd'], $budi->getRoleNames()->all());
    }

    public function test_admin_opd_tidak_mengubah_role_admin_opd_lain(): void
    {
        $department = $this->makeDepartment('Dinas Sosial');
        $admin = $this->makeUser('admin_opd', $department);
        $colleague = $this->makeUser('admin_opd', $department);
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['user' => $colleague])
            ->call('selectPosition', $kadis->id)
            ->assertSet('role', 'admin_opd')
            ->call('applyPositionRole')
            ->assertSet('role', 'admin_opd');
    }

    public function test_role_jabatan_menyesuaikan_alur_unit_kerja(): void
    {
        $kabag = Position::create(['name' => 'Kepala Bagian Hukum', 'default_role' => 'kabid_irban_kabag']);
        $seklur = Position::create(['name' => 'Sekretaris Lurah', 'default_role' => 'sekretaris_opd']);

        // Kabid/Kabag tidak dikenal alur Dinas → Staf; di Setda tetap Kabid/Kabag.
        $this->assertSame('staf', $kabag->roleForDepartment($this->makeDepartment('Dinas Sosial')));
        $this->assertSame('kabid_irban_kabag', $kabag->roleForDepartment($this->makeDepartment('Bagian Hukum', DepartmentType::BAGIAN)));
        $this->assertSame('sekretaris_opd', $seklur->roleForDepartment($this->makeDepartment('Kelurahan Bende', DepartmentType::KELURAHAN)));
    }

    public function test_sinkron_role_massal_hanya_menaikkan_staf_dan_melewati_kandidat_ganda(): void
    {
        $kelurahan = $this->makeDepartment('Kelurahan Bende', DepartmentType::KELURAHAN);
        $lurah = Position::create(['name' => 'Lurah', 'default_role' => 'kepala_opd']);
        $seklur = Position::create(['name' => 'Sekretaris Lurah', 'default_role' => 'sekretaris_opd']);

        $makeOfficial = function (Position $position, string $role) use ($kelurahan) {
            $user = $this->makeUser($role, $kelurahan);
            $user->update(['position_id' => $position->id]);

            return $user;
        };
        $pakLurah = $makeOfficial($lurah, 'staf');
        $seklurA = $makeOfficial($seklur, 'staf');
        $seklurB = $makeOfficial($seklur, 'staf');
        $operator = $makeOfficial($seklur, 'admin_opd');

        $this->artisan('positions:sync-roles', ['--type' => ['kelurahan'], '--dry-run' => true])->assertSuccessful();
        $this->assertSame(['staf'], $pakLurah->fresh()->getRoleNames()->all(), 'dry-run tidak menyimpan');

        // Filter jabatan: hanya Lurah yang diproses.
        $this->artisan('positions:sync-roles', ['--type' => ['kelurahan'], '--position' => ['lurah']])->assertSuccessful();
        $this->assertSame(['kepala_opd'], $pakLurah->fresh()->getRoleNames()->all());
        $this->assertSame(['staf'], $seklurA->fresh()->getRoleNames()->all());

        $this->artisan('positions:sync-roles', ['--type' => ['kelurahan']])->assertSuccessful();

        $this->assertSame(['kepala_opd'], $pakLurah->fresh()->getRoleNames()->all());
        // Dua Sekretaris Lurah di satu kelurahan: tidak bisa dipastikan, keduanya dilewati.
        $this->assertSame(['staf'], $seklurA->fresh()->getRoleNames()->all());
        $this->assertSame(['staf'], $seklurB->fresh()->getRoleNames()->all());
        $this->assertSame(['admin_opd'], $operator->fresh()->getRoleNames()->all(), 'selain Staf tidak disentuh');
    }

    public function test_jabatan_kewilayahan_di_unit_yang_salah_tidak_mendapat_role_pimpinan(): void
    {
        $camat = Position::create(['name' => 'Camat', 'default_role' => 'kepala_opd']);

        $this->assertSame('kepala_opd', $camat->roleForDepartment($this->makeDepartment('Kecamatan Kadia', DepartmentType::KECAMATAN)));
        $this->assertSame('staf', $camat->roleForDepartment($this->makeDepartment('Dinas Perumahan')));
    }

    public function test_penyetuju_tidak_dicari_keluar_dari_kelurahan(): void
    {
        Role::firstOrCreate(['name' => 'sekda', 'guard_name' => 'web'], ['label' => 'sekda']);
        $kecamatan = $this->makeDepartment('Kecamatan Kendari Barat', DepartmentType::KECAMATAN);
        $kelurahan = Department::create(['name' => 'Kelurahan Watu-watu', 'type' => DepartmentType::KELURAHAN, 'parent_id' => $kecamatan->id, 'level' => 2]);
        $seksi = Department::create(['name' => 'Seksi Pemerintahan', 'type' => DepartmentType::KECAMATAN, 'parent_id' => $kecamatan->id, 'level' => 2]);

        $sekcam = $this->makeUser('sekretaris_opd', $kecamatan);
        $sekda = $this->makeUser('sekda');
        $service = app(\App\Services\SppdWorkflowService::class);

        // Kelurahan tanpa Sekretaris Lurah: tidak dialihkan ke Sekcam.
        $this->assertNull($service->resolveApprover('sekretaris_opd', $this->makeUser('staf', $kelurahan)));
        // Sub-unit kecamatan tetap mencari ke induknya; role tingkat kota tetap ditemukan.
        $this->assertSame($sekcam->id, $service->resolveApprover('sekretaris_opd', $this->makeUser('staf', $seksi))?->id);
        $this->assertSame($sekda->id, $service->resolveApprover('sekda', $this->makeUser('staf', $kelurahan))?->id);
    }

    public function test_filter_jabatan_tidak_menyembunyikan_kandidat_ganda(): void
    {
        $dinas = $this->makeDepartment('Dinas Kelautan dan Perikanan');
        $kadis = Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);
        $kadisPerikanan = Position::create(['name' => 'Kepala Dinas Perikanan', 'default_role' => 'kepala_opd']);

        $makmur = $this->makeUser('staf', $dinas);
        $makmur->update(['position_id' => $kadis->id]);
        $agus = $this->makeUser('staf', $dinas);
        $agus->update(['position_id' => $kadisPerikanan->id]);

        $this->artisan('positions:sync-roles', ['--position' => ['Kepala Dinas']])->assertSuccessful();

        $this->assertSame(['staf'], $makmur->fresh()->getRoleNames()->all());
        $this->assertSame(['staf'], $agus->fresh()->getRoleNames()->all());
    }

    public function test_tab_sumber_memisahkan_jabatan_bawaan_dan_input_manual(): void
    {
        $department = $this->makeDepartment('Dinas Sosial');
        $admin = $this->makeUser('admin_opd', $department);
        $otherAdmin = $this->makeUser('admin_opd', $this->makeDepartment('Dinas Pangan'));

        Position::create(['name' => 'Jabatan Bawaan']);
        Position::create(['name' => 'Jabatan Input Saya', 'created_by' => $admin->id]);
        Position::create(['name' => 'Jabatan Input OPD Lain', 'created_by' => $otherAdmin->id]);

        $page = Livewire::actingAs($admin)->test(PositionIndex::class)
            ->assertSee(['Bawaan Sistem', 'Ditambahkan OPD Saya'])
            ->assertDontSee('Tambahan Semua OPD');

        $page->call('setSource', 'system')
            ->assertSee('Jabatan Bawaan')->assertDontSee('Jabatan Input Saya')->assertDontSee('Jabatan Input OPD Lain');
        // Tab "Tambahan Semua OPD" khusus Super Admin: admin OPD kembali ke "Semua".
        $page->call('setSource', 'manual')->assertSet('sourceFilter', '');
        $page->call('setSource', 'mine')
            ->assertSee('Jabatan Input Saya')->assertDontSee('Jabatan Input OPD Lain')->assertDontSee('Jabatan Bawaan');
        // Nilai tak dikenal kembali ke "Semua".
        $page->call('setSource', 'ngawur')->assertSet('sourceFilter', '');

        Livewire::actingAs($this->makeUser('super_admin'))->test(PositionIndex::class)
            ->assertSee('Tambahan Semua OPD')
            ->call('setSource', 'manual')
            ->assertDontSee('Jabatan Bawaan')->assertSee('Jabatan Input Saya')->assertSee('Jabatan Input OPD Lain');
    }

    public function test_level_jabatan_dan_bobot_golongan(): void
    {
        $this->assertSame(1, Position::levelForRole('walikota'));
        $this->assertSame(20, Position::levelForRole('kepala_opd'));
        $this->assertSame(50, Position::levelForRole('kasubid_kasubag'));
        $this->assertSame(1000, Position::levelForRole('staf'));
        $this->assertSame(1000, Position::levelForRole(null));

        $weight = fn (string $group) => (new Rank(['group' => $group]))->weight();
        $this->assertGreaterThan($weight('IV/a'), $weight('IV/e'));
        $this->assertGreaterThan($weight('III/d'), $weight('IV/a'));
        $this->assertSame($weight('III/a'), $weight('IX'), 'PPPK IX setara III/a');
        $this->assertGreaterThan($weight('II/d'), $weight('IX'));
    }

    public function test_pengikut_spt_diurutkan_dari_jabatan_dan_golongan_tertinggi(): void
    {
        $follower = function (string $name, ?int $level, ?string $group): SppdFollower {
            $user = new User(['name' => $name]);
            $user->setRelation('position', $level === null ? null : new Position(['level' => $level]));
            $user->setRelation('rank', $group === null ? null : new Rank(['group' => $group]));

            return (new SppdFollower())->setRelation('user', $user);
        };

        $sppd = (new SppdRequest())->setRelation('followers', new \Illuminate\Database\Eloquent\Collection([
            $follower('Staf III/a', 1000, 'III/a'),
            $follower('Tanpa jabatan', null, 'IV/a'),
            $follower('Kasi', 50, 'III/c'),
            $follower('Staf III/d', 1000, 'III/d'),
            $follower('Kabid', 40, 'IV/a'),
        ]));

        $this->assertSame(
            ['Kabid', 'Kasi', 'Tanpa jabatan', 'Staf III/d', 'Staf III/a'],
            $sppd->followersForSpt()->map(fn ($f) => $f->user->name)->all(),
        );
    }

    public function test_super_admin_memakai_form_tingkat_dengan_level_otomatis_yang_bisa_diubah(): void
    {
        $superAdmin = $this->makeUser('super_admin');

        // Tambah: tingkat & level terisi otomatis dari nama, level tetap bisa diubah.
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open')
            ->set('name', 'Kepala Seksi Irigasi')
            ->assertSet('tier', 'kasi')
            ->assertSet('level', 50)
            ->set('level', 45)
            ->call('save')
            ->assertHasNoErrors();

        $kasi = Position::where('name', 'Kepala Seksi Irigasi')->firstOrFail();
        $this->assertSame('kasubid_kasubag', $kasi->default_role);
        $this->assertSame(PositionScope::DEPARTMENT, $kasi->uniqueness_scope);
        $this->assertSame(45, $kasi->level);

        // Ganti tingkat saat edit: level otomatis menyesuaikan selama belum diubah manual.
        $staf = Position::create(['name' => 'Analis Data', 'default_role' => 'staf', 'level' => 1000]);
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', $staf->id)
            ->set('tier', 'kabid')
            ->assertSet('level', 40);

        // Edit tanpa ganti tingkat: role khusus (Walikota) & cakupan sistem dipertahankan.
        $walikota = Position::create([
            'name' => 'Walikota', 'default_role' => 'walikota', 'uniqueness_scope' => 'system', 'level' => 1,
        ]);
        Livewire::actingAs($superAdmin)->test(PositionFormModal::class)
            ->call('open', $walikota->id)
            ->assertSet('tier', 'lainnya')
            ->set('name', 'Wali Kota')
            ->call('save')
            ->assertHasNoErrors();

        $walikota->refresh();
        $this->assertSame('walikota', $walikota->default_role);
        $this->assertSame(PositionScope::SYSTEM, $walikota->uniqueness_scope);
        $this->assertSame(1, $walikota->level);
    }

    public function test_halaman_data_jabatan_terbuka_untuk_admin_opd_saja(): void
    {
        $department = $this->makeDepartment('Dinas Perikanan');

        Position::create(['name' => 'Analis Perikanan', 'default_role' => 'staf']);

        // Kolom Pemangku hanya untuk Super Admin.
        $this->actingAs($this->makeUser('admin_opd', $department))
            ->get(route('master.positions.index'))
            ->assertOk()
            ->assertSee('Analis Perikanan')
            ->assertDontSee('Pemangku');

        $this->actingAs($this->makeUser('super_admin'))
            ->get(route('master.positions.index'))
            ->assertOk()
            ->assertSee('Pemangku');

        $this->actingAs($this->makeUser('staf', $department))
            ->get(route('master.positions.index'))
            ->assertForbidden();
    }
}
