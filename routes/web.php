<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CampaignTagController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaFollowupController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicController::class, 'home'])->name('home');

Route::get('/program/{program:slug}', [PublicController::class, 'program'])->name('public.program');

Route::get('/cs/{slug}', [PublicController::class, 'agent'])->name('public.agent');

Route::post('/wa/followup', [PublicController::class, 'followup'])
    ->name('wa.followup')
    ->middleware('throttle:30,1');

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Admin, Supervisor, Agen, Donatur)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('campaign-tags', CampaignTagController::class)->except('show');
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('activity-logs', ActivityLogController::class)->only(['index']);
    });

    Route::middleware('role:admin,supervisor,agen')->group(function () {
        Route::resource('contacts', ContactController::class)->except('show');
        Route::resource('donations', DonationController::class)->except('show');
        Route::resource('programs', ProgramController::class)->except('show');
        Route::resource('whatsapp', WhatsAppController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::resource('followups', WaFollowupController::class)->only(['index']);
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('banners', BannerController::class)->except('show');
        Route::delete('followups/{followup}', [WaFollowupController::class, 'destroy'])->name('followups.destroy');
    });
});
