<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\Position;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Halaman publik SPPD (tanpa login): hanya terbuka dengan token yang benar dan
 * menampilkan kode QR halaman itu sendiri di kanan atas.
 */
class SppdPublicPageTest extends TestCase
{
    use RefreshDatabase;

    private function sppd(): SppdRequest
    {
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $dinas->id, 'name' => 'Budi Staf']);
        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => SppdStatus::APPROVED,
        ]);
    }

    public function test_halaman_publik_tampil_dengan_qr(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSee('Detail Surat Perjalanan Dinas')
            ->assertSee('Budi Staf')
            ->assertSee('Rapat koordinasi')
            ->assertSee('Kode QR halaman publik SPPD ini')
            ->assertSee('<svg', false);
    }

    /** Di bawah nama pelaksana hanya satu keterangan: NIP → jabatan/"Anggota DPRD" → label role. */
    public function test_keterangan_pelaksana_mengikuti_prioritas(): void
    {
        $this->withoutVite();
        foreach (['staf' => 'Staf', 'anggota_dprd' => 'Anggota DPRD'] as $name => $label) {
            Role::firstOrCreate(['name' => $name, 'guard_name' => 'web'], ['label' => $label]);
        }
        $sppd = $this->sppd();
        $pelaksana = $sppd->user;
        $pelaksana->assignRole('staf');
        $pelaksana->update([
            'name' => 'Budi Santoso',
            'nip' => '199001012020121001',
            'position_id' => Position::create(['name' => 'Pengelola Keuangan', 'default_role' => 'staf'])->id,
        ]);

        $keterangan = fn (string $teks) => $this->get($sppd->publicUrl())
            ->assertSeeInOrder(['Pelaksana</dt>', 'Budi Santoso', $teks, 'Kepada'], false);

        $keterangan('199001012020121001')->assertDontSee('Pengelola Keuangan');

        $pelaksana->update(['nip' => null]);
        $keterangan('Pengelola Keuangan');

        $pelaksana->update(['position_id' => null]);
        $keterangan('Staf');

        $pelaksana->syncRoles('anggota_dprd');
        $keterangan('Anggota DPRD');
    }

    public function test_dokumen_pendukung_tampil_bila_ada(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();
        $sppd->update(['attachment' => 'dokumen-pendukung/Undangan Rapat.pdf']);

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSee('Dokumen pendukung')
            ->assertSee('Undangan Rapat.pdf')
            ->assertSee(Storage::url('dokumen-pendukung/Undangan Rapat.pdf'), false);
    }

    public function test_dokumen_pendukung_disembunyikan_bila_kosong(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertDontSee('Dokumen pendukung');
    }

    public function test_uraian_telaah_dan_tanggal_pengajuan_tampil(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();
        $sppd->update([
            'problem' => 'Jadwal layanan belum sinkron',
            'facts' => 'Ada tiga terminal baru',
            'analysis' => 'Perlu koordinasi langsung',
        ]);

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSeeInOrder(['Persoalan', 'Jadwal layanan belum sinkron'])
            ->assertSeeInOrder(['Fakta-fakta yang mempengaruhi', 'Ada tiga terminal baru'])
            ->assertSeeInOrder(['Analisis', 'Perlu koordinasi langsung'])
            ->assertSeeInOrder(['Tanggal pengajuan', $sppd->created_at->translatedFormat('d M Y H:i') . ' WITA']);
    }

    public function test_kepada_adalah_penandatangan_spt(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();
        $kabid = User::factory()->create(['name' => 'Kabid Penyetuju', 'department_id' => $sppd->user->department_id]);
        $kadis = User::factory()->create([
            'name' => 'Kadis Penandatangan',
            'department_id' => $sppd->user->department_id,
            'position_id' => Position::create(['name' => 'Kepala Dinas Perhubungan', 'default_role' => 'kepala_opd', 'level' => 3])->id,
        ]);
        SppdApproval::create(['sppd_request_id' => $sppd->id, 'approver_id' => $kabid->id, 'role_label' => 'Kabid',
            'step_order' => 1, 'status' => 'approved', 'signs_spt' => false]);
        SppdApproval::create(['sppd_request_id' => $sppd->id, 'approver_id' => $kadis->id, 'role_label' => 'Kepala OPD',
            'step_order' => 2, 'status' => 'approved', 'signs_spt' => true]);

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSeeInOrder(['Kepada', 'Kadis Penandatangan', 'Kepala Dinas Perhubungan'])
            ->assertDontSee('Kabid Penyetuju');
    }

    public function test_status_tte_tampil_di_kepala_halaman(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();
        SppdDigitalSignature::create([
            'sppd_request_id' => $sppd->id,
            'signer_id' => $sppd->user_id,
            'document_type' => 'spt',
            'status' => SignatureStatus::SIGNED,
            'signed_at' => '2026-11-18 14:30:00',
        ]);

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSeeInOrder(['SPT', 'TTE 18 Nov 2026 14:30 WITA'])
            ->assertSeeInOrder(['SPPD', 'Belum TTE']);
    }

    public function test_uraian_telaah_kosong_tetap_tampil(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();

        $this->get($sppd->publicUrl())
            ->assertOk()
            ->assertSee('Persoalan')
            ->assertSee('Fakta-fakta yang mempengaruhi')
            ->assertSee('Analisis');
    }

    public function test_token_salah_ditolak(): void
    {
        $this->withoutVite();
        $sppd = $this->sppd();

        $this->get(route('sppd.public', ['sppd' => $sppd, 'token' => str_repeat('x', 64)]))->assertNotFound();
    }
}
