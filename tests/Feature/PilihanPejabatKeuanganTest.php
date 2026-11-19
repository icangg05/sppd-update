<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\AdvanceReceipts;
use App\Livewire\Sppd\FinanceSigners;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\SppdFinanceOfficials;
use Database\Seeders\SppdWorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * PPTK, KPA (Setuju Bayar), dan Bendahara dipilih di Portal Dokumen (tidak tampil di Buat SPPD),
 * tersimpan untuk perjalanan berikutnya pegawai pelaksana sampai diedit, dan dicatat ke SPPD saat
 * diajukan. Calon dari seluruh struktur instansi pelaksana; modal pilih punya tab pratinjau dokumen.
 */
class PilihanPejabatKeuanganTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private Department $bidang;
    private User $admin;
    private User $staf;
    private User $pptk;
    private User $kepala;
    private User $bendahara;
    private User $bendaharaPembantu;
    private User $biasa;
    private User $bendaharaLuar;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'admin_opd', 'staf', 'kepala_opd', 'sekretaris_opd', 'kabid_irban_kabag'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->grantDefaultPermissions();

        $this->dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1,
            'can_view_children_data' => true, 'letterhead' => 'kop-surat/dishub.png']);
        $this->bidang = Department::create(['name' => 'Bidang Lalu Lintas', 'type' => DepartmentType::OPD, 'level' => 2,
            'parent_id' => $this->dinas->id, 'can_view_children_data' => true]);
        $luar = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);

        $bp = Position::create(['name' => 'Bendahara Pengeluaran']);
        $bpp = Position::create(['name' => 'Bendahara Pembantu']);
        $analis = Position::create(['name' => 'Analis Keuangan']);

        $this->admin = $this->user('admin_opd', $this->dinas, 'Admin Dishub');
        $this->staf = $this->user('staf', $this->bidang, 'Staf Pelaksana');
        $this->pptk = $this->user('staf', $this->bidang, 'Pejabat Teknis');
        $this->kepala = $this->user('kepala_opd', $this->dinas, 'Kepala Dinas Perhubungan');
        $this->bendahara = $this->user('staf', $this->dinas, 'Bendahara Dishub', $bp);
        $this->bendaharaPembantu = $this->user('staf', $this->bidang, 'Pembantu Bidang', $bpp);
        $this->biasa = $this->user('staf', $this->bidang, 'Analis Biasa', $analis);
        $this->bendaharaLuar = $this->user('staf', $luar, 'Bendahara Dinsos', $bp);
    }

    private function user(string $role, Department $department, string $name, ?Position $position = null): User
    {
        $user = User::factory()->create(['department_id' => $department->id, 'name' => $name, 'position_id' => $position?->id]);
        $user->assignRole($role);

        return $user;
    }

    private function sppd(SppdStatus $status = SppdStatus::APPROVED, array $extra = []): SppdRequest
    {
        $budget = new Budget(['department_id' => $this->dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create(array_merge([
            'user_id' => $this->staf->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'domain' => 'dalam_daerah',
            'status' => $status,
        ], $extra));
    }

    private function service(): SppdFinanceOfficials
    {
        return app(SppdFinanceOfficials::class);
    }

    /** Pilih ketiga pejabat untuk Staf Pelaksana lewat Portal Dokumen SPPD $sppd. */
    private function pilihSemua(SppdRequest $sppd): void
    {
        Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->call('openFinance', 'pptk')->call('pickFinance', $this->pptk->id)->call('saveFinance')
            ->call('openFinance', 'kpa')->call('pickFinance', $this->kepala->id)->set('financeLabel', 'Pengguna Anggaran')->call('saveFinance')
            ->call('openFinance', 'bendahara')->call('pickFinance', $this->bendaharaPembantu->id)->call('saveFinance')
            ->assertHasNoErrors();
    }

    public function test_buat_sppd_tidak_menampilkan_pejabat_keuangan_dan_tidak_mengunci_pengajuan(): void
    {
        $this->seed(SppdWorkflowSeeder::class);
        $this->user('sekretaris_opd', $this->dinas, 'Sekretaris Dishub');
        $this->staf->update(['atasan_id' => $this->user('kabid_irban_kabag', $this->bidang, 'Kabid Lalu Lintas')->id]);
        $this->pilihSemua($this->sppd());

        Livewire::actingAs($this->admin)->test(SppdCreate::class)
            ->call('selectUser', $this->staf->id)
            ->assertSet('errorMessage', '')
            ->assertSet('isComplete', true)
            ->assertDontSee('Pejabat Keuangan')
            ->assertDontSeeHtml("openFinance(");
    }

    public function test_pilihan_di_portal_tersimpan_untuk_perjalanan_berikutnya(): void
    {
        $sppd = $this->sppd();
        $page = Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->assertSee('Pejabat Keuangan')
            ->assertSee('3 belum dipilih');

        // KPA: label bawaan Kuasa Pengguna Anggaran, bisa diedit.
        $page->call('openFinance', 'kpa')
            ->assertSet('showFinanceModal', true)
            ->assertSet('financeLabel', 'Kuasa Pengguna Anggaran')
            ->call('pickFinance', $this->kepala->id)
            ->call('saveFinance')
            ->assertHasNoErrors()
            ->assertSet('showFinanceModal', false)
            ->assertDispatched('toast');
        $this->assertSame([$this->kepala->id, 'Kuasa Pengguna Anggaran'], [$this->staf->fresh()->kpa_id, $this->staf->fresh()->kpa_label]);

        // Bendahara: hanya pegawai berjabatan diawali "Bendahara" di instansi pelaksana; keterangan bendahara lama
        // sudah dihapus, diganti tautan ke Data Pegawai.
        $page->call('openFinance', 'bendahara')
            ->assertViewHas('financeModal', function (array $modal) {
                $names = array_column($modal['candidates'], 'name');
                sort($names);

                return $names === ['Bendahara Dishub', 'Pembantu Bidang'];
            })
            ->assertDontSee('jabatannya ikut tercetak')
            ->assertSee('halaman Data Pegawai')
            ->assertSeeHtml(route('master.users.index'))
            ->call('pickFinance', $this->biasa->id)
            ->call('saveFinance')
            ->assertHasErrors('financeChoiceId')
            ->call('pickFinance', $this->bendaharaLuar->id)
            ->call('saveFinance')
            ->assertHasErrors('financeChoiceId')
            ->call('pickFinance', $this->bendaharaPembantu->id)
            ->call('saveFinance')
            ->assertHasNoErrors();
        $this->assertSame($this->bendaharaPembantu->id, $this->staf->fresh()->bendahara_id);

        // SPPD lain pegawai yang sama: mengosongkan KPA di sana tidak mengubah pilihan lain.
        Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $this->sppd()])
            ->call('openFinance', 'kpa')
            ->call('clearFinance');
        $this->assertNull($this->staf->fresh()->kpa_id);
        $this->assertSame($this->bendaharaPembantu->id, $this->staf->fresh()->bendahara_id);
    }

    public function test_pilihan_dicatat_ke_sppd_saat_diajukan_dan_dokumen_tidak_berubah_saat_diganti(): void
    {
        // SPPD yang sudah punya KPA (mis. data lama) tidak dikosongkan oleh pilihan yang belum ada.
        $sppd = $this->sppd(SppdStatus::IN_PROGRESS, ['kpa_id' => $this->kepala->id]);
        Livewire::actingAs($this->admin)->withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class)
            ->set('start_date', '2026-12-01')->set('end_date', '2026-12-02')
            ->call('submit');
        $this->assertSame($this->kepala->id, $sppd->fresh()->kpa_id);
        $this->assertNull($sppd->fresh()->bendahara_id);

        // Dipilih di Portal Dokumen SPPD lain pegawai yang sama (tanggal lain agar tidak bentrok), lalu
        // dicatat saat SPPD ini diajukan ulang.
        $this->pilihSemua($this->sppd(SppdStatus::APPROVED, ['start_date' => '2026-10-01', 'end_date' => '2026-10-02']));
        $this->assertSame($this->pptk->id, $this->staf->fresh()->pptk_id);
        Livewire::withQueryParams([])->actingAs($this->admin)->withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class)
            ->assertDontSee('Pejabat Keuangan')
            ->set('start_date', '2026-12-01')->set('end_date', '2026-12-02')
            ->call('submit')
            ->assertHasNoErrors();
        $sppd->refresh();
        $this->assertSame([$this->pptk->id, $this->kepala->id, 'Pengguna Anggaran', $this->bendaharaPembantu->id, 'Bendahara Pembantu'],
            [$sppd->pptk_id, $sppd->kpa_id, $sppd->kpa_label, $sppd->bendahara_id, $sppd->bendahara_label]);

        // Pilihan pegawai diganti untuk perjalanan berikutnya: SPPD yang sudah diajukan tetap.
        $this->service()->remember($this->staf->fresh(), 'bendahara', $this->bendahara);
        $sppd->update(['status' => SppdStatus::APPROVED]);
        $this->assertSame($this->bendaharaPembantu->id, $sppd->fresh()->bendahara_id);

        $captured = null;
        View::composer('exports.kuitansi_rampung', function ($view) use (&$captured) {
            $captured = $view->getData()['pdfData'];
        });
        $this->actingAs($this->admin)->get(route('sppd.stream.kuitansi-rampung', $sppd))->assertOk();
        $this->assertSame(['Kepala Dinas Perhubungan', 'Pengguna Anggaran', 'Pembantu Bidang', 'Bendahara Pembantu'],
            [$captured['approver_name'], $captured['approver_label'], $captured['bendahara_name'], $captured['bendahara_title']]);
    }

    public function test_portal_dokumen_melengkapi_sppd_dan_menyimpan_untuk_perjalanan_berikutnya(): void
    {
        $sppd = $this->sppd();

        Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->assertSee('3 belum dipilih')
            ->call('openFinance', 'bendahara')
            ->assertSet('showFinanceModal', true)
            ->call('pickFinance', $this->bendahara->id)
            ->call('saveFinance')
            ->assertHasNoErrors()
            ->assertSee('Bendahara Dishub')
            ->assertSee('2 belum dipilih');

        $this->assertSame([$this->bendahara->id, 'Bendahara Pengeluaran'], [$sppd->fresh()->bendahara_id, $sppd->fresh()->bendahara_label]);
        $this->assertSame($this->bendahara->id, $this->staf->fresh()->bendahara_id);

        // Syarat cetak kuitansi: KPA masih kosong.
        Livewire::actingAs($this->admin)->test(AdvanceReceipts::class, ['sppd' => $sppd->fresh()])
            ->assertSet('hasBendahara', true)
            ->assertSet('hasKpa', false)
            ->assertSee('Penandatangan Setuju Bayar (KPA) belum dipilih.');

        // Tanpa hak kelola biaya: tidak bisa mengubah.
        Livewire::actingAs($this->pptk)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->assertDontSeeHtml("openFinance('kpa')")
            ->call('openFinance', 'kpa')
            ->assertSet('showFinanceModal', false);
    }

    public function test_tab_pratinjau_dokumen_mengikuti_pilihan_sebelum_disimpan(): void
    {
        $sppd = $this->sppd(SppdStatus::APPROVED, ['pptk_id' => $this->pptk->id]);
        $page = Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $sppd]);
        $docs = fn (string $role) => array_column($page->call('openFinance', $role)->viewData('financeModal')['previews'], 'key');

        $this->assertSame(['riil', 'rincian'], $docs('pptk'));
        $this->assertSame(['kuitansi', 'riil', 'rincian'], $docs('kpa'));
        $this->assertSame(['kuitansi', 'rincian'], $docs('bendahara'));

        // Bendahara dipilih (belum disimpan): slot bendahara kuitansi tersorot dengan nama & jabatannya.
        $page->call('pickFinance', $this->bendaharaPembantu->id)
            ->assertSee('Pilih Pegawai')
            ->assertSee('Rincian Biaya');
        $kuitansi = $page->viewData('financeModal')['previews'][0];
        $highlighted = collect($kuitansi['sections'])->flatMap(fn ($s) => $s['slots'])->where('highlight', true)->values();
        $this->assertCount(1, $highlighted);
        $this->assertSame(['Pembantu Bidang', 'BENDAHARA PEMBANTU'], [$highlighted[0]['name'], $highlighted[0]['title']]);
        $this->assertNull($sppd->fresh()->bendahara_id);

        // KPA: label isian modal ikut pratinjau; slot lain memakai pejabat SPPD (PPTK) dan pelaksana.
        $page->call('openFinance', 'kpa')->call('pickFinance', $this->kepala->id)->set('financeLabel', 'Pengguna Anggaran');
        $riil = collect($page->viewData('financeModal')['previews'])->firstWhere('key', 'riil');
        $slots = collect($riil['sections'])->flatMap(fn ($s) => $s['slots']);
        $this->assertSame(['Pejabat Teknis', 'Staf Pelaksana', 'Kepala Dinas Perhubungan'], $slots->pluck('name')->all());
        $this->assertSame('Pengguna Anggaran', $slots->firstWhere('highlight', true)['title']);
        $this->assertTrue($slots->firstWhere('highlight', true)['live']);
    }

    public function test_calon_dari_seluruh_struktur_instansi_dan_peringatan_hanya_lintas_opd(): void
    {
        $dinkes = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::DINKES, 'level' => 1,
            'can_view_children_data' => true, 'letterhead' => 'kop-surat/dinkes.png']);
        $puskesmas = Department::create(['name' => 'Puskesmas Puuwatu', 'type' => DepartmentType::PUSKESMAS, 'level' => 2,
            'parent_id' => $dinkes->id, 'can_view_children_data' => true]);
        $puskesmasLain = Department::create(['name' => 'Puskesmas Perumnas', 'type' => DepartmentType::PUSKESMAS, 'level' => 2,
            'parent_id' => $dinkes->id, 'can_view_children_data' => true]);
        $bp = Position::where('name', 'Bendahara Pengeluaran')->first();

        $pelaksana = $this->user('staf', $puskesmas, 'Perawat Puuwatu');
        $bendaharaDinkes = $this->user('staf', $dinkes, 'Bendahara Dinkes', $bp);
        $bendaharaPkmLain = $this->user('staf', $puskesmasLain, 'Bendahara Perumnas', $bp);
        $admin = $this->user('admin_opd', $puskesmas, 'Admin Puuwatu');

        // Puskesmas di bawah Dinkes satu lingkup: bendahara Dinkes & Puskesmas lain jadi calon, tanpa peringatan.
        $names = $this->service()->search($pelaksana, 'bendahara')->pluck('name')->sort()->values()->all();
        $this->assertSame(['Bendahara Dinkes', 'Bendahara Perumnas'], $names);
        $this->assertNull($this->service()->warningFor($bendaharaDinkes->fresh(), $pelaksana, 'bendahara'));
        $this->assertFalse($this->service()->isCandidate($pelaksana, 'bendahara', $this->bendaharaLuar->id));

        $budget = new Budget(['department_id' => $puskesmas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();
        $sppd = SppdRequest::create(['user_id' => $pelaksana->id, 'creator_id' => $admin->id, 'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Rujukan'])->id, 'purpose' => 'Rujukan pasien',
            'start_date' => '2026-12-01', 'end_date' => '2026-12-02', 'domain' => 'dalam_daerah', 'status' => SppdStatus::APPROVED,
            'bendahara_id' => $bendaharaDinkes->id, 'bendahara_label' => 'Bendahara Pengeluaran']);

        Livewire::actingAs($admin)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->assertSee('Bendahara Dinkes')
            ->assertDontSee('di luar instansi pelaksana')
            ->call('openFinance', 'bendahara')->call('pickFinance', $bendaharaPkmLain->id)->call('saveFinance')
            ->assertHasNoErrors();
        $this->assertSame($bendaharaPkmLain->id, $sppd->fresh()->bendahara_id);

        // Benar-benar lintas OPD: tetap diberi peringatan.
        $sppd->update(['bendahara_id' => $this->bendaharaLuar->id]);
        Livewire::actingAs($admin)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->assertSee('Pegawai ini sudah di luar instansi pelaksana');
    }

    public function test_pegawai_nonaktif_diberi_peringatan_tanpa_dikosongkan(): void
    {
        $sppd = $this->sppd();
        $this->pilihSemua($sppd);
        $this->kepala->update(['is_active' => false]);

        $chosen = $this->service()->remembered($this->staf->fresh());
        $this->assertSame($this->kepala->id, $chosen['kpa']['user']->id);
        $this->assertStringContainsString('nonaktif', $chosen['kpa']['warning']);
        $this->assertNull($chosen['pptk']['warning']);

        Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $sppd])
            ->assertSee('Pegawai ini sudah nonaktif');
    }

    public function test_akun_administratif_dan_opd_lain_bukan_calon(): void
    {
        $adminLain = $this->user('admin_opd', Department::create(['name' => 'Dinas Lain', 'type' => DepartmentType::OPD, 'level' => 1]), 'Admin Lain');
        $this->assertFalse($this->service()->isCandidate($this->staf, 'pptk', $adminLain->id));

        Livewire::actingAs($this->admin)->test(FinanceSigners::class, ['sppd' => $this->sppd()])
            ->call('openFinance', 'pptk')->call('pickFinance', $this->admin->id)->call('saveFinance')
            ->assertHasErrors('financeChoiceId');
        $this->assertNull($this->staf->fresh()->pptk_id);
    }
}
