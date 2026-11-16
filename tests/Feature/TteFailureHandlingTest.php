<?php

namespace Tests\Feature;

use App\Enums\DepartmentType;
use App\Enums\DprdJabatan;
use App\Enums\SignatureStatus;
use App\Jobs\SendTteSignRequestJob;
use App\Models\Budget;
use App\Models\Department;
use App\Models\SppdCategory;
use App\Models\SppdDigitalSignature;
use App\Models\SppdRequest;
use App\Models\User;
use App\Services\Tte\LocalProxySignService;
use App\Services\Tte\PdfGeneratorService;
use App\Services\Tte\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\TimeoutExceededException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Penyebab TTE gagal di log 9–15 Nov 2026: SPT DPRD selalu error ($participants),
 * gangguan sesaat provider (502/timeout) tanpa pengulangan, dan kegagalan sistem
 * yang tidak tercatat di log TTE.
 */
class TteFailureHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['staf', 'anggota_dprd', 'pimpinan_dprd'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web'], ['label' => $role]);
        }
        Storage::fake(config('tte.storage.disk'));
        Sleep::fake();
        config(['tte.providers.local_proxy.endpoint' => 'http://tte.test']);
    }

    private function sppdFor(User $user): SppdRequest
    {
        $budget = new Budget(['department_id' => $user->department_id, 'description' => 'Perjalanan dinas', 'year' => now()->year]);
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
            'domain' => 'dalam_daerah',
        ]);
    }

    private function opdUser(): User
    {
        $dinas = Department::firstOrCreate(['name' => 'Dinas Perhubungan'], ['type' => DepartmentType::OPD, 'level' => 1]);

        return User::factory()->create(['department_id' => $dinas->id, 'nik' => '7471000000000001']);
    }

    private function dprdUser(string $role, array $attributes = []): User
    {
        $setwan = Department::firstOrCreate(['name' => 'Sekretariat DPRD'], ['type' => DepartmentType::DPRD, 'level' => 1]);
        $user = User::factory()->create(['department_id' => $setwan->id, ...$attributes]);
        $user->assignRole($role);

        return $user;
    }

    private function signature(SppdRequest $sppd, string $type = 'spt'): SppdDigitalSignature
    {
        return SppdDigitalSignature::create([
            'sppd_request_id' => $sppd->id,
            'signer_id' => $sppd->user_id,
            'document_type' => $type,
            'status' => SignatureStatus::PROCESSING,
        ]);
    }

    /** Palsukan provider: tiap permintaan mengambil respons berikutnya; jumlah permintaan dihitung. */
    private function fakeProvider(array $responses): \stdClass
    {
        $counter = new \stdClass();
        $counter->calls = 0;
        Http::fake(function () use ($counter, $responses) {
            return $responses[min($counter->calls++, count($responses) - 1)];
        });

        return $counter;
    }

    private function bsreGateway502(): mixed
    {
        // Bentuk asli dari log: proxy meneruskan halaman 502 nginx milik BSrE di dalam JSON.
        return Http::response([
            'error_description' => '502 Bad Gateway: "<html><head><title>502 Bad Gateway</title></head><body><center>nginx</center></body></html>"',
            'error' => '502 Bad Gateway: "<html>…</html>"',
        ], 500);
    }

    // ── 1. SPT DPRD ─────────────────────────────────────────────────────────

    public function test_draft_tte_spt_dprd_berhasil_dibuat_dan_peserta_urut_jabatan(): void
    {
        $anggota = $this->dprdUser('anggota_dprd', ['name' => 'Anggota Biasa', 'dprd_jabatan' => DprdJabatan::KOMISI_3->value]);
        $ketua = $this->dprdUser('pimpinan_dprd', ['name' => 'Ketua Dewan', 'dprd_jabatan' => DprdJabatan::KETUA->value]);
        $sppd = $this->sppdFor($anggota);
        $sppd->followers()->create(['user_id' => $ketua->id]);

        $build = new \ReflectionMethod(PdfGeneratorService::class, 'buildSptData');
        $data = $build->invoke(app(PdfGeneratorService::class), $sppd->fresh());

        $this->assertSame('exports.spt_dprd', $data['view']);
        $this->assertSame(['Ketua Dewan', 'Anggota Biasa'], $data['data']['participants']->pluck('name')->all());

        // Sebelumnya selalu gagal: "Undefined variable $participants".
        $path = app(PdfGeneratorService::class)->generateDraft($this->signature($sppd));
        $this->assertStringStartsWith('%PDF', file_get_contents($path));
    }

    public function test_staf_sekretariat_dprd_bukan_anggota_memakai_spt_biasa_seperti_pratinjau(): void
    {
        $staf = $this->dprdUser('staf');
        $sppd = $this->sppdFor($staf);

        $this->assertFalse($sppd->usesDprdSptFormat());

        $build = new \ReflectionMethod(PdfGeneratorService::class, 'buildSptData');
        $this->assertSame('exports.spt', $build->invoke(app(PdfGeneratorService::class), $sppd)['view']);
    }

    // ── 2. Pengulangan otomatis ─────────────────────────────────────────────

    private function requestSign(): array|string
    {
        $file = Storage::disk(config('tte.storage.disk'))->path('draft.pdf');
        Storage::disk(config('tte.storage.disk'))->put('draft.pdf', '%PDF-1.4');

        return (new LocalProxySignService())->requestSign($file, '7471000000000001', 'rahasia', 1, 0, 0, 10, 10, 'https://sppd.test/p', 'invisible');
    }

    public function test_error_gateway_bsre_diulang_otomatis_lalu_berhasil(): void
    {
        $provider = $this->fakeProvider([$this->bsreGateway502(), Http::response(['id_dokumen' => 'DOC-1'])]);

        $this->assertSame(['id_dokumen' => 'DOC-1'], $this->requestSign());
        $this->assertSame(2, $provider->calls);
    }

    public function test_status_503_dan_koneksi_ditolak_diulang_maksimal_dua_kali(): void
    {
        $provider = $this->fakeProvider([
            Http::response('Service Unavailable', 503),
            Http::failedConnection('cURL error 7: Failed to connect to tte.test port 80: Connection refused'),
            Http::response('Service Unavailable', 503),
            Http::response(['id_dokumen' => 'TIDAK-TERCAPAI']),
        ]);

        $result = $this->requestSign();

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(3, $provider->calls); // 1 percobaan + 2 pengulangan
    }

    public function test_passphrase_salah_tidak_diulang(): void
    {
        $provider = $this->fakeProvider([Http::response(['error' => 'Passphrase anda salah', 'status_code' => 2031], 400)]);

        $result = $this->requestSign();

        $this->assertStringContainsString('Passphrase anda salah', $result['error']);
        $this->assertSame(1, $provider->calls);
    }

    public function test_timeout_menunggu_jawaban_tidak_diulang(): void
    {
        $provider = $this->fakeProvider([
            Http::failedConnection('cURL error 28: Operation timed out after 60002 milliseconds with 0 bytes received'),
            Http::response(['id_dokumen' => 'TIDAK-TERCAPAI']),
        ]);

        $result = $this->requestSign();

        $this->assertStringContainsString('Operation timed out', $result['error']);
        $this->assertSame(1, $provider->calls);
    }

    public function test_passphrase_tidak_tertulis_di_log_saat_provider_gagal(): void
    {
        $this->fakeProvider([Http::response(['error' => 'Passphrase anda salah', 'status_code' => 2031], 400)]);
        \Illuminate\Support\Facades\Log::spy();

        $this->requestSign();

        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')
            ->withArgs(fn ($message, $context = []) => ! str_contains(json_encode($context), 'rahasia'));
    }

    // ── 3. Batas waktu ──────────────────────────────────────────────────────

    public function test_batas_waktu_job_mencakup_dua_permintaan_beserta_pengulangannya(): void
    {
        config(['tte.providers.local_proxy.timeout' => 60, 'tte.providers.local_proxy.retries' => 2, 'tte.providers.local_proxy.retry_delay_ms' => 5000]);

        // 3 percobaan × 60 dtk + jeda 5 + 10 dtk.
        $this->assertSame(195, LocalProxySignService::maxRequestSeconds());

        $sppd = $this->sppdFor($this->opdUser());
        $job = new SendTteSignRequestJob($this->signature($sppd), 'rahasia');
        $this->assertSame(2 * 195 + 60, $job->timeout);
    }

    // ── 4. Log TTE untuk kegagalan sistem ───────────────────────────────────

    private function rejectedLogs(): \Illuminate\Support\Collection
    {
        return Activity::where('log_name', 'tte')->where('event', 'rejected')->get();
    }

    public function test_error_saat_membuat_draft_tercatat_di_log_tte(): void
    {
        $this->mock(PdfGeneratorService::class)
            ->shouldReceive('generateDraft')
            ->andThrow(new \ErrorException('Undefined variable $participants'));

        $sppd = $this->sppdFor($this->opdUser());
        $signature = $this->signature($sppd);

        (new SendTteSignRequestJob($signature, 'rahasia'))->handle(app(SignatureService::class));

        $this->assertSame(SignatureStatus::REJECTED, $signature->fresh()->status);
        $logs = $this->rejectedLogs();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('kesalahan sistem', $logs->first()->description);
        $this->assertSame('Undefined variable $participants', $logs->first()->properties['error_message']);
    }

    public function test_penolakan_provider_hanya_tercatat_sekali(): void
    {
        Storage::disk(config('tte.storage.disk'))->put('doc-dummy/x/draft.pdf', '%PDF-1.4');
        $this->mock(PdfGeneratorService::class)
            ->shouldReceive('generateDraft')
            ->andReturn(Storage::disk(config('tte.storage.disk'))->path('doc-dummy/x/draft.pdf'));
        $this->fakeProvider([Http::response(['error' => 'Passphrase anda salah', 'status_code' => 2031], 400)]);

        $sppd = $this->sppdFor($this->opdUser());
        (new SendTteSignRequestJob($this->signature($sppd), 'salah'))->handle(app(SignatureService::class));

        $logs = $this->rejectedLogs();
        $this->assertCount(1, $logs);
        $this->assertStringContainsString('permintaan provider ditolak', $logs->first()->description);
    }

    public function test_job_melewati_batas_waktu_tercatat_di_log_tte(): void
    {
        $sppd = $this->sppdFor($this->opdUser());
        $signature = $this->signature($sppd);

        (new SendTteSignRequestJob($signature, 'rahasia'))->failed(new TimeoutExceededException('Job has timed out.'));

        $this->assertSame(SignatureStatus::REJECTED, $signature->fresh()->status);
        $this->assertStringContainsString('batas waktu', $this->rejectedLogs()->first()->description);
    }

    public function test_dokumen_yang_sudah_ditandatangani_tidak_ditandai_gagal(): void
    {
        $sppd = $this->sppdFor($this->opdUser());
        $signature = $this->signature($sppd);
        $signature->update(['status' => SignatureStatus::SIGNED]);

        (new SendTteSignRequestJob($signature, 'rahasia'))->failed(new \RuntimeException('Telegram gagal'));

        $this->assertSame(SignatureStatus::SIGNED, $signature->fresh()->status);
        $this->assertCount(0, $this->rejectedLogs());
    }
}
