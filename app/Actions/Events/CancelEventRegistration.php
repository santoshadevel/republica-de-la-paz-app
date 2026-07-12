<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Exceptions\EventException;
use App\Models\EventRegistration;

/** Cancels an event registration, freeing the seat. */
class CancelEventRegistration
{
    public function execute(EventRegistration $registration): EventRegistration
    {
        if ($registration->status !== EventRegistrationStatus::Registered) {
            throw EventException::notCancellable();
        }

        $registration->update([
            'status' => EventRegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $registration;
    }
}
