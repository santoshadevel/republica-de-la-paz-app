<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Login extends Component
{
    #[Validate('required|email')]
    public string $email = '';

    #[Validate('required')]
    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate();

        // Throttle brute-force attempts: 5 failures per email+IP, then a lockout.
        $throttleKey = 'login:'.Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Demasiados intentos. Probá de nuevo en {$seconds} segundos.");

            return null;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);
            $this->addError('email', 'Las credenciales no coinciden.');

            return null;
        }

        RateLimiter::clear($throttleKey);
        Session::regenerate();

        // Students go to the portal; staff to the admin panel.
        return Auth::user()->isStudent()
            ? redirect()->route('portal.dashboard')
            : redirect('/admin');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
