<?php

namespace App\Models;

use Database\Factories\PractitionerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\OpeningHours\OpeningHours;

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

    /** Events this practitioner facilitates. */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    /** Compensation rules (honorarios) for this practitioner. */
    public function feeSchemes(): HasMany
    {
        return $this->hasMany(FeeScheme::class);
    }

    /** Recurring weekly availability blocks. */
    public function availabilities(): HasMany
    {
        return $this->hasMany(PractitionerAvailability::class);
    }

    /** Date-specific availability overrides (closed days / special hours). */
    public function availabilityExceptions(): HasMany
    {
        return $this->hasMany(PractitionerAvailabilityException::class);
    }

    /** Full display name. */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** Whether this practitioner has any availability configured at all. */
    public function hasAvailabilitySchedule(): bool
    {
        return $this->availabilities()->exists() || $this->availabilityExceptions()->exists();
    }

    /** Build the spatie/opening-hours object from the stored blocks and exceptions. */
    public function openingHours(): OpeningHours
    {
        $schedule = [];

        foreach ($this->availabilities as $block) {
            $schedule[$block->day_of_week->spatieKey()][] = $block->range();
        }

        $exceptions = [];
        foreach ($this->availabilityExceptions as $exception) {
            $key = $exception->date->format('Y-m-d');

            if ($exception->range() === null) {
                $exceptions[$key] = []; // closed that day
            } else {
                $exceptions[$key][] = $exception->range();
            }
        }

        if ($exceptions !== []) {
            $schedule['exceptions'] = $exceptions;
        }

        return OpeningHours::create($schedule);
    }

    /**
     * Whether this practitioner is available for the whole [start, end] range.
     * A practitioner with no schedule configured is treated as unconstrained.
     */
    public function isAvailableAt(Carbon $start, Carbon $end): bool
    {
        if (! $this->hasAvailabilitySchedule()) {
            return true;
        }

        $hours = $this->openingHours();

        if (! $hours->isOpenAt($start)) {
            return false;
        }

        $close = $hours->nextClose($start);

        return $close !== false && $end <= $close;
    }
}
