<?php

use App\Actions\Auth\VerifyStudentEmail;
use App\Http\Controllers\LandingController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyEmail;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\Plans;
use App\Livewire\Portal\Schedule;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/ingresar', Login::class)->name('login');
    Route::get('/registrarse', Register::class)->name('register');
});

Route::post('/salir', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

// Email verification. Until the address is proven, the account is not linked to
// any ficha (see App\Actions\Auth\VerifyStudentEmail).
Route::middleware('auth')->group(function () {
    Route::get('/verificar-email', VerifyEmail::class)->name('verification.notice');

    // EmailVerificationRequest validates the signed id/hash; the action does the
    // work its fulfill() would, but atomically with attaching the ficha.
    Route::get('/verificar-email/{id}/{hash}', function (EmailVerificationRequest $request, VerifyStudentEmail $verify) {
        $verify->execute($request->user());

        return redirect()->route('portal.dashboard');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
});

// Student portal (authenticated students with a verified email only). `student`
// runs before `verified` so staff get a plain 403 instead of a verification prompt
// for a portal they cannot enter anyway.
Route::middleware(['auth', 'student', 'verified'])->prefix('portal')->group(function () {
    Route::get('/', Dashboard::class)->name('portal.dashboard');
    Route::get('/reservar', Schedule::class)->name('portal.schedule');
    Route::get('/pases', Plans::class)->name('portal.plans');
});
