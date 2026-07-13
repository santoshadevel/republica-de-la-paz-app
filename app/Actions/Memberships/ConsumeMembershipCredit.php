<?php

namespace App\Actions\Memberships;

use App\Enums\CreditMovementType;
use App\Models\Booking;
use App\Models\CreditMovement;
use App\Models\StudentMembership;

/**
 * Consumes one practice credit from a membership for a booking. Unlimited
 * memberships record the booking but do not move the balance.
 */
class ConsumeMembershipCredit
{
    public function execute(StudentMembership $membership, ?Booking $booking = null): ?CreditMovement
    {
        if ($membership->is_unlimited) {
            return null;
        }

        return $membership->recordMovement(
            CreditMovementType::Consumption,
            -1,
            'Reserva de práctica grupal',
            $booking,
        );
    }
}
