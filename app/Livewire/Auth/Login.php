<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
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

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Las credenciales no coinciden.');

            return null;
        }

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
