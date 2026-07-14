<?php

namespace App\Actions\Bookings;

use App\Actions\Memberships\ConsumeMembershipCredit;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\ScheduledSession;
use App\Models\Student;
use App\Models\StudentMembership;
use Illuminate\Support\Facades\DB;

/**
 * Books a student into a group session, enforcing the business rules: valid
 * membership, plan coverage, available credit and seat capacity (with a row
 * lock to avoid overbooking under concurrency). Consumes one credit.
 */
class BookSession
{
    public function __construct(private ConsumeMembershipCredit $consume) {}

    public function execute(Student $student, ScheduledSession $session): Booking
    {
        if ($session->status !== SessionStatus::Scheduled) {
            throw BookingException::notBookable();
        }

        if ($session->starts_at->isPast()) {
            throw BookingException::alreadyStarted();
        }

        if ($this->studentAlreadyBooked($student, $session)) {
            throw BookingException::alreadyBooked();
        }

        $membership = $student->currentMembership();
        if (! $membership || ! $membership->isCurrentlyActive()) {
            throw BookingException::noActiveMembership();
        }

        // A missing plan (deleted from the catalog) can't be checked for coverage.
        if ($membership->plan !== null && ! $membership->plan->coversActivity($session->activity)) {
            throw BookingException::activityNotCovered();
        }

        return DB::transaction(function () use ($student, $session, $membership) {
            // Re-check capacity under a row lock so two concurrent bookings can't
            // both slip past a last seat. Locking the session also serialises two
            // concurrent bookings of the *same* session by the same student.
            $locked = ScheduledSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->seatsTaken() >= $locked->capacity) {
                throw BookingException::full();
            }

            // Lock the membership row too, so two concurrent bookings on *different*
            // sessions can't both read the same remaining balance and overspend it.
            $lockedMembership = StudentMembership::query()
                ->whereKey($membership->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Re-check the duplicate inside the locked transaction; the pre-check
            // above is racy on its own.
            if ($this->studentAlreadyBooked($student, $locked)) {
                throw BookingException::alreadyBooked();
            }

            if (! $lockedMembership->hasAvailableCredit()) {
                throw BookingException::noCredit();
            }

            $booking = Booking::place($locked, $student, $lockedMembership);

            $this->consume->execute($lockedMembership, $booking);

            return $booking;
        });
    }

    private function studentAlreadyBooked(Student $student, ScheduledSession $session): bool
    {
        return $session->bookings()
            ->where('student_id', $student->getKey())
            ->whereIn('status', [
                BookingStatus::Booked->value,
                BookingStatus::Attended->value,
            ])
            ->exists();
    }
}
