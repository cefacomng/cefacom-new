<?php

use App\Http\Controllers\Account\EmailChangeVerificationController;
use App\Http\Controllers\Account\SettingsController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\FoundationController;
use App\Http\Controllers\Shell\CustomerPageController;
use App\Http\Controllers\Shell\DesignSystemController;
use App\Support\Navigation;
use Illuminate\Support\Facades\Route;

Route::get('/', FoundationController::class)->name('home');
Route::get('/design-system', DesignSystemController::class)->name('design-system');

/* Guests only: signed-in people are redirected to their own area. */
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');

    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');

    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:password-email')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'store'])->middleware('throttle:password-email')->name('password.store');
});

/* Any signed-in person (customer or staff). */
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:verification')->name('verification.send');

    Route::get('/email/change/{id}/{hash}', EmailChangeVerificationController::class)->middleware('signed')->name('email.change.verify');
});

/** Account settings routes, shared by the customer and admin areas. */
$accountRoutes = function (string $prefix, string $name) {
    Route::prefix($prefix)->name($name.'.')->group(function () {
        Route::get('/', [SettingsController::class, 'show'])->name('show');
        Route::patch('/profile', [SettingsController::class, 'updateProfile'])->name('profile');
        Route::post('/email', [SettingsController::class, 'requestEmailChange'])->middleware('throttle:verification')->name('email');
        Route::post('/email/resend', [SettingsController::class, 'resendEmailChange'])->middleware('throttle:verification')->name('email.resend');
        Route::delete('/email', [SettingsController::class, 'cancelEmailChange'])->name('email.cancel');
        Route::patch('/preferences', [SettingsController::class, 'updatePreferences'])->name('preferences');
        Route::put('/password', [SettingsController::class, 'updatePassword'])->name('password');
        Route::post('/sessions', [SettingsController::class, 'signOutOthers'])->name('sessions');
    });
};

/* Customer area. Unverified customers may sign in, see the overview and manage their account;
   everything that sends or spends requires a verified email address. */
Route::middleware(['auth', 'customer'])->prefix('app')->name('app.')->group(function () use ($accountRoutes) {
    Route::get('/', [CustomerPageController::class, 'overview'])->name('overview');

    Route::group([], fn () => $accountRoutes('settings', 'settings'));

    Route::middleware('verified')->get('/{section}', [CustomerPageController::class, 'section'])
        ->whereIn('section', array_values(array_diff(Navigation::keys('customer'), ['overview', 'settings'])))
        ->name('section');
});

/* Admin area. Hidden (404) from anyone who is not staff; each section then needs its own permission. */
Route::middleware(['auth', 'staff', 'verified'])->prefix('admin')->name('admin.')->group(function () use ($accountRoutes) {
    Route::get('/', [AdminPageController::class, 'overview'])->name('overview');

    Route::group([], fn () => $accountRoutes('account', 'account'));

    Route::get('/{section}', [AdminPageController::class, 'section'])
        ->whereIn('section', array_values(array_diff(Navigation::keys('admin'), ['overview'])))
        ->name('section');
});
