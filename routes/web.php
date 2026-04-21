<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\SppdController;
use App\Http\Controllers\SppdWorkflowController;
use App\Http\Controllers\UserController;
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

  // SPPD
  Route::get('/sppd', [SppdController::class, 'index'])->name('sppd.index');
  Route::get('/sppd/create', [SppdController::class, 'create'])->name('sppd.create');
  Route::post('/sppd', [SppdController::class, 'store'])->name('sppd.store');
  Route::get('/sppd/{sppd}', [SppdController::class, 'show'])->name('sppd.show');
  Route::post('/sppd/{sppd}/submit', [SppdController::class, 'submit'])->name('sppd.submit');
  Route::post('/sppd/{sppd}/approve', [SppdController::class, 'approve'])->name('sppd.approve');
  Route::post('/sppd/{sppd}/reject', [SppdController::class, 'reject'])->name('sppd.reject');
  Route::delete('/sppd/{sppd}', [SppdController::class, 'destroy'])->name('sppd.destroy');

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

    // Workflows SPPD
    Route::resource('workflows', SppdWorkflowController::class)->except(['show']);
  });

  // API
  Route::get('/api/provinces/{province}/regencies', [SppdController::class, 'getRegencies'])->name('api.regencies');
});
