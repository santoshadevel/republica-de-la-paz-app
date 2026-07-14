<?php

namespace App\Filament\Concerns;

/**
 * Restricts a Filament widget to admins only. Financial widgets (income,
 * expense, margin) are hidden from other staff on the dashboard.
 */
trait AdminOnlyWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
