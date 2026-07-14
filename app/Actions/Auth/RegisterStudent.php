<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;

/**
 * Registers a student portal account: a User with the `student` role. The domain
 * ficha is NOT linked here — staff may already have loaded a ficha for this email
 * (with its credits, history and notes), so it is only attached once the address
 * is verified (see App\Actions\Auth\VerifyStudentEmail). Reusable by the web portal
 * and the future API.
 */
class RegisterStudent
{
    public function execute(string $name, string $email, string $password): User
    {
        $user = User::registerStudent($name, $email, $password);

        // Sends the verification link (MustVerifyEmail + framework listener).
        event(new Registered($user));

        return $user;
    }
}
