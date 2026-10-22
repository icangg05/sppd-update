<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SPPD yang direvisi: TTE lama (berhasil maupun gagal) dibuang beserta file bertanda
 * tangannya, dan tahap penanda tangan yang sudah menyetujui kembali menunggu agar
 * menandatangani ulang dokumen hasil perbaikan.
 */
class SppdRevisionResetTteTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_tte_menghapus_tanda_tangan_dan_mengembalikan_tahap_penanda_tangan(): void
    {
        $disk = Storage::fake(config('tte.storage.disk'));

        $dinas = Department::create(['name' => 'Dinas Kominfo', 'type' => DepartmentType::OPD, 'level' => 1]);
        [$staf, $kabid, $sekretaris, $kepala] = User::factory()->count(4)->create(['department_id' => $dinas->id]);
        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $sppd = SppdRequest::create([
            'user_id' => $staf->id,
            'creator_id' => $staf->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Rapat koordinasi',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => SppdStatus::IN_PROGRESS,
        ]);

        // Tahap 1 sudah menandatangani SPT, tahap 2 menyetujui tanpa TTE,
        // tahap 3 (penanda tangan SPPD) masih menunggu dan TTE-nya gagal.
        $signer = $sppd->approvals()->create(['approver_id' => $kabid->id, 'role_label' => 'Kabid', 'step_order' => 1,
            'status' => ApprovalStatus::APPROVED, 'acted_at' => now(), 'signs_spt' => true, 'signs_sppd' => false]);
        $plain = $sppd->approvals()->create(['approver_id' => $sekretaris->id, 'role_label' => 'Sekretaris', 'step_order' => 2,
            'status' => ApprovalStatus::APPROVED, 'acted_at' => now(), 'signs_spt' => false, 'signs_sppd' => false]);
        $last = $sppd->approvals()->create(['approver_id' => $kepala->id, 'role_label' => 'Kepala', 'step_order' => 3,
            'status' => ApprovalStatus::PENDING, 'signs_spt' => false, 'signs_sppd' => true]);

        $disk->put('tte/signed/spt.pdf', 'pdf');
        $sppd->digitalSignatures()->create(['signer_id' => $kabid->id, 'document_type' => 'spt',
            'status' => SignatureStatus::SIGNED, 'signed_at' => now(), 'signed_file_path' => 'tte/signed/spt.pdf']);
        $sppd->digitalSignatures()->create(['signer_id' => $kepala->id, 'document_type' => 'sppd_' . $staf->id,
            'status' => SignatureStatus::REJECTED, 'error_message' => 'Gagal']);

        $sppd->resetDigitalSignatures();

        $this->assertSame(0, $sppd->digitalSignatures()->count());
        $disk->assertMissing('tte/signed/spt.pdf');

        $this->assertSame(ApprovalStatus::PENDING, $signer->fresh()->status);
        $this->assertNull($signer->fresh()->acted_at);
        $this->assertSame(ApprovalStatus::APPROVED, $plain->fresh()->status);
        $this->assertSame(ApprovalStatus::PENDING, $last->fresh()->status);
    }
}
