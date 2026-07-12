<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;

/**
 * Records whether a student attended a session. A no-show keeps the credit
 * consumed (no refund), per the business rules.
 */
class MarkAttendance
{
    public function execute(Booking $booking, bool $attended): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Booked, BookingStatus::Attended, BookingStatus::NoShow], true)) {
            throw BookingException::notCancellable();
        }

        $booking->update([
            'status' => $attended ? BookingStatus::Attended : BookingStatus::NoShow,
        ]);

        return $booking;
    }
}
