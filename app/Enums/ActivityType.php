<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Kind of activity in the catalog. Drives how it is scheduled later:
 * group classes (capacity + balance), individual sessions, or events.
 */
enum ActivityType: string implements HasLabel
{
    case GroupClass = 'group_class';
    case IndividualSession = 'individual_session';
    case Event = 'event';

    /** Human-readable label for the UI (Spanish); also used by Filament. */
    public function label(): string
    {
        return match ($this) {
            self::GroupClass => 'Práctica grupal',
            self::IndividualSession => 'Acompañamiento individual',
            self::Event => 'Evento',
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
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
