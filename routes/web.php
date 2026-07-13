<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Portal\Dashboard;
use App\Livewire\Portal\Plans;
use App\Livewire\Portal\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');

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

// Student portal (authenticated students only).
Route::middleware(['auth', 'student'])->prefix('portal')->group(function () {
    Route::get('/', Dashboard::class)->name('portal.dashboard');
    Route::get('/reservar', Schedule::class)->name('portal.schedule');
    Route::get('/pases', Plans::class)->name('portal.plans');
});
