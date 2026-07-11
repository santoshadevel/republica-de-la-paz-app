<?php

namespace App\Enums;

/**
 * Application roles. The backing value is the role name stored by
 * spatie/laravel-permission. Labels are shown in the (Spanish) UI.
 */
enum Role: string
{
    case Student = 'student';
    case Practitioner = 'practitioner';
    case Receptionist = 'receptionist';
    case Admin = 'admin';

    /** Roles whose members may access the Filament admin panel. */
    public const STAFF = [self::Practitioner, self::Receptionist, self::Admin];

    /** Human-readable label for the UI (Spanish). */
    public function label(): string
    {
        return match ($this) {
            self::Student => 'Alumno',
            self::Practitioner => 'Profesional',
            self::Receptionist => 'Recepción',
            self::Admin => 'Administración',
        };
    }

    /** All role backing values. */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
