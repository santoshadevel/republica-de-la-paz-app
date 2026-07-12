<?php

namespace App\Actions\Bookings;

use App\Actions\Memberships\ConsumeMembershipCredit;
use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\ScheduledSession;
use App\Models\Student;
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
            // both slip past a last seat.
            $locked = ScheduledSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->seatsTaken() >= $locked->capacity) {
                throw BookingException::full();
            }

            if (! $membership->hasAvailableCredit()) {
                throw BookingException::noCredit();
            }

            $booking = $locked->bookings()->create([
                'student_id' => $student->getKey(),
                'student_membership_id' => $membership->getKey(),
                'status' => BookingStatus::Booked,
                'booked_at' => now(),
            ]);

            $this->consume->execute($membership, $booking);

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
