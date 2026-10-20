<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Livewire\PositionFormModal;
use App\Livewire\Sppd\SppdCreate;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use App\Services\SppdWorkflowService;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Penetapan pejabat yang belum terdata langsung dari langkah 1 pembuatan SPPD. */
class AppointOfficialTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $staf;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'staf', 'kabid_irban_kabag', 'sekretaris_opd', 'kepala_opd', 'asisten', 'sekda', 'walikota'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->seed(SppdWorkflowSeeder::class);

        $this->dinas = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->user('sekretaris_opd');
        $this->staf = $this->user('staf');
    }

    private function user(string $role, ?Position $position = null, ?Department $department = null): User
    {
        $user = User::factory()->create([
            'department_id' => ($department ?? $this->dinas)->id,
            'position_id' => $position?->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function kepalaPosition(): Position
    {
        return Position::create(['name' => 'Kepala Dinas', 'default_role' => 'kepala_opd']);
    }

    public function test_admin_opd_menetapkan_lewat_jabatan_yang_sesuai(): void
    {
        $admin = $this->user('admin_opd');
        $kepala = $this->user('staf', $this->kepalaPosition());
        $lain = $this->user('staf');
        $service = app(SppdWorkflowService::class);

        $candidates = $service->officialCandidates('kepala_opd', $this->staf);
        // Jabatan yang sesuai tampil paling atas.
        $this->assertSame($kepala->id, $candidates[0]['id']);
        $this->assertTrue($candidates[0]['fits']);

        // Tanpa jabatan yang sesuai, Admin OPD diminta memilih jabatan dulu.
        try {
            $service->appointOfficial($admin, $this->staf, 'dalam_daerah', 'kepala_opd', $lain->id);
            $this->fail('Admin OPD seharusnya tidak dapat memberi role di luar jabatan.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Pilih jabatan', $e->getMessage());
        }
        $this->assertTrue($lain->fresh()->hasRole('staf'));

        $service->appointOfficial($admin, $this->staf, 'dalam_daerah', 'kepala_opd', $kepala->id);
        $this->assertTrue($kepala->fresh()->hasRole('kepala_opd'));
        $this->assertSame(['found', 'found'], array_column($service->simulateApprovals($this->staf, 'dalam_daerah'), 'status'));
    }

    public function test_jabatan_diubah_sekaligus_saat_menetapkan(): void
    {
        $admin = $this->user('admin_opd');
        $pegawai = $this->user('staf');
        $umum = $this->kepalaPosition();
        $spesifik = Position::create(['name' => 'Kepala Dinas Sosial', 'default_role' => 'kepala_opd']);
        Position::create(['name' => 'Camat', 'default_role' => 'kepala_opd']);
        $service = app(SppdWorkflowService::class);

        $positions = collect($service->officialPositions('kepala_opd', $this->staf, $pegawai->id));
        // Camat hanya untuk Kecamatan; nama yang cocok dengan instansi disarankan.
        $this->assertEqualsCanonicalizing([$umum->id, $spesifik->id], $positions->pluck('id')->all());
        $this->assertSame($spesifik->id, $positions->firstWhere('suggested', true)['id']);

        $service->appointOfficial($admin, $this->staf, 'dalam_daerah', 'kepala_opd', $pegawai->id, $spesifik->id);

        $pegawai->refresh();
        $this->assertSame($spesifik->id, $pegawai->position_id);
        $this->assertTrue($pegawai->hasRole('kepala_opd'));
    }

    public function test_jabatan_baru_lewat_form_lengkap_tingkatnya_mengikuti_tahap(): void
    {
        $admin = $this->user('admin_opd');

        // Form yang sama dengan Data Jabatan; tingkat dikunci ke role tahap.
        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', null, '', 'kepala_opd')
            ->assertSet('fixedRole', 'kepala_opd')
            ->assertSee('Mengikuti tahap alur persetujuan')
            ->set('tier', 'staf')
            ->assertSet('tier', 'pimpinan')
            ->set('name', 'kepala dinas sosial')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('position-saved');

        $position = Position::findByName('Kepala Dinas Sosial');
        $this->assertSame('kepala_opd', $position->default_role);
        $this->assertSame(\App\Enums\PositionScope::DEPARTMENT, $position->uniqueness_scope);
        $this->assertSame($admin->id, $position->created_by);

        // Nama yang sudah terdaftar tidak diduplikasi.
        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', null, 'Kepala Dinas Sosial', 'kepala_opd')
            ->call('save')
            ->assertHasErrors('name');
        $this->assertSame(1, Position::where('name', 'Kepala Dinas Sosial')->count());

        // Role tanpa tingkat umum (Sekda) tetap tersimpan dengan cakupan seluruh sistem.
        Livewire::actingAs($admin)->test(PositionFormModal::class)
            ->call('open', null, 'Sekretaris Daerah Kota', 'sekda')
            ->call('save')
            ->assertHasNoErrors();
        $sekda = Position::findByName('Sekretaris Daerah Kota');
        $this->assertSame('sekda', $sekda->default_role);
        $this->assertSame(\App\Enums\PositionScope::SYSTEM, $sekda->uniqueness_scope);
        $this->assertSame(Position::levelForRole('sekda'), $sekda->level);
    }

    public function test_jabatan_baru_dari_form_langsung_terpilih_di_wizard(): void
    {
        $admin = $this->user('admin_opd');
        $pegawai = $this->user('staf');
        $this->actingAs($admin);

        $component = Livewire::test(SppdCreate::class)
            ->call('selectUser', $this->staf->id)
            ->call('openOfficial', 'kepala_opd')
            ->call('pickOfficialCandidate', $pegawai->id)
            ->assertSet('officialStep', 2)
            ->assertSeeHtml("open-position-form', { role: 'kepala_opd' }");

        $position = Position::create(['name' => 'Kepala Dinas Sosial', 'default_role' => 'kepala_opd', 'uniqueness_scope' => 'department']);
        $component->dispatch('position-saved', id: $position->id, created: true)
            ->assertSet('officialPositionId', $position->id)
            ->call('officialNext')
            ->assertSet('officialStep', 3);
    }

    public function test_super_admin_bebas_menetapkan_tetapi_tidak_di_luar_instansi(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $lain = $this->user('staf');
        $dinasLain = Department::create(['name' => 'Dinas Pertanian', 'type' => DepartmentType::OPD, 'level' => 1]);
        $luar = $this->user('staf', null, $dinasLain);
        $service = app(SppdWorkflowService::class);

        $this->expectException(\RuntimeException::class);
        try {
            $service->appointOfficial($superAdmin, $this->staf, 'dalam_daerah', 'kepala_opd', $luar->id);
        } finally {
            $service->appointOfficial($superAdmin, $this->staf, 'dalam_daerah', 'kepala_opd', $lain->id);
            $this->assertTrue($lain->fresh()->hasRole('kepala_opd'));
            $this->assertTrue($luar->fresh()->hasRole('staf'));
        }
    }

    public function test_wizard_memandu_pegawai_jabatan_lalu_konfirmasi(): void
    {
        $admin = $this->user('admin_opd');
        $pegawai = $this->user('staf');
        $spesifik = Position::create(['name' => 'Kepala Dinas Sosial', 'default_role' => 'kepala_opd']);
        $bidang = Department::create(['name' => 'Bidang Rehabilitasi Sosial', 'type' => DepartmentType::OPD, 'level' => 2, 'parent_id' => $this->dinas->id]);
        $this->user('staf', null, $bidang);
        $this->actingAs($admin);

        $component = Livewire::test(SppdCreate::class)
            ->call('selectUser', $this->staf->id)
            // Modal tidak terbuka sendiri; tahap kosong punya tombol Lengkapi.
            ->assertSet('showOfficialModal', false)
            ->assertSeeHtml("openOfficial('kepala_opd')")
            // Tahap yang sudah terisi tidak bisa dibuka.
            ->call('openOfficial', 'sekretaris_opd')
            ->assertSet('showOfficialModal', false)
            ->call('openOfficial', 'kepala_opd')
            ->assertSet('showOfficialModal', true)
            ->assertSee('Lengkapi kepala_opd')
            ->assertSee('Langkah 1 dari 3')
            // Jabatan pegawai belum sesuai → langkah jabatan, saran langsung terpilih.
            ->call('pickOfficialCandidate', $pegawai->id)
            ->assertSet('officialStep', 2)
            ->assertSet('officialPositionId', $spesifik->id)
            ->assertSee('Disarankan')
            ->call('officialNext')
            ->assertSet('officialStep', 3)
            ->assertSee('Kepala Dinas Sosial')
            ->call('confirmOfficial')
            ->assertSet('showOfficialModal', false);

        $pegawai->refresh();
        $this->assertTrue($pegawai->hasRole('kepala_opd'));
        $this->assertSame($spesifik->id, $pegawai->position_id);
        $this->assertSame([], array_values(array_filter($component->get('steps'), fn ($s) => $s['status'] === 'not_found')));
    }

    public function test_super_admin_melihat_instansi_induk_pegawai(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $bidang = Department::create(['name' => 'Bidang Rehabilitasi Sosial', 'type' => DepartmentType::OPD, 'level' => 2, 'parent_id' => $this->dinas->id]);
        $this->user('staf', null, $bidang);
        $this->actingAs($superAdmin);

        Livewire::test(SppdCreate::class)
            ->call('selectUser', $this->staf->id)
            ->assertSee('Instansi:')
            // Pegawai sub-unit tampil bersama instansi induknya.
            ->assertSee('› Bidang Rehabilitasi Sosial');
    }

    public function test_admin_setda_melengkapi_walikota_dan_admin_opd_lain_tidak(): void
    {
        $setda = Department::create(['name' => 'Sekretariat Daerah', 'type' => DepartmentType::SETDA, 'level' => 1]);
        $adminSetda = $this->user('admin_opd', null, $setda);
        $this->user('sekda', null, $setda);
        $this->user('asisten', null, $setda);
        $calon = $this->user('staf', null, $setda);
        $stafSetda = $this->user('staf', null, $setda);
        $walikota = Position::create(['name' => 'Walikota', 'default_role' => 'walikota', 'uniqueness_scope' => 'system']);

        // Admin OPD (Dinas Sosial) tidak bisa membuka tahap Wali Kota untuk pegawainya.
        $this->actingAs($this->user('admin_opd'));
        $this->user('kepala_opd');
        Livewire::test(SppdCreate::class)
            ->set('domain', 'luar_negeri')
            ->call('selectUser', $this->staf->id)
            ->assertSee('Diatur Admin Sekretariat Daerah')
            ->call('openOfficial', 'walikota')
            ->assertSet('showOfficialModal', false);

        // Admin Setda melengkapi Wali Kota dari pegawai Sekretariat Daerah.
        $this->actingAs($adminSetda);
        Livewire::test(SppdCreate::class)
            ->set('domain', 'luar_negeri')
            ->call('selectUser', $stafSetda->id)
            ->assertSeeHtml("openOfficial('walikota')")
            ->call('openOfficial', 'walikota')
            ->assertSet('showOfficialModal', true)
            ->assertSee('Calon dari pegawai Sekretariat Daerah')
            ->call('pickOfficialCandidate', $calon->id)
            ->assertSet('officialStep', 2)
            ->call('chooseOfficialPosition', $walikota->id)
            ->call('officialNext')
            ->call('confirmOfficial')
            ->assertSet('showOfficialModal', false);

        $calon->refresh();
        $this->assertTrue($calon->hasRole('walikota'));
        $this->assertSame($walikota->id, $calon->position_id);
    }
    public function test_wizard_mengganti_pejabat_yang_sudah_terisi(): void
    {
        $admin = $this->user('admin_opd');
        $jabatan = Position::create(['name' => 'Kepala Dinas Sosial', 'default_role' => 'kepala_opd', 'uniqueness_scope' => 'department']);
        $lama = $this->user('kepala_opd', $jabatan);
        $pengganti = $this->user('staf');
        $this->actingAs($admin);

        $component = Livewire::test(SppdCreate::class)
            ->call('selectUser', $this->staf->id)
            ->assertSeeHtml("replaceOfficial('kepala_opd')")
            ->call('replaceOfficial', 'kepala_opd')
            ->assertSet('showOfficialModal', true)
            ->assertSet('officialReplaceId', $lama->id)
            ->assertSee('Siapa yang menggantikan')
            ->call('pickOfficialCandidate', $pengganti->id)
            // Jabatan pejabat lama langsung disarankan untuk dialihkan.
            ->assertSet('officialStep', 2)
            ->assertSet('officialPositionId', $jabatan->id)
            ->assertSee('dialihkan ke pengganti')
            ->call('officialNext')
            ->assertSee('Pejabat lama')
            ->assertSee('Jabatan Kepala Dinas Sosial dilepas')
            ->call('confirmOfficial')
            ->assertSet('showOfficialModal', false);

        $pengganti->refresh();
        $lama->refresh();
        $this->assertTrue($pengganti->hasRole('kepala_opd'));
        $this->assertSame($jabatan->id, $pengganti->position_id);
        $this->assertTrue($lama->hasRole('staf'));
        $this->assertNull($lama->position_id);

        $kepala = collect($component->get('steps'))->firstWhere('role', 'kepala_opd');
        $this->assertSame($pengganti->id, $kepala['approver_id']);
    }

    public function test_admin_opd_lain_tidak_bisa_mengganti_pejabat(): void
    {
        $this->user('kepala_opd');
        $dinasLain = Department::create(['name' => 'Dinas Pertanian', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->actingAs($this->user('admin_opd', null, $dinasLain));

        // Pelaksana di luar lingkup tidak tampil, tapi panggilan langsung pun ditolak.
        Livewire::test(SppdCreate::class)
            ->set('user_id', $this->staf->id)
            ->call('checkWorkflow')
            ->assertDontSeeHtml("replaceOfficial('kepala_opd')")
            ->call('replaceOfficial', 'kepala_opd')
            ->assertSet('showOfficialModal', false);
    }

    public function test_pengganti_mewarisi_role_pejabat_lama_dan_tidak_bisa_mengganti_orang_lain(): void
    {
        foreach (['camat', 'sekcam'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $kecamatan = Department::create(['name' => 'Kecamatan Mandonga', 'type' => DepartmentType::KECAMATAN, 'level' => 1]);
        $this->user('sekcam', null, $kecamatan);
        $camat = $this->user('camat', null, $kecamatan);
        $stafKecamatan = $this->user('staf', null, $kecamatan);
        $calon = $this->user('staf', null, $kecamatan);
        $service = app(SppdWorkflowService::class);

        try {
            // Hanya pejabat yang kini mengisi tahap itu yang bisa diganti.
            $service->appointOfficial($superAdmin, $stafKecamatan, 'dalam_daerah', 'kepala_opd', $calon->id, null, $calon->id);
            $this->fail('Pegawai yang bukan pejabat tahap ini seharusnya tidak bisa diganti.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('sudah berubah', $e->getMessage());
        }

        $service->appointOfficial($superAdmin, $stafKecamatan, 'dalam_daerah', 'kepala_opd', $calon->id, null, $camat->id);

        // Tahap Kepala OPD di kecamatan dipegang Camat: pengganti menjadi Camat, bukan Kepala OPD.
        $this->assertTrue($calon->fresh()->hasRole('camat'));
        $this->assertTrue($camat->fresh()->hasRole('staf'));
    }
}
