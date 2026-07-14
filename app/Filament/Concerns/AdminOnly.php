<?php

namespace App\Filament\Concerns;

/**
 * Restricts a Filament Resource or Page to admins only. Non-admin staff
 * (practitioner, receptionist) neither see it in the navigation nor can reach
 * its routes. Accounting/configuration modules use this.
 */
trait AdminOnly
{
    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
