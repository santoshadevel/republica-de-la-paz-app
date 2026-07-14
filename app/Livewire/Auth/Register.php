<?php

namespace App\Livewire\Auth;

use App\Actions\Auth\RegisterStudent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register()
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = app(RegisterStudent::class)->execute($this->name, $this->email, $this->password);

        Auth::login($user);
        Session::regenerate();

        // The ficha is only attached once the address is verified, so the portal
        // has nothing to show yet.
        return redirect()->route('verification.notice');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
