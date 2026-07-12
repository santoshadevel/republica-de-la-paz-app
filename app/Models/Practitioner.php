<?php

namespace App\Models;

use Database\Factories\PractitionerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A teacher/therapist who leads activities. May be linked to a login User. */
#[Fillable([
    'user_id',
    'first_name',
    'last_name',
    'email',
    'phone',
    'identity_number',
    'bio',
    'is_active',
])]
class Practitioner extends Model
{
    /** @use HasFactory<PractitionerFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** The optional login account for this practitioner. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Activities this practitioner leads (their specialties). */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withTimestamps();
    }

    /** Full display name. */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
