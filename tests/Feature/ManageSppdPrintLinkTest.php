<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Enums\SppdStatus;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Vinkla\Hashids\Facades\Hashids;

/**
 * Kelola SPPD & Kelola SPT (menu Selanjutnya): tombol cetak dokumen yang sudah TTE
 * selalu langsung membuka berkas bertanda tangan, bukan route stream. Bila berkasnya
 * tidak ada di disk server ini, tautan tetap ke berkas dan halaman memberi catatan.
 */
class ManageSppdPrintLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $pelaksana;
    private User $pengikut;
    private SppdRequest $sppd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        Storage::fake('public');

        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $this->admin = User::factory()->create(['department_id' => $dinas->id]);
        $this->admin->assignRole('admin_opd');
        $this->pelaksana = User::factory()->create(['department_id' => $dinas->id]);
        $this->pengikut = User::factory()->create(['department_id' => $dinas->id]);

        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        $this->sppd = SppdRequest::create([
            'user_id' => $this->pelaksana->id,
            'creator_id' => $this->admin->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Kunjungan kerja',
            'start_date' => today(),
            'end_date' => today(),
            'domain' => 'dalam_daerah',
        ]);
        $this->sppd->forceFill(['status' => SppdStatus::APPROVED])->save();
        $this->sppd->followers()->create(['user_id' => $this->pengikut->id]);

        $this->assertTrue($this->admin->canOpenCostMenu());
    }

    private function signed(string $documentType, string $path, bool $fileExists): void
    {
        if ($fileExists) {
            Storage::disk('public')->put($path, '%PDF-1.4');
        }

        SppdDigitalSignature::create([
            'sppd_request_id' => $this->sppd->id,
            'signer_id' => $this->admin->id,
            'document_type' => $documentType,
            'status' => SignatureStatus::SIGNED,
            'signed_file_path' => $path,
            'signed_at' => now(),
        ]);
    }

    public function test_cetak_sppd_yang_sudah_tte_langsung_ke_berkas_bertanda_tangan(): void
    {
        $signedPath = 'dokumen-2026/doc-tte/SPPD-pelaksana.pdf';
        $missingPath = 'dokumen-2026/doc-tte/SPPD-pengikut-hilang.pdf';
        $this->signed('sppd_' . $this->pelaksana->id, $signedPath, fileExists: true);
        $this->signed('sppd_' . $this->pengikut->id, $missingPath, fileExists: false);

        $response = $this->actingAs($this->admin)->get(route('sppd.manage-sppd', $this->sppd))->assertOk();

        // Keduanya sudah TTE → tautan langsung ke berkas, tidak ada stream sama sekali.
        $response->assertSee(Storage::disk('public')->url($signedPath), false);
        $response->assertSee(Storage::disk('public')->url($missingPath), false);
        $response->assertDontSee(route('sppd.stream.sppd', $this->sppd) . '"', false);
        $response->assertDontSee(e(route('sppd.stream.sppd', ['sppd' => $this->sppd, 'user_id' => Hashids::encode($this->pengikut->id)])), false);
        // Hanya berkas pengikut yang tidak ada di disk.
        $this->assertSame(1, substr_count($response->getContent(), 'Berkas TTE tidak ditemukan di server ini.'));
    }

    public function test_cetak_sppd_yang_belum_tte_memakai_stream(): void
    {
        $this->actingAs($this->admin)->get(route('sppd.manage-sppd', $this->sppd))
            ->assertOk()
            ->assertSee(route('sppd.stream.sppd', $this->sppd) . '"', false)
            ->assertSee(e(route('sppd.stream.sppd', ['sppd' => $this->sppd, 'user_id' => Hashids::encode($this->pengikut->id)])), false)
            ->assertDontSee('Berkas TTE tidak ditemukan di server ini.');
    }

    public function test_cetak_spt_yang_sudah_tte_langsung_ke_berkas_bertanda_tangan(): void
    {
        $signedPath = 'dokumen-2026/doc-tte/SPT-ada.pdf';
        $this->signed('spt', $signedPath, fileExists: true);

        $this->actingAs($this->admin)->get(route('sppd.manage-spt', $this->sppd))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($signedPath), false)
            ->assertDontSee(route('sppd.stream.spt', $this->sppd), false)
            ->assertSee('Buka SPT Bertanda Tangan')
            ->assertDontSee('Berkas TTE tidak ditemukan di server ini.');
    }

    public function test_cetak_spt_tte_yang_berkasnya_tidak_ada_tetap_ke_berkas_dengan_catatan(): void
    {
        $missingPath = 'dokumen-2026/doc-tte/SPT-hilang.pdf';
        $this->signed('spt', $missingPath, fileExists: false);

        $this->actingAs($this->admin)->get(route('sppd.manage-spt', $this->sppd))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($missingPath), false)
            ->assertDontSee(route('sppd.stream.spt', $this->sppd), false)
            ->assertSee('Berkas TTE tidak ditemukan di server ini.');
    }

    public function test_cetak_spt_yang_belum_tte_memakai_stream(): void
    {
        $this->actingAs($this->admin)->get(route('sppd.manage-spt', $this->sppd))
            ->assertOk()
            ->assertSee(route('sppd.stream.spt', $this->sppd), false)
            ->assertSee('Cetak SPT');
    }
}
