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
use App\Services\Tte\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Nama berkas PDF hasil TTE (folder doc_tte) ditulis huruf kapital. */
class TteSignedFileNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_nama_file_doc_tte_huruf_kapital(): void
    {
        Storage::fake(config('tte.storage.disk'));

        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $staf = User::factory()->create(['department_id' => $dinas->id, 'name' => 'Suliman, S.Kom., M.Kom']);
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
            'spt_date' => '2026-10-22',
            'domain' => 'dalam_daerah',
        ]);
        $signature = SppdDigitalSignature::create([
            'sppd_request_id' => $sppd->id,
            'signer_id' => $staf->id,
            'document_type' => 'spt',
            'status' => SignatureStatus::PROCESSING,
        ]);

        $store = new \ReflectionMethod(SignatureService::class, 'storeSignedPdf');
        $path = $store->invoke(app(SignatureService::class), $signature, '%PDF-1.4');

        $this->assertMatchesRegularExpression('#/SPT_SULIMAN-SKOM-MKOM_2026-10-22_\d{5}\.pdf$#', $path);
        Storage::disk(config('tte.storage.disk'))->assertExists($path);
    }
}
