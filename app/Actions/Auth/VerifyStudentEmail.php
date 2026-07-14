<?php

namespace App\Actions\Auth;

use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

/**
 * Marks a portal account's address verified and attaches its domain ficha, in one
 * transaction.
 *
 * Both writes must land together: the framework's EmailVerificationRequest::fulfill()
 * commits the verification *before* firing Verified, so a listener that failed to
 * attach the ficha would leave the account verified and fichaless forever — the link
 * cannot re-fire the event once the address is verified. Here a failure rolls the
 * verification back and the student can just click the link again.
 */
class VerifyStudentEmail
{
    public function execute(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        DB::transaction(function () use ($user) {
            $user->markEmailAsVerified();

            if ($user->isStudent()) {
                Student::registerFrom($user, $user->name);
            }
        });

        event(new Verified($user));
    }
}
