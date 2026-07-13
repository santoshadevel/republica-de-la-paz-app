<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine whether the user may access the given Filament panel.
     * Only staff roles enter the admin panel; students use the public site.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(array_map(fn (Role $role) => $role->value, Role::STAFF));
    }

    /** The student ficha for this account, if this user is a student. */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    /** Create a portal login account with the `student` role. */
    public static function registerStudent(string $name, string $email, string $password): self
    {
        $user = static::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // hashed by the cast
        ]);
        $user->assignRole(Role::Student->value);

        return $user;
    }

    /** Whether this account is a portal student. */
    public function isStudent(): bool
    {
        return $this->hasRole(Role::Student->value);
    }
}
