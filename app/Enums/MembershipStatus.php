<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Lifecycle status of a sold membership (StudentMembership). */
enum MembershipStatus: string implements HasLabel
{
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Expired => 'Vencida',
            self::Cancelled => 'Cancelada',
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
