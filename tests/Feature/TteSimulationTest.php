<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\SignatureStatus;
use App\Jobs\SendTteSignRequestJob;
use App\Livewire\Sppd\SppdShow;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\Tte\BsreVerificationService;
use App\Services\Tte\LocalProxySignService;
use App\Services\Tte\PdfGeneratorService;
use App\Services\Tte\SignatureService;
use App\Services\Tte\SimulatedSignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Simulasi TTE ("TTE Testing", env TTE_SIMULATION): alur sama dengan BSrE tanpa
 * mengirim apa pun ke provider, dan selalu berlabel agar tidak dikira TTE sah.
 */
class TteSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('tte.storage.disk'));
        Storage::fake('local');
        Sleep::fake();
        Http::preventStrayRequests();
        config(['tte.simulation.enabled' => true]);
    }

    private function sppd(): SppdRequest
    {
        $dinas = Department::create(['name' => 'Dinas Perhubungan', 'type' => DepartmentType::OPD, 'level' => 1]);
        $user = User::factory()->create(['department_id' => $dinas->id, 'nik' => '7471000000000001']);
        $budget = new Budget(['department_id' => $dinas->id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
        $budget->save();

        return SppdRequest::create([
            'user_id' => $user->id,
            'creator_id' => $user->id,
            'budget_id' => $budget->id,
            'category_id' => SppdCategory::create(['name' => 'Koordinasi'])->id,
            'purpose' => 'Kunjungan kerja',
            'start_date' => today(),
            'end_date' => today(),
            'spt_date' => today(),
            'sppd_date' => today(),
            'domain' => 'dalam_daerah',
        ]);
    }

    /** Jalankan job TTE seperti worker queue dan kembalikan tanda tangannya. */
    private function signViaJob(SppdRequest $sppd, string $type, string $passphrase): SppdDigitalSignature
    {
        $signature = SppdDigitalSignature::create([
            'sppd_request_id' => $sppd->id,
            'signer_id' => $sppd->user_id,
            'document_type' => $type,
            'status' => SignatureStatus::PROCESSING,
            'provider_name' => SimulatedSignService::activeProviderName(),
        ]);

        try {
            (new SendTteSignRequestJob($signature, $passphrase))->handle(app(SignatureService::class));
        } catch (\Throwable) {
            // Gagal TTE: job menandai REJECTED lalu memanggil fail(); di luar worker fail() bisa melempar.
        }

        return $signature->fresh();
    }

    private function signedPdf(SppdDigitalSignature $signature): string
    {
        return Storage::disk(config('tte.storage.disk'))->get($signature->signed_file_path);
    }

    public function test_simulasi_nonaktif_secara_bawaan_dan_memakai_provider_bsre(): void
    {
        $backup = [getenv('TTE_SIMULATION'), $_ENV['TTE_SIMULATION'] ?? null, $_SERVER['TTE_SIMULATION'] ?? null];
        putenv('TTE_SIMULATION');
        unset($_ENV['TTE_SIMULATION'], $_SERVER['TTE_SIMULATION']);

        try {
            $config = require config_path('tte.php');
            $this->assertFalse($config['simulation']['enabled']);
        } finally {
            putenv('TTE_SIMULATION=' . ($backup[0] ?: 'false'));
            $_ENV['TTE_SIMULATION'] = $backup[1] ?? 'false';
            $_SERVER['TTE_SIMULATION'] = $backup[2] ?? 'false';
        }

        config(['tte.simulation.enabled' => false]);
        $provider = new \ReflectionProperty(SignatureService::class, 'provider');
        $this->assertInstanceOf(LocalProxySignService::class, $provider->getValue(app(SignatureService::class)));
        $this->assertSame('local_proxy', SimulatedSignService::activeProviderName());
    }

    public function test_spt_ditandatangani_lewat_job_tanpa_menghubungi_bsre(): void
    {
        $sppd = $this->sppd();

        $signature = $this->signViaJob($sppd, 'spt', 'tte-testing');

        $this->assertSame(SignatureStatus::SIGNED, $signature->status);
        $this->assertSame(SimulatedSignService::PROVIDER, $signature->provider_name);
        $this->assertTrue($signature->isSimulated());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $signature->provider_id);
        $this->assertNull($signature->error_message);

        $pdf = $this->signedPdf($signature);
        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertTrue(SimulatedSignService::isSimulatedPdf($pdf));
        // Hasil sementara di disk privat dibersihkan setelah diunduh, seperti draft.
        $this->assertSame([], Storage::disk('local')->allFiles('tte-simulasi'));

        $log = Activity::where('log_name', 'tte')->where('event', 'signed')->sole();
        $this->assertStringEndsWith('(TTE Testing)', $log->description);
        $this->assertSame(SimulatedSignService::PROVIDER, $log->properties['provider']);
    }

    public function test_dokumen_sppd_ditandatangani_di_halaman_1_dan_2_seperti_bsre(): void
    {
        $sppd = $this->sppd();

        $signature = $this->signViaJob($sppd, 'sppd_' . $sppd->user_id, 'tte-testing');

        $this->assertSame(SignatureStatus::SIGNED, $signature->status, (string) $signature->error_message);
        $result = app(BsreVerificationService::class)->verify($this->signedPdf($signature), 'SPPD.pdf');
        $this->assertSame(2, $result['signature_count']);
    }

    public function test_passphrase_salah_ditolak_dengan_pesan_yang_sama_seperti_bsre(): void
    {
        $signature = $this->signViaJob($this->sppd(), 'spt', 'passphrase-keliru');

        $this->assertSame(SignatureStatus::REJECTED, $signature->status);
        // Persis pesan yang tersimpan dari BSrE asli.
        $this->assertSame(
            'Provider request failed: {"error":"Provider request failed: {\"error\":\"Passphrase anda salah\",\"status_code\":2031}"}',
            $signature->error_message,
        );
        $this->assertSame('passphrase', app(SppdShow::class)->parseTteError($signature->error_message)['type']);
        $this->assertNull($signature->signed_file_path);
    }

    public function test_nik_tidak_valid_ditolak(): void
    {
        $sppd = $this->sppd();
        $sppd->user->update(['nik' => '12345']);

        $signature = $this->signViaJob($sppd, 'spt', 'tte-testing');

        $this->assertSame(SignatureStatus::REJECTED, $signature->status);
        $this->assertSame('nik', app(SppdShow::class)->parseTteError($signature->error_message)['type']);
    }

    public function test_mode_tte_web_dan_worker_berbeda_ditolak_tanpa_menghubungi_bsre(): void
    {
        // Diajukan saat simulasi aktif, tetapi worker masih memakai .env lama (simulasi nonaktif).
        $sppd = $this->sppd();
        $signature = SppdDigitalSignature::create([
            'sppd_request_id' => $sppd->id,
            'signer_id' => $sppd->user_id,
            'document_type' => 'spt',
            'status' => SignatureStatus::PROCESSING,
            'provider_name' => SimulatedSignService::PROVIDER,
        ]);
        config(['tte.simulation.enabled' => false]);

        try {
            (new SendTteSignRequestJob($signature, 'tte-testing'))->handle(app(SignatureService::class));
        } catch (\Throwable) {
        }

        $signature->refresh();
        $this->assertSame(SignatureStatus::REJECTED, $signature->status);
        $this->assertStringContainsString('docker compose restart', $signature->error_message);
        // Http::preventStrayRequests() di setUp: tidak ada permintaan ke BSrE.
        Http::assertNothingSent();
    }

    public function test_verifikasi_simulasi_valid_untuk_dokumen_utuh_dan_tidak_valid_bila_diubah(): void
    {
        $signature = $this->signViaJob($this->sppd(), 'spt', 'tte-testing');
        $pdf = $this->signedPdf($signature);
        $verifier = app(BsreVerificationService::class);

        $valid = $verifier->verify($pdf, 'SPT.pdf');
        $this->assertTrue($valid['ok']);
        $this->assertTrue($valid['simulated']);
        $this->assertTrue($valid['valid']);
        $this->assertSame(1, $valid['signature_count']);
        $this->assertSame($signature->signer->name, $valid['details'][0]['info_signer']['signer_name']);
        $this->assertTrue($valid['details'][0]['signature_document']['document_integrity']);

        $changed = $verifier->verify(str_replace('%PDF-1.7', '%PDF-1.6', $pdf) . "\n% diubah", 'SPT.pdf');
        $this->assertFalse($changed['valid']);
        $this->assertFalse($changed['details'][0]['signature_document']['document_integrity']);
    }

    public function test_dokumen_tanpa_penanda_simulasi_tetap_diverifikasi_ke_bsre(): void
    {
        Http::fake(['*' => Http::response(['summary' => 'VALID', 'jumlah_signature' => 1, 'details' => []])]);

        $result = app(BsreVerificationService::class)->verify('%PDF-1.7 ditandatangani BSrE', 'asli.pdf');

        $this->assertTrue($result['valid']);
        $this->assertArrayNotHasKey('simulated', $result);
        Http::assertSentCount(1);
    }

    public function test_label_tte_testing_tampil_di_layar_dan_footer_dokumen(): void
    {
        $this->assertStringContainsString('TTE Testing', Blade::render('<x-ui.dev-watermark />'));
        $this->assertStringContainsString('TTE TESTING', view('exports.partials.tte-footer-note', ['pdfData' => ['tte_testing' => true]])->render());

        $build = new \ReflectionMethod(PdfGeneratorService::class, 'buildSptData');
        $this->assertTrue($build->invoke(app(PdfGeneratorService::class), $this->sppd())['data']['pdfData']['tte_testing']);

        config(['tte.simulation.enabled' => false]);
        $this->assertStringNotContainsString('TTE Testing', Blade::render('<x-ui.dev-watermark />'));
        $this->assertStringContainsString('Layanan BSrE', view('exports.partials.tte-footer-note', ['pdfData' => ['tte_testing' => false]])->render());
    }
}
