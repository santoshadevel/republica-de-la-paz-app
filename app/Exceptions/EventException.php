<?php

namespace App\Exceptions;

use RuntimeException;

/** An event registration could not be made/cancelled; message is user-facing (ES). */
class EventException extends RuntimeException
{
    public static function notOpen(): self
    {
        return new self('El evento no está abierto para inscripciones.');
    }

    public static function alreadyStarted(): self
    {
        return new self('El evento ya comenzó o pasó.');
    }

    public static function alreadyRegistered(): self
    {
        return new self('El alumno ya está inscripto en este evento.');
    }

    public static function full(): self
    {
        return new self('El evento está completo.');
    }

    public static function notCancellable(): self
    {
        return new self('Solo se pueden cancelar inscripciones activas.');
    }
}
