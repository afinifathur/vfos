<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\InvestmentController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\WealthStatementController;
use Illuminate\Support\Facades\Route;

// ── Auth (Guest only) ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Protected Routes (Auth required) ─────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('wealth-statement', [WealthStatementController::class, 'index'])->name('wealth-statement');
    Route::get('wealth-statement/pdf', [WealthStatementController::class, 'pdf'])->name('wealth-statement.pdf');

    Route::get('accounts/{account}/reconcile', [AccountController::class, 'reconcile'])->name('accounts.reconcile');
    Route::post('accounts/{account}/reconcile', [AccountController::class, 'processReconcile'])->name('accounts.processReconcile');
    Route::resource('accounts', AccountController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('subcategories', SubcategoryController::class);
    Route::resource('transactions', TransactionController::class);
    Route::resource('budgets', BudgetController::class);
    Route::resource('debts', DebtController::class);
    Route::resource('receivables', ReceivableController::class);
    Route::resource('investments', InvestmentController::class);
    Route::resource('assets', AssetController::class);

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/profile', [SettingController::class, 'updateProfile'])->name('settings.profile');
    Route::post('settings/preferences', [SettingController::class, 'updatePreferences'])->name('settings.preferences');
    Route::post('settings/security', [SettingController::class, 'updateSecurity'])->name('settings.security');
    Route::post('settings/notifications', [SettingController::class, 'updateNotifications'])->name('settings.notifications');
    Route::post('settings/sync-db', [SettingController::class, 'syncDb'])->name('settings.sync');

    Route::post('investments/refresh', [InvestmentController::class, 'refresh'])->name('investments.refresh');
    Route::post('investments/{investment}/refresh-item', [InvestmentController::class, 'refreshItem'])->name('investments.refresh-item');

});
