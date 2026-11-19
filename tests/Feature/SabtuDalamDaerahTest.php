<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdDomain;
use App\Enums\SppdStatus;
use App\Livewire\AppSettingIndex;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Models\AppSetting;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\AppSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Hari Sabtu pada perjalanan dalam daerah hanya dihitung di tipe instansi yang dicentang
 * Super Admin (Pengaturan Aplikasi). Tipe lain melewati Sabtu & Minggu, dan tidak bisa
 * berangkat/kembali di dua hari itu. Nilainya dicatat per SPPD saat disimpan.
 */
class SabtuDalamDaerahTest extends TestCase
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
    }

    /** Hanya tipe ini yang menghitung Sabtu. */
    private function sabtuDihitungDi(array $types): void
    {
        app(AppSettings::class)->update([AppSettings::SATURDAY_TRIP_TYPES => $types]);
    }

    public function test_lama_perjalanan_tanpa_sabtu(): void
    {
        $hari = fn (string $mulai, string $selesai, bool $sabtu) => SppdDomain::DALAM_DAERAH
            ->travelDays(Carbon::parse($mulai), Carbon::parse($selesai), $sabtu);

        // Rabu 11 s/d Selasa 17 November 2026: Sabtu 14 & Minggu 15 dilewati.
        $this->assertSame(5, $hari('2026-11-11', '2026-11-17', false));
        $this->assertSame(6, $hari('2026-11-11', '2026-11-17', true));
        $this->assertSame(0, $hari('2026-11-14', '2026-11-15', false));
        $this->assertSame([6, 0], SppdDomain::DALAM_DAERAH->offDays(false));
        $this->assertSame([0], SppdDomain::DALAM_DAERAH->offDays(true));
        // Luar daerah tetap menghitung semua hari.
        $this->assertSame(7, SppdDomain::LDDP->travelDays(Carbon::parse('2026-11-11'), Carbon::parse('2026-11-17'), false));
        $this->assertSame([], SppdDomain::LDDP->offDays(false));
    }

    public function test_pengaturan_per_tipe_instansi(): void
    {
        $opd = Department::create(['name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1]);
        $dinkes = Department::create(['name' => 'Dinas Kesehatan', 'type' => DepartmentType::DINKES, 'level' => 1]);
        $puskesmas = Department::create(['name' => 'Puskesmas Poasia', 'type' => DepartmentType::PUSKESMAS, 'level' => 2, 'parent_id' => $dinkes->id]);

        // Bawaan: semua tipe menghitung Sabtu (sama dengan aturan sebelumnya).
        $this->assertTrue($opd->countsSaturdayOnTrips());
        $this->assertTrue($puskesmas->countsSaturdayOnTrips());

        $this->sabtuDihitungDi(['puskesmas']);
        $this->assertFalse($opd->countsSaturdayOnTrips());
        $this->assertFalse($dinkes->countsSaturdayOnTrips());
        $this->assertTrue($puskesmas->countsSaturdayOnTrips());
    }

    public function test_super_admin_mengatur_dari_halaman_pengaturan(): void
    {
        $component = Livewire::actingAs($this->superAdmin)
            ->test(AppSettingIndex::class)
            ->assertSee('Sabtu Dihitung (Dalam Daerah)')
            ->assertSee('Puskesmas')
            ->assertSet('values.' . AppSettings::SATURDAY_TRIP_TYPES, array_column(DepartmentType::cases(), 'value'));

        $component->set('values.' . AppSettings::SATURDAY_TRIP_TYPES, ['bukan_tipe'])
            ->call('save')
            ->assertHasErrors('values.' . AppSettings::SATURDAY_TRIP_TYPES . '.0');

        // Disimpan urut sesuai opsi, apa pun urutan centangnya.
        $component->set('values.' . AppSettings::SATURDAY_TRIP_TYPES, ['rumah_sakit', 'puskesmas'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(['puskesmas', 'rumah_sakit'], AppSetting::firstWhere('key', AppSettings::SATURDAY_TRIP_TYPES)->value);
        $this->assertSame(['puskesmas', 'rumah_sakit'], (new AppSettings)->saturdayTripTypes());

        // Semua dicentang dikosongkan pun boleh (tidak ada instansi yang menghitung Sabtu).
        $component->set('values.' . AppSettings::SATURDAY_TRIP_TYPES, [])
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame([], (new AppSettings)->saturdayTripTypes());
    }

    public function test_form_menolak_sabtu_bila_instansi_tidak_menghitung_sabtu(): void
    {
        $this->sabtuDihitungDi(['puskesmas']);
        $form = $this->formSppd(DepartmentType::OPD)
            ->assertViewHas('offDays', [6, 0]);

        // Sabtu 14 s/d Sabtu 21 November 2026.
        $form->set('start_date', '2026-11-14')->set('end_date', '2026-11-21')
            ->call('openConfirmModal')
            ->assertHasErrors(['start_date', 'end_date'])
            ->assertSee('Perjalanan dalam daerah tidak bisa berangkat pada hari Sabtu')
            ->assertSee('Pilih hari Senin–Jumat');

        // Minggu juga tetap ditolak.
        $form->set('start_date', '2026-11-15')->set('end_date', '2026-11-16')
            ->call('openConfirmModal')
            ->assertHasErrors(['start_date'])
            ->assertSee('tidak bisa berangkat pada hari Minggu');

        // Jumat 13 s/d Senin 16 melewati akhir pekan: boleh.
        $form->set('start_date', '2026-11-13')->set('end_date', '2026-11-16')
            ->call('openConfirmModal')
            ->assertHasNoErrors(['start_date', 'end_date']);
    }

    public function test_instansi_yang_menghitung_sabtu_boleh_berangkat_sabtu(): void
    {
        $this->sabtuDihitungDi(['puskesmas']);

        $this->formSppd(DepartmentType::PUSKESMAS)
            ->assertViewHas('offDays', [0])
            ->set('start_date', '2026-11-14')->set('end_date', '2026-11-21')
            ->call('openConfirmModal')
            ->assertHasNoErrors(['start_date', 'end_date']);
    }

    public function test_aturan_sabtu_dicatat_saat_sppd_disimpan(): void
    {
        $this->sabtuDihitungDi(['puskesmas']);
        $form = $this->formSppd(DepartmentType::OPD);
        $sppd = SppdRequest::findOrFail($form->get('sppd_id'));

        // Rabu 11 s/d Selasa 17 November 2026.
        $form->set('start_date', '2026-11-11')->set('end_date', '2026-11-17')->call('submit');

        $sppd->refresh();
        $this->assertFalse($sppd->counts_saturday);
        $this->assertSame(5, $sppd->durationInDays());

        // Pengaturan diubah belakangan: lama hari SPPD yang sudah disimpan tidak berubah.
        $this->sabtuDihitungDi(array_column(DepartmentType::cases(), 'value'));
        $this->assertSame(5, $sppd->fresh()->durationInDays());
    }

    public function test_sppd_lama_tetap_menghitung_sabtu(): void
    {
        $this->sabtuDihitungDi([]);
        $lama = new SppdRequest(['domain' => 'dalam_daerah', 'start_date' => '2026-11-11', 'end_date' => '2026-11-17']);

        $this->assertNull($lama->counts_saturday);
        $this->assertSame(6, $lama->durationInDays());
    }

    /** Form Edit Perbaikan SPPD dalam daerah dengan pelaksana di instansi bertipe $type. */
    private function formSppd(DepartmentType $type)
    {
        $this->actingAs($this->superAdmin);

        $instansi = Department::create(['name' => 'Instansi ' . $type->label(), 'type' => $type, 'level' => 1]);
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
            'status' => SppdStatus::IN_PROGRESS,
        ]);

        return Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class);
    }
}
