<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Status of an individual appointment slot. A slot starts as "available" (agenda
 * opened by admin), gets "booked" by a student, then "completed"; it can also be
 * "cancelled" or "blocked" (admin reserves the time without a booking).
 */
enum AppointmentStatus: string implements HasLabel
{
    case Available = 'available';
    case Booked = 'booked';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Disponible',
            self::Booked => 'Reservado',
            self::Completed => 'Realizado',
            self::Cancelled => 'Cancelado',
            self::Blocked => 'Bloqueado',
        };
    }

    /** Filament HasLabel contract. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** @return array<string, string> value => label, for Filament selects/filters. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
