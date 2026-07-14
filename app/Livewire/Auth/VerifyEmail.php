<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Prompts for the verification link and resends it on request. */
#[Layout('components.layouts.app')]
class VerifyEmail extends Component
{
    public function mount()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('portal.dashboard');
        }

        return null;
    }

    public function resend(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        // Resending is a mail send on an unauthenticated-ish surface: cap it.
        $throttleKey = 'verification-send:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            session()->flash('error', "Esperá {$seconds} segundos antes de pedir otro enlace.");

            return;
        }

        RateLimiter::hit($throttleKey, 300);
        $user->sendEmailVerificationNotification();

        session()->flash('status', 'Te reenviamos el enlace de verificación.');
    }

    public function render()
    {
        return view('livewire.auth.verify-email', [
            'email' => Auth::user()->email,
        ]);
    }
}
