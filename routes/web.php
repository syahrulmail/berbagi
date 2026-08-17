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
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhatsAppController;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $programs = Program::where('is_active', true)
        ->withSum('donations as total_collected', 'amount')
        ->with('campaignTags')
        ->orderBy('name')
        ->get();

    return view('public.home', compact('programs'));
})->name('home');

Route::get('/program/{program:slug}', function (Program $program) {
    $collected = $program->donations()->sum('amount');

    return view('public.program', compact('program', 'collected'));
})->name('public.program');

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
    });

    Route::middleware('role:admin,supervisor')->group(function () {
        Route::resource('banners', BannerController::class)->except('show');
    });
});
