<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** How a practitioner is paid for a service: a fixed amount or a percentage. */
enum FeeType: string implements HasLabel
{
    case FixedPerSession = 'fixed';
    case Percentage = 'percentage';

    public function label(): string
    {
        return match ($this) {
            self::FixedPerSession => 'Monto fijo por sesión',
            self::Percentage => 'Porcentaje del ingreso',
        };
    }

    /** Filament HasLabel contract. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** @return array<string, string> value => label, for Filament selects. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
