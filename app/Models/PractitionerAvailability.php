<?php

namespace App\Models;

use App\Enums\Weekday;
use Database\Factories\PractitionerAvailabilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A recurring weekly availability block for a practitioner. */
#[Fillable([
    'practitioner_id',
    'day_of_week',
    'start_time',
    'end_time',
])]
class PractitionerAvailability extends Model
{
    /** @use HasFactory<PractitionerAvailabilityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'day_of_week' => Weekday::class,
        ];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** The block as an "HH:MM-HH:MM" range (spatie/opening-hours format). */
    public function range(): string
    {
        return substr((string) $this->start_time, 0, 5).'-'.substr((string) $this->end_time, 0, 5);
    }
}
