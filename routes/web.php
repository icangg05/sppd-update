<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Livewire\Auth\Login;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SppdActualExpenseController;
use App\Http\Controllers\SppdAdvanceReceiptController;
use App\Http\Controllers\SppdController;
use App\Http\Controllers\SppdCostDetailController;
use App\Http\Controllers\SppdDigitalSignatureController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KirimChatWebhookController;
use App\Jobs\LogQueueHeartbeatJob;
use App\Livewire\Sppd\SppdCalendar;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\Sppd\SppdShow;
use App\Livewire\Budgets\BudgetForm;
use App\Livewire\Budgets\BudgetIndex;
use App\Livewire\DepartmentForm;
use App\Livewire\DepartmentIndex;
use App\Livewire\PositionIndex;
use App\Livewire\PositionRequestIndex;
use App\Livewire\ProvinceIndex;
use App\Livewire\RankIndex;
use App\Livewire\RegencyIndex;
use App\Livewire\RoleForm;
use App\Livewire\RoleIndex;
use App\Livewire\WorkflowForm;
use App\Livewire\WorkflowIndex;
use App\Livewire\UsersIndex;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Halaman publik (read-only) detail SPPD — tanpa login, dilindungi token rahasia.
Route::get('/publik/sppd/{sppd}/{token}', [SppdController::class, 'publicShow'])->name('sppd.public');

