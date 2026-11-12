<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Livewire\Sppd\SppdIndex;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use App\Rules\UniqueIdentityInInstansi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pegawai yang bertugas di dua OPD: satu akun per OPD, dikenali dari NIP yang sama.
 * SPPD baru boleh dibuat walau masih ada SPPD aktif, asal tanggalnya tidak bentrok
 * dengan SPPD lain miliknya di OPD mana pun (SPT/SPPD dibuat jauh hari karena TTE lama).
 */
class PegawaiDuaOpdTest extends TestCase
{
    use RefreshDatabase;

    private const NIP = '198001012005011001';

    private Department $opdA;
    private Department $opdB;
    private User $akunA;
    private User $akunB;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web'], ['label' => 'Super Admin']);

        $this->opdA = $this->dept('Dinas Sosial', DepartmentType::OPD);
        $this->opdB = $this->dept('Dinas Pendidikan', DepartmentType::OPD);
        $this->akunA = User::factory()->create(['department_id' => $this->opdA->id, 'nip' => self::NIP, 'name' => 'Pegawai Rangkap']);
        $this->akunB = User::factory()->create(['department_id' => $this->opdB->id, 'nip' => self::NIP, 'name' => 'Pegawai Rangkap']);
    }

    private function dept(string $name, DepartmentType $type, ?Department $parent = null): Department
    {
        return Department::create(['name' => $name, 'type' => $type, 'level' => 1, 'parent_id' => $parent?->id]);
    }

    /** Tanggal relatif terhadap hari ini: negatif = sudah lewat. */
    private function hari(int $selisih): string
    {
        return today()->addDays($selisih)->format('Y-m-d');
    }

    private function sppd(User $pelaksana, int $mulai, int $selesai, SppdStatus $status = SppdStatus::IN_PROGRESS, string $domain = 'dalam_daerah'): SppdRequest
    {
        $budget = new Budget(['department_id' => $pelaksana->department_id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $pelaksana->id,
            'creator_id' => $pelaksana->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => $this->hari($mulai),
            'end_date' => $this->hari($selesai),
            'domain' => $domain,
            'status' => $status,
        ]);
    }

    private function nipDiterima(string $nip, Department $unit): bool
    {
        return Validator::make(['nip' => $nip], ['nip' => [new UniqueIdentityInInstansi('nip', 'NIP', $unit->id)]])->passes();
    }

    /** SPPD yang bentrok dengan perjalanan hari ke-$mulai s/d ke-$selesai dari sekarang. */
    private function bentrok(User $user, int $mulai, int $selesai, ?int $exceptId = null): ?SppdRequest
    {
        return SppdRequest::clashingForUsers(collect([$user]), $this->hari($mulai), $this->hari($selesai), $exceptId)[$user->id] ?? null;
    }

    /** Pesan bentrok untuk SPPD dalam proses milik Pegawai Rangkap di Dinas Sosial. */
    private function pesanBentrok(SppdRequest $sppd): string
    {
        return "Pegawai Rangkap sudah memiliki SPPD di Dinas Sosial pada {$sppd->periodLabel()} (dalam proses persetujuan) yang bentrok dengan tanggal perjalanan ini.";
    }

    private function masukSuperAdmin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $this->actingAs($superAdmin);
    }

    /** Buka form Edit Perbaikan SPPD sebagai Super Admin. */
    private function formSppd(SppdRequest $sppd)
    {
        $this->masukSuperAdmin();

        return Livewire::withQueryParams(['sppd_id' => $sppd->id])->test(SppdCreateDetails::class);
    }

    public function test_nip_sama_boleh_di_opd_lain(): void
    {
        $this->assertTrue($this->nipDiterima(self::NIP, $this->dept('Dinas Perhubungan', DepartmentType::OPD)));
    }

    public function test_nip_sama_boleh_di_dinas_kesehatan_dan_puskesmas(): void
    {
        $dinkes = $this->dept('Dinas Kesehatan', DepartmentType::DINKES);
        $puskesmas = $this->dept('Puskesmas Poasia', DepartmentType::PUSKESMAS, $dinkes);
        User::factory()->create(['department_id' => $dinkes->id, 'nip' => '197001012000011002']);

        $this->assertTrue($this->nipDiterima('197001012000011002', $puskesmas));
    }

    public function test_nip_sama_ditolak_di_dalam_satu_instansi(): void
    {
        $bidang = $this->dept('Bidang Rehabilitasi', DepartmentType::BAGIAN, $this->opdA);

        $this->assertFalse($this->nipDiterima(self::NIP, $this->opdA));
        $this->assertFalse($this->nipDiterima(self::NIP, $bidang));
    }

    public function test_nip_dikirim_ke_database_sebagai_teks(): void
    {
        DB::enableQueryLog();

        $this->bentrok($this->akunB, 0, 1);

        $query = collect(DB::getQueryLog())->first(fn (array $q) => str_contains($q['query'], '"nip" in'));
        $this->assertSame([self::NIP], $query['bindings']);
    }

    public function test_sppd_dalam_proses_di_opd_lain_bentrok_untuk_orang_yang_sama(): void
    {
        $sppd = $this->sppd($this->akunA, 30, 32);

        $this->assertTrue($sppd->is($this->bentrok($this->akunB, 31, 35)));
        $this->assertNull($this->bentrok($this->akunB, 31, 35, $sppd->id));
    }

    public function test_sppd_aktif_tidak_menghalangi_tanggal_yang_tidak_beririsan(): void
    {
        $this->sppd($this->akunA, 30, 32);

        $this->assertNull($this->bentrok($this->akunB, 33, 35));
        $this->assertNull($this->bentrok($this->akunB, 25, 29));
        // Hari kembali sama dengan hari berangkat perjalanan lain tetap bentrok.
        $this->assertNotNull($this->bentrok($this->akunB, 32, 34));
        $this->assertNotNull($this->bentrok($this->akunB, 28, 30));
    }

    public function test_sppd_disetujui_dan_selesai_tetap_bentrok_yang_ditolak_tidak(): void
    {
        $this->sppd($this->akunA, -3, 0, SppdStatus::APPROVED);
        $this->sppd($this->akunA, -10, -8, SppdStatus::COMPLETED);
        $this->sppd($this->akunA, 5, 6, SppdStatus::REJECTED);

        $this->assertNotNull($this->bentrok($this->akunB, 0, 1));
        $this->assertNotNull($this->bentrok($this->akunB, -9, -9));
        $this->assertNull($this->bentrok($this->akunB, 5, 6));
    }

    public function test_bentrok_yang_dilaporkan_adalah_yang_berangkat_paling_awal(): void
    {
        $this->sppd($this->akunB, 33, 34);
        $awal = $this->sppd($this->akunA, 30, 31, SppdStatus::APPROVED);

        $this->assertTrue($awal->is($this->bentrok($this->akunB, 30, 40)));
    }

    public function test_akun_lain_sebagai_pengikut_ikut_bentrok(): void
    {
        $rekan = User::factory()->create(['department_id' => $this->opdA->id]);
        $sppd = $this->sppd($rekan, 30, 32);
        $sppd->followers()->create(['user_id' => $this->akunA->id]);

        $this->assertTrue($sppd->is($this->bentrok($this->akunB, 30, 30)));
    }

    public function test_pengikut_di_opd_sendiri_juga_bentrok(): void
    {
        $rekan = User::factory()->create(['department_id' => $this->opdA->id]);
        $tunggal = User::factory()->create(['department_id' => $this->opdA->id, 'nip' => null]);
        $sppd = $this->sppd($rekan, 30, 32);
        $sppd->followers()->create(['user_id' => $tunggal->id]);

        $this->assertTrue($sppd->is($this->bentrok($tunggal, 31, 31)));
    }

    public function test_nik_sama_tanpa_nip_sama_bukan_orang_yang_sama(): void
    {
        [$satu, $dua] = User::factory()->count(2)->create(['department_id' => $this->opdA->id, 'nip' => null, 'nik' => '7471040607780032'])->all();
        $this->sppd($satu, 30, 32);

        $this->assertNull($this->bentrok($dua, 30, 32));
    }

    public function test_sppd_aktif_di_semua_opd_terdaftar_urut_tanggal(): void
    {
        $kedua = $this->sppd($this->akunB, 40, 41);
        $pertama = $this->sppd($this->akunA, 30, 32, SppdStatus::APPROVED);
        $this->sppd($this->akunA, -5, -1, SppdStatus::APPROVED);

        $aktif = SppdRequest::activeForUsers(collect([$this->akunB]))[$this->akunB->id];

        $this->assertSame([$pertama->id, $kedua->id], $aktif->pluck('id')->all());
    }

    public function test_memilih_nama_menampilkan_sppd_aktif_tanpa_menghalangi(): void
    {
        $sppd = $this->sppd($this->akunA, 30, 32);
        $this->masukSuperAdmin();

        Livewire::test(SppdCreate::class)
            ->call('selectUser', $this->akunB->id)
            ->assertSee('Pegawai Rangkap memiliki 1 SPPD aktif')
            ->assertSee($sppd->periodLabel())
            ->assertDontSee('Belum dapat membuat SPPD')
            ->assertDontSee('Pegawai sedang bertugas');
    }

    public function test_pesan_bentrok_menyebut_instansi_periode_dan_status(): void
    {
        $bidang = $this->dept('Bidang Rehabilitasi', DepartmentType::BAGIAN, $this->opdA);
        $pelaksana = User::factory()->create(['department_id' => $bidang->id]);
        $sppd = $this->sppd($pelaksana, 0, 3, SppdStatus::APPROVED);

        $this->assertSame(
            "sudah memiliki SPPD di Dinas Sosial pada {$sppd->periodLabel()} (sudah disetujui) yang bentrok dengan tanggal perjalanan ini.",
            $sppd->clashMessage(),
        );
    }

    public function test_label_periode(): void
    {
        $sppd = new SppdRequest(['start_date' => '2026-11-12', 'end_date' => '2026-11-15']);
        $this->assertSame('12–15 November 2026', $sppd->periodLabel());

        $sppd->fill(['start_date' => '2026-11-28', 'end_date' => '2026-12-02']);
        $this->assertSame('28 Nov – 02 Des 2026', $sppd->periodLabel(true));

        $sppd->fill(['start_date' => '2026-12-30', 'end_date' => '2027-01-02']);
        $this->assertSame('30 Desember 2026 – 02 Januari 2027', $sppd->periodLabel());

        $sppd->fill(['start_date' => '2026-11-12', 'end_date' => '2026-11-12']);
        $this->assertSame('12 November 2026', $sppd->periodLabel());
    }

    public function test_sppd_lanjutan_tidak_ditolak_walau_masih_ada_sppd_aktif(): void
    {
        $selesai = $this->sppd($this->akunB, -5, -2, SppdStatus::APPROVED);
        $this->sppd($this->akunA, 30, 32);
        $this->masukSuperAdmin();

        $daftar = new SppdIndex;
        $daftar->startSppdLanjutan($selesai->id);

        $this->assertNull($daftar->errorMessage);
    }

    public function test_calon_pengikut_yang_bentrok_menampilkan_perjalanannya_di_opd_lain(): void
    {
        $lain = $this->sppd($this->akunA, 30, 32, SppdStatus::APPROVED);
        $pelaksana = User::factory()->create(['department_id' => $this->opdB->id]);
        $sppd = $this->sppd($pelaksana, 40, 41);

        // Tanggal tidak beririsan: SPPD-nya hanya keterangan dan pegawainya tetap bisa dipilih.
        $form = $this->formSppd($sppd)
            ->assertSee('SPPD lain: ' . $lain->periodLabel(true))
            ->assertDontSee('Bentrok: bertugas')
            ->assertViewHas('busyFollowers', fn (array $busy) => ! isset($busy[$this->akunB->id]));

        $form->set('start_date', $this->hari(31))
            ->set('end_date', $this->hari(33))
            ->assertSee('Bentrok: bertugas ' . $lain->periodLabel(true) . ' di Dinas Sosial')
            ->assertViewHas('busyFollowers', fn (array $busy) => isset($busy[$this->akunB->id]));
    }

    public function test_pengajuan_ditahan_bila_tanggal_bentrok_dengan_sppd_di_opd_lain(): void
    {
        $lain = $this->sppd($this->akunA, 30, 32);
        $sppdB = $this->sppd($this->akunB, 40, 41);

        $this->formSppd($sppdB)
            ->set('start_date', $this->hari(31))
            ->set('end_date', $this->hari(33))
            ->call('submit')
            ->assertDispatched('toast', type: 'error', message: $this->pesanBentrok($lain), title: 'Gagal Mengajukan')
            ->assertSet('showConfirmModal', false);

        $this->assertSame($this->hari(40), $sppdB->fresh()->start_date->format('Y-m-d'));
    }

    public function test_pengajuan_ditahan_bila_pengikut_bentrok_di_opd_lain(): void
    {
        $lain = $this->sppd($this->akunA, 30, 32);
        $pelaksana = User::factory()->create(['department_id' => $this->opdB->id]);
        $sppd = $this->sppd($pelaksana, 40, 41);

        $this->formSppd($sppd)
            ->set('followers', [$this->akunB->id])
            ->set('start_date', $this->hari(32))
            ->set('end_date', $this->hari(32))
            ->call('submit')
            ->assertDispatched('toast', type: 'error', message: $this->pesanBentrok($lain), title: 'Gagal Mengajukan');

        $this->assertSame(0, $sppd->followers()->count());
    }

    public function test_pengajuan_lolos_walau_masih_ada_sppd_aktif_yang_tidak_bentrok(): void
    {
        $this->sppd($this->akunA, 30, 32);
        $sppdB = $this->sppd($this->akunB, 40, 41);
        // Pengikut pada SPPD yang sedang diperbaiki tidak bentrok dengan SPPD itu sendiri,
        // dan pegawai dengan SPPD aktif di tanggal lain tetap bisa ikut.
        $pengikut = User::factory()->create(['department_id' => $this->opdB->id]);
        $sppdB->followers()->create(['user_id' => $pengikut->id]);
        $this->sppd($pengikut, 10, 12);

        $this->formSppd($sppdB)
            ->set('start_date', $this->hari(50))
            ->set('end_date', $this->hari(51))
            ->call('submit');

        $this->assertSame($this->hari(50), $sppdB->fresh()->start_date->format('Y-m-d'));
        $this->assertSame(1, $sppdB->followers()->count());
    }

    public function test_kalender_menonaktifkan_tanggal_terpakai_pelaksana_dan_pengikut(): void
    {
        $this->sppd($this->akunA, 30, 32, SppdStatus::APPROVED);
        $this->sppd($this->akunB, 35, 36, SppdStatus::REJECTED);
        $pelaksana = User::factory()->create(['department_id' => $this->opdB->id]);
        $this->sppd($pelaksana, 10, 11);
        $sppd = $this->sppd($pelaksana, 40, 41);
        $mulai = fn (array $rentang) => array_column($rentang, 'start');

        $this->formSppd($sppd)
            // SPPD yang sedang diperbaiki tidak memblokir dirinya sendiri.
            ->assertViewHas('blockedDates', fn (array $r) => $mulai($r) === [$this->hari(10)])
            // Pengikut dicentang: SPPD-nya di OPD lain ikut diblokir, yang ditolak tidak.
            ->set('followers', [$this->akunB->id])
            ->assertViewHas('blockedDates', fn (array $r) => $mulai($r) === [$this->hari(10), $this->hari(30)]
                && str_contains($r[1]['label'], 'Pegawai Rangkap'));
    }

    public function test_tanggal_kosong_tidak_mengunci_pengikut_yang_punya_sppd_lain(): void
    {
        $lain = $this->sppd($this->akunA, 0, 3, SppdStatus::APPROVED);
        $pelaksana = User::factory()->create(['department_id' => $this->opdB->id]);
        $sppd = $this->sppd($pelaksana, 40, 41);

        // Pengajuan baru dimulai tanpa tanggal: belum ada pengikut yang dikunci.
        $this->formSppd($sppd)
            ->set('start_date', '')
            ->set('end_date', '')
            ->assertViewHas('busyFollowers', [])
            ->assertSee('SPPD lain: ' . $lain->periodLabel(true))
            ->assertDontSee('Bentrok: bertugas');
    }

    public function test_pengikut_yang_dicentang_tampil_paling_atas(): void
    {
        $pelaksana = User::factory()->create(['department_id' => $this->opdB->id, 'name' => 'Zainal Pelaksana']);
        User::factory()->create(['department_id' => $this->opdB->id, 'name' => 'Aaron Rekan']);
        $sppd = $this->sppd($pelaksana, 40, 41);

        $form = $this->formSppd($sppd)
            ->set('followers', [$this->akunB->id])
            ->assertViewHas('users', fn ($users) => $users->first()->is($this->akunB) && ! $users->contains($pelaksana))
            ->assertSeeInOrder(['Ikut perjalanan (1)', 'Pegawai Rangkap', 'Pegawai lain', 'Aaron Rekan']);

        // Tetap di atas walau tidak cocok dengan pencarian.
        $form->set('searchFollower', 'Aaron')
            ->assertSeeInOrder(['Ikut perjalanan (1)', 'Pegawai Rangkap', 'Pegawai lain', 'Aaron Rekan']);
    }
}
