<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Whether a movement brings money in (income) or out (expense). */
enum TransactionType: string implements HasColor, HasLabel
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Ingreso',
            self::Expense => 'Egreso',
        };
    }

    /** Filament HasLabel contract. */
    public function getLabel(): string
    {
        return $this->label();
    }

    /** Filament HasColor contract (badges). */
    public function getColor(): string
    {
        return match ($this) {
            self::Income => 'success',
            self::Expense => 'danger',
        };
    }

    /** Signed multiplier for balance math (+1 income, -1 expense). */
    public function sign(): int
    {
        return $this === self::Income ? 1 : -1;
    }

    /** @return array<string, string> value => label, for Filament selects/filters. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
