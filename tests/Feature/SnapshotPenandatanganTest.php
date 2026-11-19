<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\Rank;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Nama, NIP, pangkat, dan jabatan penandatangan, pelaksana, dan pengikut dibekukan saat SPPD
 * disetujui (App\Support\PersonSnapshot): dokumen & halaman SPPD lama tidak ikut berubah saat
 * pejabatnya diganti (Ganti pejabat) atau profil pegawai diedit. SPPD lama diisi lewat
 * sppd:snapshot-people dari log aktivitas.
 */
class SnapshotPenandatanganTest extends TestCase
{
    use RefreshDatabase;

    private Department $dinas;
    private User $admin;
    private User $kadis;
    private User $pelaksana;
    private User $pengikut;
    private Rank $pembina;
    private Rank $penata;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'staf', 'kepala_opd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        $this->grantDefaultPermissions();

        $this->dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->pembina = Rank::create(['name' => 'Pembina Utama Muda', 'group' => 'IV/c']);
        $this->penata = Rank::create(['name' => 'Penata', 'group' => 'III/c']);

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole('super_admin');

        $this->kadis = $this->user('kepala_opd', 'Budi Kadis', '197001012000011001', 'Kepala Dinas Perhubungan', 20, $this->pembina);
        $this->pelaksana = $this->user('staf', 'Sari Pelaksana', '199001012020012001', 'Analis Lalu Lintas', 1000, $this->penata);
        $this->pengikut = $this->user('staf', 'Tono Pengikut', '199202022021011002', 'Pengelola Sarana', 1000, $this->penata);
    }

    private function user(string $role, string $name, string $nip, string $jabatan, int $level, Rank $rank): User
    {
        $user = User::factory()->create([
            'name' => $name, 'nip' => $nip, 'department_id' => $this->dinas->id, 'rank_id' => $rank->id,
            'position_id' => Position::create(['name' => $jabatan, 'level' => $level])->id,
        ]);
        $user->assignRole($role);

        return $user;
    }

    /** SPPD dalam proses dengan satu tahap Kepala Dinas yang menandatangani SPT & SPPD. */
    private function sppd(): SppdRequest
    {
        $budget = new Budget(['department_id' => $this->dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $this->pelaksana->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-02',
            'domain' => 'lddp',
            'status' => SppdStatus::IN_PROGRESS,
        ]);
        $sppd->followers()->create(['user_id' => $this->pengikut->id]);
        SppdApproval::create(['sppd_request_id' => $sppd->id, 'approver_id' => $this->kadis->id, 'role_label' => 'Kepala OPD',
            'step_order' => 1, 'status' => 'pending', 'signs_spt' => true, 'signs_sppd' => true]);

        return $sppd;
    }

    /** Kepala Dinas menyetujui dan SPPD disetujui penuh, seperti di SppdShow::approve. */
    private function setujui(SppdRequest $sppd): SppdRequest
    {
        $sppd->approvals()->first()->approve();
        $sppd->update(['status' => SppdStatus::APPROVED]);

        return $sppd->fresh();
    }

    /** Ganti pejabat: pejabat lama melepas jabatan & menjadi Staf, pengganti memegang jabatannya. */
    private function gantiKadis(): User
    {
        $positionId = $this->kadis->position_id;
        $this->kadis->update(['position_id' => null, 'rank_id' => $this->penata->id]);
        $this->kadis->syncRoles(['staf']);

        $pengganti = User::factory()->create(['name' => 'Citra Kadis Baru', 'department_id' => $this->dinas->id, 'position_id' => $positionId]);
        $pengganti->assignRole('kepala_opd');

        return $pengganti;
    }

    public function test_penandatangan_tetap_tercetak_seperti_saat_menyetujui_setelah_pejabat_diganti(): void
    {
        $sppd = $this->setujui($this->sppd());
        $this->gantiKadis();
        $this->kadis->update(['name' => 'Budi Kadis (akun diubah)', 'nip' => '000']);

        foreach (['spt', 'sppd'] as $document) {
            $data = SppdRequest::signerPdfData($sppd->fresh()->sptApproval(), $document);
            $this->assertSame(['Budi Kadis', 'Kepala Dinas Perhubungan', '197001012000011001', 'Pembina Utama Muda', 'IV/c'],
                [$data['approver_name'], $data['approver_role'], $data['approver_nip'], $data['approver_rank'], $data['approver_group']], $document);
        }

        $this->get($sppd->publicUrl())->assertOk()
            ->assertSeeInOrder(['Kepada', 'Budi Kadis', 'Kepala Dinas Perhubungan', '197001012000011001'])
            ->assertDontSee('akun diubah');
    }

    public function test_tahap_yang_belum_bertindak_masih_mengikuti_profil(): void
    {
        $sppd = $this->sppd();
        $this->kadis->update(['name' => 'Budi Kadis Baru']);

        $this->assertSame('Budi Kadis Baru', SppdRequest::signerPdfData($sppd->sptApproval(), 'spt')['approver_name']);
        $this->assertNull($sppd->approvals()->first()->approver_snapshot);
    }

    public function test_pelaksana_dan_pengikut_beku_sejak_disetujui(): void
    {
        $sppd = $this->sppd();

        // Sebelum disetujui: mengikuti profil.
        $this->pengikut->position->update(['name' => 'Pengelola Sarana Prasarana']);
        $this->assertSame('Pengelola Sarana Prasarana', $sppd->fresh()->personFor($this->pengikut)->jabatanLabel());

        $sppd = $this->setujui($sppd);
        $this->assertNotNull($sppd->pelaksana_snapshot);

        // Setelah disetujui: naik jabatan, pangkat, dan nama diubah → dokumen tetap.
        $this->pelaksana->update(['name' => 'Sari Berubah', 'rank_id' => $this->pembina->id,
            'position_id' => Position::create(['name' => 'Kepala Bidang', 'level' => 40])->id]);
        $this->pengikut->update(['position_id' => null]);
        $this->pengikut->syncRoles(['kepala_opd']);

        $sppd = $sppd->fresh();
        $rows = $sppd->sptParticipants()->map(fn ($p) => [$p->person->name, $p->person->jabatanLabel(), $p->person->pangkatGolongan()])->all();
        $this->assertSame([
            ['Sari Pelaksana', 'Analis Lalu Lintas', 'Penata, Gol. III/c'],
            ['Tono Pengikut', 'Pengelola Sarana Prasarana', 'Penata, Gol. III/c'],
        ], $rows);
        $this->assertSame('Sari Pelaksana', $sppd->personFor($this->pelaksana->fresh())->name);

        // Dokumen biaya memakai data beku yang sama.
        $captured = null;
        View::composer('exports.pengeluaran_riil', function ($view) use (&$captured) {
            $captured ??= $view->getData();
        });
        $this->actingAs($this->admin)->get(route('sppd.stream.pengeluaran-riil', $sppd))->assertOk();
        $html = view('exports.pengeluaran_riil', $captured)->render();
        $this->assertStringContainsString('Sari Pelaksana', $html);
        $this->assertStringContainsString('Analis Lalu Lintas', $html);
        $this->assertStringNotContainsString('Sari Berubah', $html);
    }

    public function test_revisi_mengosongkan_snapshot_tahap_penanda_tangan(): void
    {
        $sppd = $this->sppd();
        $sppd->approvals()->first()->approve();
        $this->assertNotNull($sppd->approvals()->first()->approver_snapshot);

        $sppd->resetDigitalSignatures();

        $this->assertNull($sppd->approvals()->first()->approver_snapshot);
    }

    public function test_backfill_memulihkan_jabatan_dari_log_dan_memakai_nama_terkini(): void
    {
        Carbon::setTestNow('2026-11-01 09:00:00');
        $this->pengikut->update(['rank_id' => null]); // pangkat pengikut belum diisi saat disetujui
        $sppd = $this->setujui($this->sppd());
        $berjalan = $this->sppd();
        // SPPD lama sebelum fitur ini: belum ada snapshot sama sekali.
        DB::table('sppd_requests')->update(['pelaksana_snapshot' => null]);
        DB::table('sppd_followers')->update(['snapshot' => null]);
        DB::table('sppd_approvals')->update(['approver_snapshot' => null]);

        Carbon::setTestNow('2026-11-10 09:00:00');
        $outgoingRole = 'kepala_opd';
        $this->gantiKadis();
        activity()->performedOn($this->kadis)->event('updated')
            ->withChanges(['old' => ['role' => $outgoingRole], 'attributes' => ['role' => 'staf']])
            ->log('Pegawai tidak lagi menjabat Kepala Dinas');
        $this->kadis->update(['name' => 'Budi Kadis, S.T.']);
        $this->pelaksana->update(['position_id' => Position::create(['name' => 'Kepala Seksi', 'level' => 50])->id]);
        // Perapian data: pangkat yang dulu kosong diisi, nama jabatan di master dirapikan.
        $this->pengikut->update(['rank_id' => $this->penata->id]);
        $this->pengikut->position->update(['name' => 'Pengelola Sarana dan Prasarana']);
        Carbon::setTestNow();

        $updatedAt = DB::table('sppd_requests')->where('id', $sppd->id)->value('updated_at');
        $this->artisan('sppd:snapshot-people')->assertSuccessful();
        // Pengisian data lama bukan perubahan SPPD: updated_at tidak berubah.
        $this->assertSame($updatedAt, DB::table('sppd_requests')->where('id', $sppd->id)->value('updated_at'));

        $signer = $sppd->fresh()->approvals()->first()->signer();
        $this->assertSame(['Budi Kadis, S.T.', 'Kepala Dinas Perhubungan', 20, 'IV/c'],
            [$signer->name, $signer->positionName, $signer->positionLevel, $signer->rankGroup]);
        $this->assertTrue($signer->hasRole('kepala_opd'));
        $this->assertSame('Analis Lalu Lintas', $sppd->fresh()->pelaksana()->jabatanLabel());

        // Data yang dulu kosong & nama jabatan yang dirapikan memakai data sekarang.
        $pengikut = $sppd->fresh()->followers->first()->person();
        $this->assertSame(['Pengelola Sarana dan Prasarana', 'Penata, Gol. III/c'], [$pengikut->jabatanLabel(), $pengikut->pangkatGolongan()]);

        // SPPD yang masih berjalan tidak dibekukan.
        $this->assertNull($berjalan->fresh()->pelaksana_snapshot);
        $this->assertNull($berjalan->approvals()->first()->approver_snapshot);
    }
}
