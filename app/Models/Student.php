<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A client/member of the center. The record is unique by email; identity_number
 * is an optional generic document (white-label friendly).
 */
#[Fillable([
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

    /** Full display name. */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Every membership/pass this student has bought (newest first). */
    public function memberships(): HasMany
    {
        return $this->hasMany(StudentMembership::class)->latest('starts_at');
    }

    /** The student's current membership: active, in-window, latest to expire. */
    public function currentMembership(): ?StudentMembership
    {
        return $this->memberships()
            ->active()
            ->orderByDesc('ends_at')
            ->first();
    }
}
