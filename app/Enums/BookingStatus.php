<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Status of a student's booking for a group session. */
enum BookingStatus: string implements HasLabel
{
    case Booked = 'booked';       // reserved, credit consumed
    case Cancelled = 'cancelled'; // cancelled (credit refunded if within window)
    case Attended = 'attended';   // student showed up
    case NoShow = 'no_show';      // student did not show up

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Reservada',
            self::Cancelled => 'Cancelada',
            self::Attended => 'Asistió',
            self::NoShow => 'No asistió',
        };
    }

    /** Filament HasLabel contract. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** Whether this status still occupies a seat / counts as an active booking. */
    public function isActive(): bool
    {
        return $this === self::Booked || $this === self::Attended;
    }

    /** @return array<string, string> value => label, for Filament selects/filters. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
