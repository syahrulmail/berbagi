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
use App\Http\Controllers\MobileAppController;
use App\Http\Controllers\MobileAuthController;
use App\Http\Controllers\MobileCrudController;
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

Route::post('/program/{program}/suka', [PublicController::class, 'suka'])
    ->name('public.program.suka')
    ->middleware('throttle:30,1');

Route::post('/program/{program}/klik', [PublicController::class, 'klik'])
    ->name('public.program.klik')
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
| Mobile Login (guest) — /mo/login
|--------------------------------------------------------------------------
*/
Route::prefix('mo')->name('mo.')->middleware('noindex')->group(function () {
    Route::get('/login', [MobileAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [MobileAuthController::class, 'login']);
});

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

    /*
    |--------------------------------------------------------------------------
    | Mobile App (Berbagi Mobile) — desain mobile-first, kelak jadi app native.
    |--------------------------------------------------------------------------
    */
    Route::prefix('mo')->name('mo.')->middleware('role:admin,supervisor,agen', 'noindex')->group(function () {
        Route::get('/', [MobileAppController::class, 'home'])->name('home');
        Route::get('/dashboard', [MobileAppController::class, 'dashboard'])->name('dashboard');
        Route::get('/donasi', [MobileAppController::class, 'donations'])->name('donations');
        Route::get('/kontak', [MobileAppController::class, 'contacts'])->name('contacts');
        Route::get('/program', [MobileAppController::class, 'programs'])->name('programs');
        Route::get('/lainnya', [MobileAppController::class, 'more'])->name('more');
        Route::get('/cabang', [MobileAppController::class, 'branches'])->name('branches')->middleware('role:admin');
        Route::get('/pengguna', [MobileAppController::class, 'users'])->name('users')->middleware('role:admin');

        Route::get('/api/donasi/{donation}/detail', [MobileAppController::class, 'donationDetail'])->name('api.donation-detail');
        Route::get('/api/kontak/{contact}/detail', [MobileAppController::class, 'contactDetail'])->name('api.contact-detail');
        Route::get('/api', fn () => url('/mo/api'))->name('api');

        // Donasi CRUD
        Route::get('/donasi/tambah', [MobileCrudController::class, 'donationCreate'])->name('donation.create');
        Route::post('/donasi/tambah', [MobileCrudController::class, 'donationStore'])->name('donation.store');
        Route::get('/donasi/{donation}/edit', [MobileCrudController::class, 'donationEdit'])->name('donation.edit');
        Route::put('/donasi/{donation}', [MobileCrudController::class, 'donationUpdate'])->name('donation.update');
        Route::delete('/donasi/{donation}', [MobileCrudController::class, 'donationDestroy'])->name('donation.destroy');

        // Kontak CRUD
        Route::get('/kontak/tambah', [MobileCrudController::class, 'contactCreate'])->name('contact.create');
        Route::post('/kontak/tambah', [MobileCrudController::class, 'contactStore'])->name('contact.store');
        Route::get('/kontak/{contact}/edit', [MobileCrudController::class, 'contactEdit'])->name('contact.edit');
        Route::put('/kontak/{contact}', [MobileCrudController::class, 'contactUpdate'])->name('contact.update');
        Route::delete('/kontak/{contact}', [MobileCrudController::class, 'contactDestroy'])->name('contact.destroy');

        // Program CRUD
        Route::get('/program/tambah', [MobileCrudController::class, 'programCreate'])->name('program.create')->middleware('role:admin,supervisor,agen');
        Route::post('/program/tambah', [MobileCrudController::class, 'programStore'])->name('program.store')->middleware('role:admin,supervisor,agen');
        Route::get('/program/{program}/edit', [MobileCrudController::class, 'programEdit'])->name('program.edit')->middleware('role:admin,supervisor,agen');
        Route::put('/program/{program}', [MobileCrudController::class, 'programUpdate'])->name('program.update')->middleware('role:admin,supervisor,agen');
        Route::delete('/program/{program}', [MobileCrudController::class, 'programDestroy'])->name('program.destroy')->middleware('role:admin,supervisor,agen');

        // Cabang CRUD (admin only)
        Route::get('/cabang/tambah', [MobileCrudController::class, 'branchCreate'])->name('branch.create')->middleware('role:admin');
        Route::post('/cabang/tambah', [MobileCrudController::class, 'branchStore'])->name('branch.store')->middleware('role:admin');
        Route::get('/cabang/{branch}/edit', [MobileCrudController::class, 'branchEdit'])->name('branch.edit')->middleware('role:admin');
        Route::put('/cabang/{branch}', [MobileCrudController::class, 'branchUpdate'])->name('branch.update')->middleware('role:admin');
        Route::delete('/cabang/{branch}', [MobileCrudController::class, 'branchDestroy'])->name('branch.destroy')->middleware('role:admin');

        // Pengguna CRUD (admin only)
        Route::get('/pengguna/tambah', [MobileCrudController::class, 'userCreate'])->name('user.create')->middleware('role:admin');
        Route::post('/pengguna/tambah', [MobileCrudController::class, 'userStore'])->name('user.store')->middleware('role:admin');
        Route::get('/pengguna/{user}/edit', [MobileCrudController::class, 'userEdit'])->name('user.edit')->middleware('role:admin');
        Route::put('/pengguna/{user}', [MobileCrudController::class, 'userUpdate'])->name('user.update')->middleware('role:admin');
        Route::delete('/pengguna/{user}', [MobileCrudController::class, 'userDestroy'])->name('user.destroy')->middleware('role:admin');
    });
});
