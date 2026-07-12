<?php

namespace App\Exceptions;

use RuntimeException;

/** A booking could not be made or cancelled; message is user-facing (ES). */
class BookingException extends RuntimeException
{
    public static function notBookable(): self
    {
        return new self('La sesión no está disponible para reservar.');
    }

    public static function alreadyStarted(): self
    {
        return new self('La sesión ya comenzó o pasó.');
    }

    public static function alreadyBooked(): self
    {
        return new self('El alumno ya tiene una reserva para esta sesión.');
    }

    public static function noActiveMembership(): self
    {
        return new self('No tienes un pase vigente.');
    }

    public static function activityNotCovered(): self
    {
        return new self('El pase del alumno no cubre esta actividad.');
    }

    public static function noCredit(): self
    {
        return new self('No tienes prácticas disponibles en tu pase.');
    }

    public static function full(): self
    {
        return new self('La sesión está completa.');
    }

    public static function notCancellable(): self
    {
        return new self('Solo se pueden cancelar reservas activas.');
    }
}
