<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Lifecycle status of an event (workshop, retreat, talk, etc.). */
enum EventStatus: string implements HasLabel
{
    case Scheduled = 'scheduled';
    case Held = 'held';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Programado',
            self::Held => 'Realizado',
            self::Cancelled => 'Cancelado',
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
