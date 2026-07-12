<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Kind of money account: a cash box or a bank account. */
enum AccountType: string implements HasLabel
{
    case Cash = 'cash';
    case Bank = 'bank';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo / Caja',
            self::Bank => 'Cuenta bancaria',
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