// Kompatibilitas QR lama: dokumen ber-TTE yang sudah terbit menyimpan QR ke
// /verify/{type}/{sppd}/{hash}. Arahkan langsung ke halaman publik tanpa login.
Route::get('/verify/{type}/{sppd}/{hash}', [SppdController::class, 'publicVerifyRedirect'])
    ->whereIn('type', ['sppd', 'spt'])
    ->name('verify.document');

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Profil sendiri
    Route::get('/profile', \App\Livewire\Profile::class)->name('profile');

    // Panduan fitur
    Route::view('/panduan/whatsapp', 'guides.whatsapp')->name('guide.whatsapp');

    // Menu pejabat kepemimpinan (daftar role dari config/menu_access.php)
    $leadershipRoles = implode('|', config('menu_access.leadership')) . '|super_admin';
    Route::middleware("role:{$leadershipRoles}")->group(function () {
        Route::get('/riwayat-persetujuan', \App\Livewire\Leadership\ApprovalHistory::class)->name('leadership.history');
        Route::get('/rekap-sppd', \App\Livewire\Leadership\SppdRecap::class)->name('leadership.recap');
    });

    // Menu sekretariat (sekretaris OPD/kecamatan/DPRD)
    $secretariatRoles = implode('|', config('menu_access.secretariat')) . '|super_admin';
    Route::get('/monitoring-opd', \App\Livewire\Secretariat\OpdMonitoring::class)
        ->middleware("role:{$secretariatRoles}")->name('secretariat.monitoring');

    // Tandai changelog sudah dibaca (badge notifikasi per-user)
    Route::post('/notifications/seen', function (\Illuminate\Http\Request $request) {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->update(['changelog_seen_version' => config('changelog.version')]);

        return response()->json(['ok' => true]);
    })->name('notifications.seen');

    // SPPD — Livewire Full-Page Components
    Route::get('/sppd', SppdIndex::class)->name('sppd.index');
    Route::get('/sppd/create', SppdCreate::class)->name('sppd.create');
    Route::get('/sppd/create/details', SppdCreateDetails::class)->name('sppd.create.details');
    Route::get('/sppd/calendar', SppdCalendar::class)->name('sppd.calendar');
    Route::get('/sppd/{sppd}', SppdShow::class)->name('sppd.show');
    Route::delete('/sppd/{sppd}', [SppdController::class, 'destroy'])->name('sppd.destroy');

    // Legacy Workflow Portal
    Route::get('/sppd/{sppd}/next', [SppdController::class, 'next'])->name('sppd.next');

    // Sub-pages of 'Selanjutnya'
    Route::get('/sppd/{sppd}/manage-sppd', [SppdController::class, 'manageSppd'])->name('sppd.manage-sppd');
    Route::get('/sppd/{sppd}/manage-spt', [SppdController::class, 'manageSpt'])->name('sppd.manage-spt');
    Route::get('/sppd/{sppd}/receipts', [SppdController::class, 'receipts'])->name('sppd.receipts');
    Route::get('/sppd/{sppd}/actual-expenses', [SppdController::class, 'actualExpenses'])->name('sppd.actual-expenses');
    Route::get('/sppd/{sppd}/final-costs', [SppdController::class, 'finalCosts'])->name('sppd.final-costs');
    Route::get('/sppd/{sppd}/report-input', [SppdController::class, 'reportInput'])->name('sppd.report-input');

    // CRUD: Rincian Biaya Perjalanan Dinas
    Route::post('/sppd/{sppd}/cost-details', [SppdCostDetailController::class, 'store'])->name('sppd.cost-details.store');
    Route::put('/sppd/{sppd}/cost-details/{cost}', [SppdCostDetailController::class, 'update'])->name('sppd.cost-details.update');
    Route::delete('/sppd/{sppd}/cost-details/{cost}', [SppdCostDetailController::class, 'destroy'])->name('sppd.cost-details.destroy');

    // CRUD: Laporan Pengeluaran Riil
    Route::post('/sppd/{sppd}/actual-expenses', [SppdActualExpenseController::class, 'store'])->name('sppd.actual-expenses.store');
    Route::put('/sppd/{sppd}/actual-expenses/{expense}', [SppdActualExpenseController::class, 'update'])->name('sppd.actual-expenses.update');
    Route::delete('/sppd/{sppd}/actual-expenses/{expense}', [SppdActualExpenseController::class, 'destroy'])->name('sppd.actual-expenses.destroy');

    // Kuitansi Panjar (create or update)
    Route::post('/sppd/{sppd}/advance-receipts', [SppdAdvanceReceiptController::class, 'storeOrUpdate'])->name('sppd.advance-receipts.store');

    // Update PPTK
    Route::put('/sppd/{sppd}/pptk', [SppdController::class, 'updatePptk'])->name('sppd.update-pptk');

    // Laporan Perjalanan (store/update)
    Route::post('/sppd/{sppd}/report', [SppdController::class, 'storeReport'])->name('sppd.report.store');

    // TTE / Electronic signature
    // Route::post('/sppd/{sppd}/sign/{type}', [SppdDigitalSignatureController::class, 'request'])->name('sppd.sign');
    Route::get('/sppd/{sppd}/sign/batch-status', [SppdDigitalSignatureController::class, 'batchStatus'])->name('sppd.sign.batch-status');
    Route::get('/sppd/{sppd}/sign/{signature}/status', [SppdDigitalSignatureController::class, 'status'])->name('sppd.sign.status');
    Route::get('/sppd/{sppd}/sign/{signature}/download', [SppdDigitalSignatureController::class, 'download'])->name('sppd.sign.download');

    Route::get('/sppd/{sppd}/stream/spt', [SppdController::class, 'streamSpt'])->name('sppd.stream.spt');
    Route::get('/sppd/{sppd}/stream/sppd', [SppdController::class, 'streamSppd'])->name('sppd.stream.sppd');
    Route::get('/sppd/{sppd}/stream/kuitansi-rampung', [SppdController::class, 'streamKuitansiRampung'])->name('sppd.stream.kuitansi-rampung');
    Route::get('/sppd/{sppd}/stream/kuitansi-panjar', [SppdController::class, 'streamKuitansiPanjar'])->name('sppd.stream.kuitansi-panjar');
    Route::get('/sppd/{sppd}/stream/pengeluaran-riil', [SppdController::class, 'streamPengeluaranRiil'])->name('sppd.stream.pengeluaran-riil');
    Route::get('/sppd/{sppd}/stream/rincian-biaya', [SppdController::class, 'streamRincianBiaya'])->name('sppd.stream.rincian-biaya');

    // Cek / verifikasi keaslian dokumen TTE (unggah berkas) — super_admin & admin_opd.
    Route::middleware('role:super_admin|admin_opd')->group(function () {
        Route::get('/verify', \App\Livewire\VerifyDocument::class)->name('verify.index');
        // Pratinjau inline berkas unggahan (agar tidak memicu unduhan).
        Route::get('/verify/preview', [\App\Http\Controllers\DocumentPreviewController::class, 'tempUpload'])->name('verify.preview');
    });

    // Master Data — hanya dapat diakses oleh Super Admin & Admin OPD.
    // Mencegah akses lewat URL langsung oleh role lain (mis. kepala_opd).
    Route::prefix('master')->name('master.')->middleware('role:super_admin|admin_opd')->group(function () {
        // Users / Pegawai
        Route::get('/users', UsersIndex::class)->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');

        // Departments / Instansi / OPD
        Route::get('/departments', DepartmentIndex::class)->name('departments.index');
        Route::get('/departments/create', DepartmentForm::class)->name('departments.create');
        Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
        Route::get('/departments/{department}/edit', DepartmentForm::class)->name('departments.edit');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // Pengajuan Jabatan — Admin OPD mengajukan, Super Admin memverifikasi.
        // Aksi verifikasi (approve/reject) diguard super_admin di dalam komponen.
        Route::get('/position-requests', PositionRequestIndex::class)->name('position-requests.index');

        // Budgets / Anggaran — form create/edit via Livewire full-page.
        Route::get('/budgets', BudgetIndex::class)->name('budgets.index');
        Route::get('/budgets/create', BudgetForm::class)->name('budgets.create');
        Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
        Route::get('/budgets/{budget}/edit', BudgetForm::class)->name('budgets.edit');
        Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

        // Workflows SPPD & Roles/Permissions — hanya super_admin.
        Route::middleware('role:super_admin')->group(function () {
            // Master Data Jabatan — kelola penuh oleh Super Admin.
            Route::get('/positions', PositionIndex::class)->name('positions.index');

            // Master Data Wilayah & Pangkat — kelola penuh oleh Super Admin.
            Route::get('/provinces', ProvinceIndex::class)->name('provinces.index');
            Route::get('/regencies', RegencyIndex::class)->name('regencies.index');
            Route::get('/ranks', RankIndex::class)->name('ranks.index');

            // Workflow SPPD — Livewire full-page (index, create, edit).
            Route::get('/workflows', WorkflowIndex::class)->name('workflows.index');
            Route::get('/workflows/create', WorkflowForm::class)->name('workflows.create');
            Route::get('/workflows/{workflow}/edit', WorkflowForm::class)->name('workflows.edit');

            // Roles & Permissions — Livewire full-page (index, create, edit).
            Route::get('/roles', RoleIndex::class)->name('roles.index');
            Route::get('/roles/create', RoleForm::class)->name('roles.create');
            Route::get('/roles/{role}/edit', RoleForm::class)->name('roles.edit');

            // Backup Database — hanya super_admin.
            Route::get('/database/backup', \App\Livewire\DatabaseBackup::class)->name('database.backup');
        });

        // Logs Aktivitas & TTE — super_admin (semua data) & admin_opd (data department-nya).
        Route::get('/logs', \App\Livewire\LogIndex::class)->defaults('scope', 'system')->name('logs.index');
        Route::get('/logs/tte', \App\Livewire\LogIndex::class)->defaults('scope', 'tte')->name('logs.tte');
    });

    // API
    Route::get('/api/provinces/{province}/regencies', [SppdController::class, 'getRegencies'])->name('api.regencies');
    Route::get('/api/sppd/workflow-preview', [SppdController::class, 'previewWorkflow'])->name('api.sppd.workflow-preview');

    // Diagnostik queue — cukup butuh login (auth).
    // Alur: hit /system/health/ping untuk menitipkan job heartbeat ke queue,
    // lalu buka /system/health untuk melihat apakah worker memprosesnya.
    Route::get('/system/health/ping', function () {
        $token = (string) Str::uuid();
        $dispatchedAt = now()->toDateTimeString();

        Cache::store('database')->put(LogQueueHeartbeatJob::DISPATCHED_KEY, [
            'token' => $token,
            'dispatched_at' => $dispatchedAt,
        ], now()->addDays(7));

        LogQueueHeartbeatJob::dispatch($token, $dispatchedAt);

        return response()->json([
            'status' => 'dispatched',
            'message' => 'Job heartbeat dikirim ke queue. Tunggu beberapa detik, lalu buka /system/health.',
            'token' => $token,
            'dispatched_at' => $dispatchedAt,
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    })->name('system.health.ping');

    // System Health Check — deteksi worker queue jalan/tidak.
    Route::get('/system/health', function () {
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $recentFailed = DB::table('failed_jobs')->latest('failed_at')->limit(10)->get();

        $dispatched = Cache::store('database')->get(LogQueueHeartbeatJob::DISPATCHED_KEY);
        $processed = Cache::store('database')->get(LogQueueHeartbeatJob::PROCESSED_KEY);

        $heartbeat = 'never_pinged';
        if ($dispatched) {
            // 'ok' jika ping terakhir sudah diproses worker; 'pending' jika belum
            // (worker mungkin mati/lambat).
            $heartbeat = ($processed && ($processed['token'] ?? null) === $dispatched['token'])
                ? 'ok'
                : 'pending';
        }

        return response()->json([
            'status' => $failedJobs === 0 ? 'ok' : 'has_failures',
            'queue_worker_note' => 'Hit /system/health/ping lalu refresh halaman ini. Jika heartbeat.status tetap "pending", worker queue tidak berjalan.',
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'heartbeat' => [
                'status' => $heartbeat,
                'last_dispatched_at' => $dispatched['dispatched_at'] ?? null,
                'last_processed_at' => $processed['processed_at'] ?? null,
            ],
            // Penilaian otomatis berbasis kesegaran heartbeat terjadwal — sumber
            // yang sama dipakai gerbang pembuatan SPPD (QueueHealthService).
            'worker_healthy' => app(\App\Services\QueueHealthService::class)->isWorkerHealthy(),
            'failed_job_details' => $recentFailed->map(fn ($j) => [
                'id' => $j->id,
                'queue' => $j->queue,
                'failed_at' => $j->failed_at,
                'payload' => json_decode($j->payload, true)['displayName'] ?? '(unknown)',
                // Ringkasan pesan error saja — hindari bocorkan full stack trace.
                'error' => Str::limit((string) strtok((string) $j->exception, "\n"), 200),
            ]),
            'checked_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    })->name('system.health');
});

// Kirim.Chat Webhook
Route::post('/webhook/kirimchat', [KirimChatWebhookController::class, 'handle'])->name('webhook.kirimchat');

// ---------------------------------------------------------------------------
// UJI COBA — ambil 20 berita terbaru dari WordPress berita.kendarikota.go.id.
// Route terpisah & tanpa auth, hanya untuk percobaan. Bebas dihapus nanti.
// Sumber: WordPress REST API (/wp-json/wp/v2/posts).
// ---------------------------------------------------------------------------
Route::get('/uji-coba/berita', function () {
    // URL target WordPress (20 berita terbaru + data ternaut/gambar unggulan).
    $target = 'https://berita.kendarikota.go.id/wp-json/wp/v2/posts?' . http_build_query([
        'per_page' => 20,
        'orderby'  => 'date',
        'order'    => 'desc',
        '_embed'   => 1,
    ]);

    // IP VPS diblokir WAF Cloudflare situs berita. Bila layanan scraper
    // dikonfigurasi (BERITA_SCRAPER_ENDPOINT) kita lewat situ (IP residensial);
    // kalau kosong (mis. tes dari laptop yang lolos) tembak WordPress langsung.
    $endpoint = config('services.berita.scraper_endpoint');

    if ($endpoint) {
        // Bungkus target sebagai satu parameter di layanan scraper.
        $query = [
            config('services.berita.scraper_key_param') => config('services.berita.scraper_key'),
            config('services.berita.scraper_url_param') => $target,
        ];
        // Flag tambahan spesifik provider (mis. "premium=true&render=false").
        parse_str((string) config('services.berita.scraper_extra'), $extra);

        // Scraper (apalagi dgn anti-bot) bisa lambat — beri waktu lebih longgar.
        $response = \Illuminate\Support\Facades\Http::timeout(60)
            ->get(rtrim($endpoint, '/'), array_merge($query, $extra));
        $via = 'scraper';
    } else {
        $response = \Illuminate\Support\Facades\Http::timeout(15)->acceptJson()->get($target);
        $via = 'langsung';
    }

    if ($response->failed()) {
        return response()->json([
            'ok' => false,
            'message' => 'Gagal mengambil berita dari sumber WordPress.',
            'via' => $via,
            'status' => $response->status(),
        ], 502, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // Bila lewat scraper tapi Cloudflare masih menghadang, body bisa berupa
    // HTML (halaman blokir/challenge), bukan JSON. Deteksi & beri petunjuk.
    $posts = $response->json();
    if (! is_array($posts)) {
        return response()->json([
            'ok' => false,
            'message' => 'Respons bukan JSON berita — kemungkinan scraper belum menembus Cloudflare. '
                . 'Coba sesuaikan BERITA_SCRAPER_EXTRA (aktifkan proxy premium/residensial, matikan render JS untuk endpoint API).',
            'via' => $via,
            'status' => $response->status(),
            'preview' => \Illuminate\Support\Str::limit(trim(strip_tags($response->body())), 300),
        ], 502, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    $berita = collect($posts)->map(fn ($post) => [
        'id' => $post['id'] ?? null,
        'judul' => html_entity_decode(strip_tags($post['title']['rendered'] ?? '')),
        'tanggal' => $post['date'] ?? null,
        'tautan' => $post['link'] ?? null,
        'ringkasan' => trim(html_entity_decode(strip_tags($post['excerpt']['rendered'] ?? ''))),
        'gambar' => $post['_embedded']['wp:featuredmedia'][0]['source_url'] ?? null,
    ])->values();

    return response()->json([
        'ok' => true,
        'via' => $via,
        'jumlah' => $berita->count(),
        'data' => $berita,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->name('uji-coba.berita');
