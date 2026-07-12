<?php

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use App\Support\Money;

/**
 * Cancels an appointment. If a booked session is cancelled with less than the
 * configured notice, a fee (default 50% of the price) is charged. See
 * config/booking.php. The fee's actual accounting entry lands in Fase 7.
 */
class CancelAppointment
{
    public function execute(Appointment $appointment): Appointment
    {
        if (! in_array($appointment->status, [AppointmentStatus::Available, AppointmentStatus::Booked], true)) {
            throw AppointmentException::notCancellable();
        }

        $fee = $this->lateCancellationFee($appointment);

        $appointment->update([
            'status' => AppointmentStatus::Cancelled,
            'cancellation_fee' => $fee,
        ]);

        return $appointment;
    }

    /** The fee owed for a late cancellation, or null when none applies. */
    private function lateCancellationFee(Appointment $appointment): ?Money
    {
        if ($appointment->status !== AppointmentStatus::Booked || $appointment->price === null) {
            return null;
        }

        $hours = (int) config('booking.individual_cancellation_hours', 24);
        $deadline = $appointment->starts_at->copy()->subHours($hours);

        // On time (before the deadline) → no fee.
        if (now()->lessThanOrEqualTo($deadline)) {
            return null;
        }

        $percent = (int) config('booking.individual_late_fee_percent', 50);
        $minor = (int) round($appointment->price->minorAmount * $percent / 100);

        return Money::ofMinor($minor, $appointment->price->currency);
    }
}
