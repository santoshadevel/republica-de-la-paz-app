<?php

namespace App\Actions\Events;

use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use App\Exceptions\EventException;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Registers a student for an event, enforcing status, timing, duplicates and
 * capacity (with a row lock to avoid overbooking). Event payment is handled
 * separately (accounting, Fase 7).
 */
class RegisterForEvent
{
    public function execute(Student $student, Event $event): EventRegistration
    {
        if ($event->status !== EventStatus::Scheduled) {
            throw EventException::notOpen();
        }

        if ($event->starts_at->isPast()) {
            throw EventException::alreadyStarted();
        }

        if ($this->alreadyRegistered($student, $event)) {
            throw EventException::alreadyRegistered();
        }

        return DB::transaction(function () use ($student, $event) {
            $locked = Event::query()->whereKey($event->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->isFull()) {
                throw EventException::full();
            }

            return EventRegistration::place($locked, $student);
        });
    }

    private function alreadyRegistered(Student $student, Event $event): bool
    {
        return $event->registrations()
            ->where('student_id', $student->getKey())
            ->whereIn('status', [
                EventRegistrationStatus::Registered->value,
                EventRegistrationStatus::Attended->value,
            ])
            ->exists();
    }
}
