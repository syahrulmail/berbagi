<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CampaignTagController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProfileController;
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

Route::get('/cs/{agentSlug}/program/{program:slug}', [PublicController::class, 'agentProgram'])->name('public.agent-program');

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('role:admin,supervisor,agen');

    Route::middleware('role:admin')->group(function () {
        Route::resource('branches', BranchController::class)->except('show');
        Route::resource('users', UserController::class)->except('show');
        Route::resource('campaign-tags', CampaignTagController::class)->except('show');
        Route::resource('achievements', AchievementController::class)->except('show');
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('activity-logs', ActivityLogController::class)->only(['index']);
    });

    Route::middleware('role:admin,supervisor,agen')->group(function () {
        Route::resource('contacts', ContactController::class)->except('show');
        Route::post('contacts/quick', [ContactController::class, 'storeQuick'])->name('contacts.quick');
        Route::post('contacts/paste', [ContactController::class, 'storePaste'])->name('contacts.paste');
        Route::post('contacts/import', [ContactController::class, 'import'])->name('contacts.import');
        Route::get('contacts/{contact}/detail', [ContactController::class, 'detail'])->name('contacts.detail');
        Route::get('contacts/{contact}/edit-fields', [ContactController::class, 'editFields'])->name('contacts.edit-fields');
        Route::resource('donations', DonationController::class)->except('show');
        Route::get('donations/{donation}/detail', [DonationController::class, 'detail'])->name('donations.detail');
        Route::get('donations/{donation}/edit-fields', [DonationController::class, 'editFields'])->name('donations.edit-fields');
        Route::resource('programs', ProgramController::class)->except('show');
        Route::post('/uploads/rich-image', [ProgramController::class, 'uploadRichImage'])->name('uploads.rich-image');
        Route::resource('whatsapp', WhatsAppController::class)->only(['index', 'create', 'store', 'destroy']);
        Route::resource('followups', WaFollowupController::class)->only(['index']);
        Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('banners', BannerController::class)->except('show');
        Route::delete('followups/{followup}', [WaFollowupController::class, 'destroy'])->name('followups.destroy');
    });
});
