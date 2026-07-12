<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Kind of entry in a membership's credit ledger. The signed `amount` on each
 * movement is what actually changes the balance; the type explains why.
 */
enum CreditMovementType: string implements HasLabel
{
    case Sale = 'sale';                 // credits granted when the pass is sold
    case Consumption = 'consumption';   // a booking consumed one credit
    case Refund = 'refund';             // an in-window cancellation gave one back
    case ManualAdjust = 'manual_adjust'; // admin added/subtracted credits by hand
    case Expiration = 'expiration';     // credits voided when the pass expired

    public function label(): string
    {
        return match ($this) {
            self::Sale => 'Venta',
            self::Consumption => 'Consumo',
            self::Refund => 'Reintegro',
            self::ManualAdjust => 'Ajuste manual',
            self::Expiration => 'Expiración',
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
