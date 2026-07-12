<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Status of a student's registration for an event. */
enum EventRegistrationStatus: string implements HasLabel
{
    case Registered = 'registered';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Registered => 'Inscripto',
            self::Cancelled => 'Cancelado',
            self::Attended => 'Asistió',
            self::NoShow => 'No asistió',
        };
    }

    /** Filament HasLabel contract. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** Whether this registration still occupies a seat. */
    public function isActive(): bool
    {
        return $this === self::Registered || $this === self::Attended;
    }

    /** @return array<string, string> value => label, for Filament selects/filters. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
