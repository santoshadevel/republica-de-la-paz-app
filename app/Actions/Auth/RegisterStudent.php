<?php

namespace App\Actions\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registers a student portal account: a User with the `student` role, linked to
 * the domain ficha (creating it, or attaching to a ficha staff already made for
 * that email). Reusable by the web portal and the future API.
 */
class RegisterStudent
{
    public function execute(string $name, string $email, string $password): User
    {
        return DB::transaction(function () use ($name, $email, $password) {
            $user = User::registerStudent($name, $email, $password);
            Student::registerFrom($user, $name);

            return $user;
        });
    }
}
