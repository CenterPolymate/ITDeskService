<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

use App\Http\Controllers\Api\LineLiffController;
use App\Http\Controllers\Api\LineWebhookController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\ImpersonateController;
use App\Http\Controllers\NormalUserController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserManagementController;

Route::post('/api/line/webhook', [LineWebhookController::class, 'handle']);

Route::get('/line/link-account', [LineLiffController::class, 'showLoginForm']);
Route::post('/line/link-account', [LineLiffController::class, 'linkAccount']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Ticket Routes
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{id}', [TicketController::class, 'show'])->name('tickets.show');
    Route::get('/tickets/{id}/print', [TicketController::class, 'print'])->name('tickets.print');
    Route::put('/tickets/{id}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus'])->name('tickets.updateStatus');

    // Ticket Comments Route
    Route::post('/tickets/{id}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');

    // SLA Management
    // (Removed as SLA is now globally fixed at 4 hours)

    // User Management
    Route::post('users/{user}/force-reset-password', [UserManagementController::class, 'forceResetPassword'])->name('users.force_reset_password');
    Route::get('users/export', [UserManagementController::class, 'export'])->name('users.export');
    Route::resource('users', UserManagementController::class)->except(['show']);
    Route::post('normal_users/import', [NormalUserController::class, 'import'])->name('normal_users.import');
    Route::get('normal_users/export', [NormalUserController::class, 'export'])->name('normal_users.export');
    Route::post('normal_users/{normal_user}/force-reset-password', [NormalUserController::class, 'forceResetPassword'])->name('normal_users.force_reset_password');
    Route::resource('normal_users', NormalUserController::class)->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::post('companies/import', [CompanyController::class, 'import'])->name('companies.import');
    Route::get('companies/export', [CompanyController::class, 'export'])->name('companies.export');
    Route::resource('companies', CompanyController::class)->except(['show']);
    Route::post('companies/{company}/departments', [CompanyController::class, 'storeDepartment'])->name('companies.departments.store');
    Route::post('companies/{company}/departments/map', [CompanyController::class, 'mapDepartment'])->name('companies.departments.map');
    Route::put('companies/departments/{department}', [CompanyController::class, 'updateDepartment'])->name('companies.departments.update');
    Route::delete('companies/departments/{department}', [CompanyController::class, 'destroyDepartment'])->name('companies.departments.destroy');

    Route::resource('holidays', HolidayController::class)->only(['index', 'store', 'destroy']);

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('audit_logs', [AuditLogController::class, 'index'])->name('audit_logs.index');

    // Backup Routes
    Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('backups/download', [BackupController::class, 'download'])->name('backups.download');
    Route::delete('backups', [BackupController::class, 'destroy'])->name('backups.destroy');

    // Impersonate Routes
    Route::post('/impersonate/{user}', [ImpersonateController::class, 'impersonate'])->name('impersonate');
    Route::post('/impersonate-leave', [ImpersonateController::class, 'leave'])->name('impersonate.leave');
});
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
