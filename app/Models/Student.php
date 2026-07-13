<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A client/member of the center. The record is unique by email; identity_number
 * is an optional generic document (white-label friendly).
 */
#[Fillable([
    'user_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'identity_number',
    'tax_id',
    'birth_date',
    'acquisition_source',
    'goals',
    'notes',
    'is_active',
])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** The optional login account for the student portal. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Full display name. */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Link a portal login to the matching ficha (by email), creating it if the
     * student had never been registered by staff. Returns the ficha.
     */
    public static function registerFrom(User $user, string $fullName): self
    {
        [$first, $last] = self::splitName($fullName);

        $student = static::firstOrNew(['email' => $user->email]);
        $student->fill([
            'user_id' => $user->getKey(),
            'first_name' => $student->first_name ?: $first,
            'last_name' => $student->last_name ?: $last,
            'is_active' => true,
        ]);
        $student->save();

        return $student;
    }

    /** @return array{0: string, 1: string} first name, rest as last name. */
    private static function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2) ?: [];

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }

    /** Every membership/pass this student has bought (newest first). */
    public function memberships(): HasMany
    {
        return $this->hasMany(StudentMembership::class)->latest('starts_at');
    }

    /** Group-session reservations made by this student. */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Individual sessions (acompañamientos) booked for this student. */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** Event registrations for this student. */
    public function eventRegistrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /** The student's current membership: active, in-window, latest to expire. */
    public function currentMembership(): ?StudentMembership
    {
        return $this->memberships()
            ->active()
            ->orderByDesc('ends_at')
            ->first();
    }

    /** Open a new membership for this student from the given snapshot attributes. */
    public function openMembership(array $attributes): StudentMembership
    {
        return $this->memberships()->create($attributes);
    }
}
