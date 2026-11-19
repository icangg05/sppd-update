<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\AppSettings;
use App\Services\SppdWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Perjalanan dalam daerah: hari Sabtu dan Minggu dihitung atau tidak dipilih pembuat SPPD lewat
 * centang di form (Sabtu awalnya tercentang untuk Puskesmas & Rumah Sakit, Minggu selalu kosong).
 * Hari yang tidak dicentang tidak dihitung dan tidak bisa jadi tanggal berangkat/kembali. Pilihan
 * disimpan per SPPD; SPPD lama (null) tetap Sabtu dihitung, Minggu tidak.
 */
class AkhirPekanDalamDaerahTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);
        $this->grantDefaultPermissions();
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('super_admin');
        $this->actingAs($this->superAdmin);
    }

    public function test_lama_perjalanan_menurut_centang(): void
    {
        // Rabu 11 s/d Selasa 17 November 2026 (Sabtu 14, Minggu 15).
        [$start, $end] = [Carbon::parse('2026-11-11'), Carbon::parse('2026-11-17')];
        $dalam = SppdDomain::DALAM_DAERAH;

        foreach ([[false, false, 5, [6, 0]], [true, false, 6, [0]], [false, true, 6, [6]], [true, true, 7, []]] as [$sat, $sun, $days, $off]) {
            $this->assertSame($days, $dalam->travelDays($start, $end, $sat, $sun), "Sabtu={$sat} Minggu={$sun}");
            $this->assertSame($off, $dalam->offDays($sat, $sun));
        }

        // Luar daerah selalu menghitung semua hari, centang tidak berpengaruh.
        $this->assertSame(7, SppdDomain::LDDP->travelDays($start, $end, false, false));
        $this->assertSame([], SppdDomain::LDDP->offDays(false, false));
    }

    public function test_form_baru_centang_sabtu_awal_hanya_untuk_puskesmas_dan_rumah_sakit(): void
    {
        $this->partialMock(SppdWorkflowService::class, fn ($mock) => $mock->shouldReceive('simulateApprovals')->andReturn([[
            'index' => 0, 'role' => 'kepala_opd', 'role_label' => 'Kepala OPD', 'approver_id' => $this->superAdmin->id,
            'approver_name' => 'Kepala', 'status' => 'found', 'candidates' => collect(), 'signs_spt' => true,
            'signs_sppd' => true, 'subordinate_id' => null, 'subordinate_name' => null,
        ]]));

        foreach (['opd' => false, 'puskesmas' => true, 'rumah_sakit' => true] as $value => $sabtu) {
            $type = DepartmentType::from($value);
            $instansi = Department::create(['name' => 'Instansi ' . $type->label(), 'type' => $type, 'level' => 1]);
            $pelaksana = User::factory()->create(['department_id' => $instansi->id]);

            Livewire::withQueryParams(['user_id' => $pelaksana->id, 'domain' => 'dalam_daerah'])
                ->test(SppdCreateDetails::class)
                ->assertSet('counts_saturday', $sabtu)
                ->assertSet('counts_sunday', false)
                ->assertViewHas('offDays', $sabtu ? [0] : [6, 0])
                ->assertSee('Hitung hari Sabtu')
                ->assertSee('Hitung hari Minggu');
        }
    }

    public function test_hari_yang_tidak_dicentang_ditolak_lalu_boleh_setelah_dicentang(): void
    {
        $form = $this->formSppd(false, false)->assertViewHas('offDays', [6, 0]);

        // Sabtu 14 s/d Sabtu 21 November 2026.
        $form->set('start_date', '2026-11-14')->set('end_date', '2026-11-21')
            ->call('openConfirmModal')
            ->assertHasErrors(['start_date', 'end_date'])
            ->assertSee('tidak bisa berangkat pada hari Sabtu, karena hari Sabtu tidak dihitung')
            ->assertSee('Centang "Hitung hari Sabtu" atau pilih hari lain');

        // Dicentang: error hilang saat itu juga, Sabtu bisa dipilih.
        $form->set('counts_saturday', true)
            ->assertHasNoErrors(['start_date', 'end_date'])
            ->assertViewHas('offDays', [0]);

        // Minggu 15 s/d Senin 16: ditolak sampai Minggu dicentang.
        $form->set('start_date', '2026-11-15')->set('end_date', '2026-11-16')
            ->call('openConfirmModal')
            ->assertHasErrors(['start_date'])
            ->assertSee('tidak bisa berangkat pada hari Minggu');
        $form->set('counts_sunday', true)
            ->assertHasNoErrors(['start_date'])
            ->assertViewHas('offDays', []);
    }

    public function test_melepas_centang_setelah_memilih_tanggal_langsung_error(): void
    {
        // Sabtu 14 s/d Senin 16 November 2026, Sabtu & Minggu dicentang.
        $this->formSppd(true, true)
            ->set('start_date', '2026-11-14')->set('end_date', '2026-11-16')
            ->assertHasNoErrors(['start_date', 'end_date'])
            ->set('counts_saturday', false)
            ->assertHasErrors(['start_date'])
            ->assertHasNoErrors(['end_date']);
    }

    public function test_pilihan_tersimpan_tercatat_di_log_dan_dimuat_ulang(): void
    {
        $form = $this->formSppd(false, false);
        $sppd = SppdRequest::findOrFail($form->get('sppd_id'));

        // Rabu 11 s/d Selasa 17 November 2026.
        $form->set('counts_saturday', true)->set('counts_sunday', true)
            ->set('start_date', '2026-11-11')->set('end_date', '2026-11-17')
            ->call('submit');

        $sppd->refresh();
        $this->assertTrue($sppd->counts_saturday);
        $this->assertTrue($sppd->counts_sunday);
        $this->assertSame(7, $sppd->durationInDays());
        $this->assertSame('Sabtu & Minggu dihitung', $sppd->weekendNote());

        $log = Activity::where('subject_type', SppdRequest::class)->where('subject_id', $sppd->id)->latest('id')->get()
            ->first(fn (Activity $a) => array_key_exists('counts_sunday', $a->attribute_changes?->get('attributes') ?? []));
        $this->assertNotNull($log, 'Perubahan centang Sabtu/Minggu tercatat di log aktivitas.');

        Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class)
            ->assertSet('counts_saturday', true)
            ->assertSet('counts_sunday', true)
            ->assertViewHas('offDays', []);
    }

    public function test_sppd_lama_tetap_sabtu_dihitung_minggu_tidak(): void
    {
        $lama = new SppdRequest(['domain' => 'dalam_daerah', 'start_date' => '2026-11-11', 'end_date' => '2026-11-17']);

        $this->assertNull($lama->counts_saturday);
        $this->assertNull($lama->counts_sunday);
        $this->assertSame(6, $lama->durationInDays());
        $this->assertSame('Sabtu dihitung, Minggu tidak', $lama->weekendNote());

        $luar = new SppdRequest(['domain' => 'lddp', 'start_date' => '2026-11-11', 'end_date' => '2026-11-17']);
        $this->assertNull($luar->weekendNote());
    }

    public function test_keterangan_tampil_di_halaman_publik_dan_pengaturan_per_tipe_dihapus(): void
    {
        $this->withoutVite();
        $sppd = SppdRequest::findOrFail($this->formSppd(false, false)->get('sppd_id'));

        $this->get($sppd->publicUrl())->assertOk()->assertSee('Sabtu &amp; Minggu tidak dihitung', false);
        $this->assertArrayNotHasKey('dalam_daerah_sabtu_types', AppSettings::definitions());
    }

    /** Form Edit Perbaikan SPPD dalam daerah dengan pilihan Sabtu/Minggu tersimpan. */
    private function formSppd(bool $countsSaturday, bool $countsSunday)
    {
        $instansi = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);
        $pelaksana = User::factory()->create(['department_id' => $instansi->id]);
        $budget = new Budget(['department_id' => $instansi->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'domain' => 'dalam_daerah',
            'counts_saturday' => $countsSaturday,
            'counts_sunday' => $countsSunday,
            'status' => SppdStatus::IN_PROGRESS,
        ]);

        return Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class);
    }
}
