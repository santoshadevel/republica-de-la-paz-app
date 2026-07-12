<?php

namespace App\Actions\Memberships;

use App\Enums\CreditMovementType;
use App\Models\Booking;
use App\Models\CreditMovement;
use App\Models\StudentMembership;

/**
 * Refunds one practice credit to a membership when a booking is cancelled within
 * the allowed window. Unlimited memberships have no balance to refund.
 */
class RefundMembershipCredit
{
    public function execute(StudentMembership $membership, ?Booking $booking = null): ?CreditMovement
    {
        if ($membership->is_unlimited) {
            return null;
        }

        return $membership->movements()->create([
            'type' => CreditMovementType::Refund,
            'amount' => 1,
            'reason' => 'Cancelación en plazo',
            'booking_id' => $booking?->getKey(),
        ]);
    }
}
