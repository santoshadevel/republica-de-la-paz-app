<?php

namespace App\Exceptions;

use RuntimeException;

/** An appointment could not be booked/cancelled; message is user-facing (ES). */
class AppointmentException extends RuntimeException
{
    public static function notBookable(): self
    {
        return new self('El horario ya no está disponible.');
    }

    public static function notCancellable(): self
    {
        return new self('Este turno no se puede cancelar.');
    }

    public static function notCompletable(): self
    {
        return new self('Solo se pueden completar turnos reservados.');
    }
}
