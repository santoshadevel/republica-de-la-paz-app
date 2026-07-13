<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** ISO-8601 day of the week (1 = Monday .. 7 = Sunday). */
enum Weekday: int implements HasLabel
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function label(): string
    {
        return match ($this) {
            self::Monday => 'Lunes',
            self::Tuesday => 'Martes',
            self::Wednesday => 'Miércoles',
            self::Thursday => 'Jueves',
            self::Friday => 'Viernes',
            self::Saturday => 'Sábado',
            self::Sunday => 'Domingo',
        };
    }

    /** Lowercase English day name used by spatie/opening-hours. */
    public function spatieKey(): string
    {
        return strtolower($this->name);
    }

    /** Filament HasLabel contract. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** @return array<int, string> value => label, for Filament selects. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $day) => [$day->value => $day->label()])
            ->all();
    }
}
