<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Exceptions\EventException;
use App\Models\EventRegistration;

/** Records whether a registered student attended the event. */
class MarkEventAttendance
{
    public function execute(EventRegistration $registration, bool $attended): EventRegistration
    {
        if ($registration->status === EventRegistrationStatus::Cancelled) {
            throw EventException::notCancellable();
        }

        $registration->update([
            'status' => $attended ? EventRegistrationStatus::Attended : EventRegistrationStatus::NoShow,
        ]);

        return $registration;
    }
}
