<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SppdActualExpenseController;
use App\Http\Controllers\SppdAdvanceReceiptController;
use App\Http\Controllers\SppdController;
use App\Http\Controllers\SppdCostDetailController;
use App\Http\Controllers\SppdDigitalSignatureController;
use App\Http\Controllers\SppdWorkflowController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KirimChatWebhookController;
use App\Livewire\Sppd\SppdCalendar;
use App\Livewire\Sppd\SppdCreate;
use App\Livewire\Sppd\SppdCreateDetails;
use App\Livewire\Sppd\SppdIndex;
use App\Livewire\Sppd\SppdShow;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

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

    // Workflows Preview
    Route::get('/workflows/preview', [SppdWorkflowController::class, 'preview'])->name('workflows.preview');

    // Master Data
    Route::prefix('master')->name('master.')->group(function () {
        // Users / Pegawai
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/users/{user}/toggle', [UserController::class, 'toggleActive'])->name('users.toggle');

        // Departments / Instansi / OPD
        Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
        Route::get('/departments/create', [DepartmentController::class, 'create'])->name('departments.create');
        Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
        Route::get('/departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
        Route::get('/departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
        Route::put('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
        Route::delete('/departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

        // Budgets / Anggaran
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('/budgets/{budget}', [BudgetController::class, 'show'])->name('budgets.show');
        Route::get('/budgets/{budget}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('/budgets/{budget}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy'])->name('budgets.destroy');

        // Workflows SPPD (hanya super_admin)
        Route::resource('workflows', SppdWorkflowController::class)->except(['show']);

        // Roles & Permissions (hanya super_admin)
        // create/edit ditangani oleh Livewire RoleForm component
        Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', fn () => view('master.roles.create'))->name('roles.create');
        Route::get('/roles/{role}/edit', fn (\Spatie\Permission\Models\Role $role) => view('master.roles.edit', compact('role')))->name('roles.edit');
        Route::delete('/roles/{role}', [RolePermissionController::class, 'destroy'])->name('roles.destroy');
    });

    // API
    Route::get('/api/provinces/{province}/regencies', [SppdController::class, 'getRegencies'])->name('api.regencies');
    Route::get('/api/sppd/workflow-preview', [SppdController::class, 'previewWorkflow'])->name('api.sppd.workflow-preview');

    // System Health Check — hanya super_admin
    Route::get('/system/health', function () {
        // abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin_opd']), 403);

        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $recentFailed = DB::table('failed_jobs')->latest('failed_at')->limit(10)->get();

        // pending=0 berarti worker aktif memproses (meski job bisa gagal)
        $workerLikelyRunning = true; // jika pending_jobs tidak menumpuk, worker berjalan

        return response()->json([
            'status' => $failedJobs === 0 ? 'ok' : 'has_failures',
            'queue_worker_note' => 'Jika pending_jobs tidak menumpuk, worker sedang berjalan.',
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'failed_job_details' => $recentFailed->map(fn ($j) => [
                'id' => $j->id,
                'queue' => $j->queue,
                'failed_at' => $j->failed_at,
                'payload' => json_decode($j->payload, true)['displayName'] ?? '(unknown)',
                'exception' => $j->exception,  // full exception untuk debug
            ]),
            'checked_at' => now()->toDateTimeString(),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    })->name('system.health');
});

// Kirim.Chat Webhook
Route::post('/webhook/kirimchat', [KirimChatWebhookController::class, 'handle'])->name('webhook.kirimchat');
