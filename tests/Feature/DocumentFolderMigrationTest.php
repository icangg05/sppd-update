<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use App\Support\DocumentFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Folder unggahan {tahun}/... dipindah ke dokumen-{tahun}/..., lalu nama folder
 * bergaris bawah diganti tanda hubung — beserta path di database.
 */
class DocumentFolderMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(string $name): object
    {
        return require database_path("migrations/{$name}.php");
    }

    private function sppdWithAttachment(Department $dinas, string $attachment): SppdRequest
    {
        $staf = User::factory()->create(['department_id' => $dinas->id]);
        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => 2026]);
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
            'attachment' => $attachment,
        ]);
    }

    public function test_folder_tahun_dipindah_beserta_path_di_database(): void
    {
        $disk = Storage::fake('public');

        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $sppd = $this->sppdWithAttachment($dinas, '2026/dokumen_pendukung/undangan.jpg');
        $signature = SppdDigitalSignature::create([
            'sppd_request_id' => $sppd->id,
            'signer_id' => $sppd->user_id,
            'document_type' => 'spt',
            'status' => SignatureStatus::SIGNED,
            'signed_file_path' => '2026/doc_tte/SPT_BUDI_2026-10-22_13171.pdf',
        ]);
        $disk->put('2026/dokumen_pendukung/undangan.jpg', 'foto');
        $disk->put('2026/doc_tte/SPT_BUDI_2026-10-22_13171.pdf', '%PDF');
        $disk->makeDirectory('2026/laporan_perjalanan');
        $disk->put('kop_surat/1_primary.png', 'kop');

        $this->migration('2026_10_22_000002_move_yearly_upload_folders_to_dokumen_prefix')->up();

        $this->assertSame('dokumen-2026/dokumen_pendukung/undangan.jpg', $sppd->fresh()->attachment);
        $this->assertSame('dokumen-2026/doc_tte/SPT_BUDI_2026-10-22_13171.pdf', $signature->fresh()->signed_file_path);
        $disk->assertExists(['dokumen-2026/dokumen_pendukung/undangan.jpg', 'dokumen-2026/doc_tte/SPT_BUDI_2026-10-22_13171.pdf', 'kop_surat/1_primary.png']);
        $this->assertFalse($disk->directoryExists('2026'));

        // Rollback mengembalikan ke folder lama.
        $this->migration('2026_10_22_000002_move_yearly_upload_folders_to_dokumen_prefix')->down();

        $this->assertSame('2026/dokumen_pendukung/undangan.jpg', $sppd->fresh()->attachment);
        $disk->assertExists('2026/doc_tte/SPT_BUDI_2026-10-22_13171.pdf');
        $this->assertFalse($disk->directoryExists('dokumen-2026'));
    }

    public function test_nama_folder_bergaris_bawah_diganti_tanda_hubung(): void
    {
        $disk = Storage::fake('public');
        $migration = $this->migration('2026_10_22_000003_rename_upload_folders_to_kebab_case');

        $dinas = Department::create([
            'name' => 'Dinas Sosial', 'type' => DepartmentType::OPD, 'level' => 1,
            'letterhead' => 'kop_surat/DINAS_SOSIAL.png',
        ]);
        $legacy = Department::create(['name' => 'Dinas Lama', 'type' => DepartmentType::OPD, 'level' => 1, 'letterhead' => 'kop.png']);
        $sppd = $this->sppdWithAttachment($dinas, 'dokumen-2026/dokumen_pendukung/Undangan Rapat_1.pdf');
        $disk->put('kop_surat/DINAS_SOSIAL.png', 'kop');
        $disk->put('kop_surat/TIDAK_DIRUJUK.jpg', 'kop lama');
        $disk->put('dokumen-2026/dokumen_pendukung/Undangan Rapat_1.pdf', '%PDF');

        $migration->up();

        // Hanya nama folder yang berubah, nama berkas tetap.
        $this->assertSame('kop-surat/DINAS_SOSIAL.png', $dinas->fresh()->letterhead);
        $this->assertSame('dokumen-2026/dokumen-pendukung/Undangan Rapat_1.pdf', $sppd->fresh()->attachment);
        $this->assertSame('kop.png', $legacy->fresh()->letterhead);
        $disk->assertExists(['kop-surat/DINAS_SOSIAL.png', 'kop-surat/TIDAK_DIRUJUK.jpg', 'dokumen-2026/dokumen-pendukung/Undangan Rapat_1.pdf']);
        $this->assertFalse($disk->directoryExists('kop_surat'));
        $this->assertFalse($disk->directoryExists('dokumen-2026/dokumen_pendukung'));

        $migration->down();

        $this->assertSame('kop_surat/DINAS_SOSIAL.png', $dinas->fresh()->letterhead);
        $disk->assertExists(['kop_surat/DINAS_SOSIAL.png', 'kop_surat/TIDAK_DIRUJUK.jpg']);
        $this->assertFalse($disk->directoryExists('kop-surat'));
    }

    public function test_folder_unggahan_baru_memakai_prefix_dokumen_dan_tanda_hubung(): void
    {
        $this->assertSame('dokumen-' . date('Y') . '/dokumen-pendukung', DocumentFolder::path('dokumen-pendukung'));
        $this->assertSame('dokumen-' . date('Y') . '/doc-tte', config('tte.storage.paths.signed'));
        $this->assertSame('doc-dummy', config('tte.storage.paths.draft'));
    }
}
