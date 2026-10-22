<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\DepartmentForm;
use App\Livewire\Sppd\PptkSelector;
use App\Livewire\Sppd\SetujuBayarSettings;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Penandatangan Setuju Bayar diatur dari Portal Dokumen SPPD (modal), disimpan
 * per instansi pelaksana dan dipakai semua dokumen cetaknya.
 */
class SetujuBayarSettingsTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $admin;
    private User $kepala;
    private SppdRequest $sppd;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['staf', 'admin_opd', 'kepala_opd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }

        $this->dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1, 'can_view_children_data' => true]);
        $bidang = Department::create(['name' => 'Bidang Lalu Lintas', 'type' => DepartmentType::OPD, 'level' => 2, 'parent_id' => $this->dinas->id, 'can_view_children_data' => true]);
        $this->admin = $this->user('admin_opd', $this->dinas);
        $this->kepala = $this->user('kepala_opd', $this->dinas, 'Kepala Dinas Perhubungan');
        $staf = $this->user('staf', $bidang, 'Pelaksana Teknis');

        $budget = new Budget(['department_id' => $this->dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();
        $this->sppd = SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => SppdStatus::APPROVED,
        ]);
    }

    private function user(string $role, Department $department, ?string $name = null): User
    {
        $user = User::factory()->create(array_filter(['department_id' => $department->id, 'name' => $name]));
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_opd_mengatur_penandatangan_dengan_pratinjau(): void
    {
        $sekretaris = $this->user('staf', $this->dinas, 'Sekretaris Dinas');

        Livewire::actingAs($this->admin)
            ->test(SetujuBayarSettings::class, ['sppd' => $this->sppd])
            // Belum diatur: Kepala OPD sebagai bawaan.
            ->assertSee('Kepala Dinas Perhubungan')
            ->assertSee('bawaan: Kepala OPD')
            ->call('open')
            ->assertSet('showModal', true)
            // Isian tidak .live: pratinjau diperbarui Alpine di klien; isi awal tetap dari server.
            ->assertDontSeeHtml('wire:model.live="signerId"')
            ->assertDontSeeHtml('wire:model.live.debounce.400ms="label"')
            ->assertSeeHtml('option-chosen')
            ->set('signerId', (string) $sekretaris->id)
            ->set('label', 'Kuasa Pengguna Anggaran')
            ->assertSee('Setuju bayar:')
            ->assertSee('Sekretaris Dinas')
            ->assertSee('Kuasa Pengguna Anggaran')
            ->call('save')
            ->assertHasNoErrors()
            // Modal tertutup otomatis; isinya tetap dirender agar animasi tutup tidak kosong.
            ->assertSet('showModal', false)
            ->assertSee('Setuju bayar:')
            ->assertDispatched('toast');

        $this->assertSame($sekretaris->id, $this->dinas->fresh()->setuju_bayar_user_id);
        // Pelaksana di sub-unit memakai pengaturan instansinya.
        $resolved = $this->sppd->user->department->resolveSetujuBayar();
        $this->assertSame($sekretaris->id, $resolved['user']->id);
        $this->assertSame('Kuasa Pengguna Anggaran', $resolved['label']);
    }

    public function test_reset_pengaturan_baru_tersimpan_saat_simpan(): void
    {
        $this->dinas->update(['setuju_bayar_user_id' => $this->admin->id, 'setuju_bayar_label' => 'Kuasa Pengguna Anggaran']);

        $component = Livewire::actingAs($this->admin)
            ->test(SetujuBayarSettings::class, ['sppd' => $this->sppd])
            ->call('open')
            // Reset pengaturan hanya mengosongkan isian di klien (Alpine), tanpa aksi server.
            ->assertSee('Reset pengaturan')
            ->assertDontSeeHtml('wire:click="resetDefault"')
            ->set('signerId', '')
            ->set('label', '');

        // Belum disimpan: dokumen masih memakai pengaturan lama.
        $this->assertSame($this->admin->id, $this->dinas->fresh()->setuju_bayar_user_id);

        $component->call('save')
            ->assertHasNoErrors()
            ->assertSet('showModal', false)
            ->assertDispatched('toast');

        $this->assertNull($this->dinas->fresh()->setuju_bayar_user_id);
        $this->assertNull($this->dinas->fresh()->setuju_bayar_label);
        $this->assertSame($this->kepala->id, $this->sppd->user->department->resolveSetujuBayar()['user']->id);
    }

    public function test_pptk_diatur_lewat_modal_portal_dokumen(): void
    {
        $pptk = $this->user('staf', $this->dinas, 'Pejabat Teknis');

        Livewire::actingAs($this->admin)
            ->test(PptkSelector::class, ['sppd' => $this->sppd])
            ->assertSee('Belum diatur')
            ->call('open')
            ->assertSet('showModal', true)
            ->set('pptk_id', $pptk->id)
            ->call('updatePptk')
            ->assertSet('showModal', false)
            ->assertSee('Pejabat Teknis');

        $this->assertSame($pptk->id, $this->sppd->fresh()->pptk_id);
    }

    public function test_pencarian_pegawai_di_server_dibatasi(): void
    {
        foreach (range(1, 30) as $i) {
            $this->user('staf', $this->dinas, sprintf('Pegawai Uji %02d', $i));
        }
        $lain = $this->user('staf', Department::create(['name' => 'Dinas Lain', 'type' => DepartmentType::OPD, 'level' => 1]), 'Pegawai Uji Luar');

        $pptk = Livewire::actingAs($this->admin)->test(PptkSelector::class, ['sppd' => $this->sppd]);
        $all = $pptk->instance()->searchPptk('');
        $this->assertCount(25, $all['options']);
        $this->assertTrue($all['more']);

        $found = $pptk->instance()->searchPptk('Uji 07');
        $this->assertSame(['Pegawai Uji 07'], array_map(fn ($o) => explode(' (', $o['label'])[0], $found['options']));
        $this->assertFalse($found['more']);
        // Pegawai instansi lain tidak ikut.
        $this->assertSame([], $pptk->instance()->searchPptk('Luar')['options']);

        // Setuju Bayar: pilihan bawaan Kepala OPD di urutan teratas saat kata kunci kosong.
        $signers = Livewire::actingAs($this->admin)->test(SetujuBayarSettings::class, ['sppd' => $this->sppd])
            ->instance()->searchSigners('');
        $this->assertSame('', $signers['options'][0]['value']);
        $this->assertStringContainsString('Kepala Dinas Perhubungan', $signers['options'][0]['label']);
        // Tiap opsi membawa data pratinjau (dihitung di klien tanpa request saat dipilih).
        $this->assertSame('Kepala Dinas Perhubungan', $signers['options'][0]['name']);
        $this->assertArrayHasKey('nip', $signers['options'][1]);
        $this->assertArrayHasKey('position', $signers['options'][1]);
        $this->assertNotContains((string) $lain->id, array_column($signers['options'], 'value'));
    }

    public function test_penandatangan_di_luar_instansi_ditolak(): void
    {
        $lain = Department::create(['name' => 'Dinas Lain', 'type' => DepartmentType::OPD, 'level' => 1]);
        $orangLain = $this->user('staf', $lain);

        Livewire::actingAs($this->admin)
            ->test(SetujuBayarSettings::class, ['sppd' => $this->sppd])
            ->call('open')
            ->set('signerId', (string) $orangLain->id)
            ->call('save')
            ->assertHasErrors('signerId');

        $this->assertNull($this->dinas->fresh()->setuju_bayar_user_id);
    }

    public function test_admin_instansi_lain_tidak_bisa_mengatur(): void
    {
        $adminLain = $this->user('admin_opd', Department::create(['name' => 'Dinas Lain', 'type' => DepartmentType::OPD, 'level' => 1]));

        Livewire::actingAs($adminLain)
            ->test(SetujuBayarSettings::class, ['sppd' => $this->sppd])
            ->assertDontSeeHtml('wire:click="open"')
            ->call('open')
            ->assertSet('showModal', false);
    }

    public function test_simpan_profil_unit_tidak_menghapus_pengaturan(): void
    {
        $this->dinas->update(['setuju_bayar_user_id' => $this->kepala->id, 'setuju_bayar_label' => 'Pengguna Anggaran']);

        Livewire::actingAs($this->admin)
            ->test(DepartmentForm::class, ['department' => $this->dinas])
            ->assertDontSee('Setuju Bayar')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($this->kepala->id, $this->dinas->fresh()->setuju_bayar_user_id);
        $this->assertSame('Pengguna Anggaran', $this->dinas->fresh()->setuju_bayar_label);
    }
}
