<?php

namespace App\Models;

use Database\Factories\PractitionerAvailabilityExceptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A date-specific override of a practitioner's weekly availability: a closed day
 * (is_available = false) or special hours (is_available = true with a block).
 */
#[Fillable([
    'practitioner_id',
    'date',
    'is_available',
    'start_time',
    'end_time',
    'reason',
])]
class PractitionerAvailabilityException extends Model
{
    /** @use HasFactory<PractitionerAvailabilityExceptionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** The special hours as an "HH:MM-HH:MM" range, or null when closed. */
    public function range(): ?string
    {
        if (! $this->is_available || $this->start_time === null || $this->end_time === null) {
            return null;
        }

        return substr((string) $this->start_time, 0, 5).'-'.substr((string) $this->end_time, 0, 5);
    }
}
