<?php

namespace App\Actions\Appointments;

use App\Enums\AppointmentStatus;
use App\Exceptions\AppointmentException;
use App\Models\Appointment;

/** Marks a booked appointment as completed (attendance for individual sessions). */
class CompleteAppointment
{
    public function execute(Appointment $appointment): Appointment
    {
        if ($appointment->status !== AppointmentStatus::Booked) {
            throw AppointmentException::notCompletable();
        }

        $appointment->update(['status' => AppointmentStatus::Completed]);

        return $appointment;
    }
}
