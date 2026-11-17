<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Jobs\SendTteSignRequestJob;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdApproval;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\Tte\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * TTE pegawai Rumah Sakit (unit kerja bertipe `rumah_sakit`) berjalan sampai selesai di job
 * antrean. Error "rumah_sakit is not a valid backing value for enum DepartmentType" hanya
 * muncul bila worker antrean masih memuat kode sebelum tipe ini ditambahkan.
 */
class RumahSakitTteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('tte.storage.disk'));
        Sleep::fake();
        Queue::fake();
        config(['tte.providers.local_proxy.endpoint' => 'http://tte.test']);
    }

    public function test_tte_spt_dan_sppd_direktur_rumah_sakit_berhasil_dan_sppd_disetujui(): void
    {
        $rsud = Department::create([
            'name' => 'Rumah Sakit Umum Daerah Kota Kendari',
            'type' => DepartmentType::RUMAH_SAKIT,
            'level' => 1,
        ]);
        $direktur = User::factory()->create(['department_id' => $rsud->id, 'nik' => '7471000000000009']);

        $budget = new Budget(['department_id' => $rsud->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();
        $sppd = SppdRequest::create([
            'user_id' => $direktur->id,
            'creator_id' => $direktur->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Forum RSUD Kota dan Healthy Cities Expo 2026',
            'start_date' => today(),
            'end_date' => today(),
            'spt_date' => today(),
            'sppd_date' => today(),
            'domain' => 'dalam_daerah',
            'status' => SppdStatus::IN_PROGRESS,
        ]);
        SppdApproval::create([
            'sppd_request_id' => $sppd->id,
            'approver_id' => $direktur->id,
            'role_label' => 'Direktur',
            'step_order' => 1,
            'status' => ApprovalStatus::PENDING,
            'signs_spt' => true,
            'signs_sppd' => true,
        ]);

        // Provider (proxy BSrE) mengembalikan PDF yang sudah ditandatangani.
        Http::fake(fn () => Http::response('%PDF-1.4 ditandatangani', 200, ['Content-Type' => 'application/pdf']));

        foreach (['spt', 'sppd_' . $direktur->id] as $type) {
            $signature = SppdDigitalSignature::create([
                'sppd_request_id' => $sppd->id,
                'signer_id' => $direktur->id,
                'document_type' => $type,
                'status' => SignatureStatus::PROCESSING,
            ]);

            (new SendTteSignRequestJob($signature, 'rahasia'))->handle(app(SignatureService::class));

            $signature->refresh();
            $this->assertNull($signature->error_message, "TTE {$type} gagal: {$signature->error_message}");
            $this->assertSame(SignatureStatus::SIGNED, $signature->status);
        }

        $this->assertSame(DepartmentType::RUMAH_SAKIT, $direktur->fresh()->department->type);
        $this->assertSame(SppdStatus::APPROVED, $sppd->fresh()->status);
    }
}
