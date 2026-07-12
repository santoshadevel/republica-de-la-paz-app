<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Whether a room is a physical space or a virtual (online) one. */
enum RoomType: string implements HasLabel
{
    case Physical = 'physical';
    case Virtual = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'Física',
            self::Virtual => 'Virtual',
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
