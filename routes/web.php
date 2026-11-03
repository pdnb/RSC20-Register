<?php

use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\FinanceReportController;
use App\Http\Controllers\Auth\Auth0Controller;
use App\Http\Controllers\FileController;
use App\Http\Controllers\TicketController;
use App\Livewire\Admin;
use App\Livewire\Auth\AdminLogin;
use App\Livewire\Auth\ChangePassword;
use App\Livewire\Auth\Login;
use App\Livewire\Coordinator;
use App\Livewire\Staff\Scanner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::livewire('/login', Login::class)->name('login');
    Route::livewire('/admin/login', AdminLogin::class)->name('admin.login');
    Route::get('/admin/auth/auth0/redirect', [Auth0Controller::class, 'redirect'])
        ->middleware('throttle:admin-auth0')->name('admin.auth0.redirect');
});

Route::get('/admin/auth/auth0/callback', [Auth0Controller::class, 'callback'])
    ->middleware('throttle:admin-auth0')->name('admin.auth0.callback');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::post('/admin/logout', [Auth0Controller::class, 'logout'])->middleware('auth')->name('admin.logout');

// Public ticket behind an unguessable ULID — shows only name, university and the QR.
Route::get('/ticket/{token}', TicketController::class)->middleware('throttle:60,1')->name('ticket');

Route::middleware('auth')->group(function () {
    Route::get('/files/slip/{group}', [FileController::class, 'slip'])->name('files.slip');
    Route::get('/files/badge/{participant}', [FileController::class, 'badge'])->name('files.badge');
    Route::get('/files/qr/{participant}', [FileController::class, 'qr'])->name('files.qr');
});

Route::middleware(['auth', 'role:coordinator'])->group(function () {
    Route::livewire('/dashboard', Coordinator\GroupManager::class)->name('coordinator.dashboard');
    Route::livewire('/payment', Coordinator\Payment::class)->name('coordinator.payment');
    Route::livewire('/qr', Coordinator\QrCards::class)->name('coordinator.qr');
    Route::livewire('/password', ChangePassword::class)->name('coordinator.password');
});

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::livewire('/staff/checkin', Scanner::class)->name('staff.checkin');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', Admin\Dashboard::class)->name('dashboard');
    Route::livewire('/groups', Admin\Groups::class)->name('groups');
    Route::livewire('/groups/{group}', Coordinator\GroupManager::class)->name('groups.show');
    Route::livewire('/participants', Admin\Participants::class)->name('participants');
    Route::livewire('/coordinators', Admin\CoordinatorAccounts::class)->name('coordinators');
    Route::livewire('/users', Admin\Users::class)->name('users');
    Route::livewire('/settings', Admin\Settings::class)->name('settings');

    Route::get('/exports', [ExportController::class, 'index'])->name('exports');
    Route::get('/exports/{type}', [ExportController::class, 'download'])->name('exports.download');

    Route::livewire('/finance', Admin\FinanceReport::class)->name('finance');
    Route::get('/finance/{submission}/{format}', [FinanceReportController::class, 'download'])
        ->whereIn('format', ['pdf', 'xlsx', 'zip'])->name('finance.download');
});
