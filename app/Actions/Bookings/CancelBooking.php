<?php

namespace App\Actions\Bookings;

use App\Actions\Memberships\RefundMembershipCredit;
use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

/**
 * Cancels a booking. If cancelled at least the configured window before the
 * session starts, the credit is refunded and the seat freed; otherwise the
 * practice is consumed (no refund). See config/booking.php.
 */
class CancelBooking
{
    public function __construct(private RefundMembershipCredit $refund) {}

    public function execute(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::Booked) {
            throw BookingException::notCancellable();
        }

        return DB::transaction(function () use ($booking) {
            $booking->cancel();

            if ($booking->session->refundsIfCancelledNow() && $booking->membership) {
                $this->refund->execute($booking->membership, $booking);
            }

            return $booking;
        });
    }
}
