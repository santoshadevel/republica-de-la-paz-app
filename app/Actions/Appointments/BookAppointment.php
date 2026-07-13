<?php

namespace App\Actions\Appointments;

use App\Exceptions\AppointmentException;
use App\Models\Appointment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

/**
 * Books a student into an available appointment slot. Individual sessions are
 * paid per session, so no membership credit is consumed.
 */
class BookAppointment
{
    public function execute(Student $student, Appointment $appointment): Appointment
    {
        return DB::transaction(function () use ($student, $appointment) {
            $locked = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $locked->isBookable()) {
                throw AppointmentException::notBookable();
            }

            return $locked->assignTo($student);
        });
    }
}
