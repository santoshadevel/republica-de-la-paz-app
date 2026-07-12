<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\EventRegistrationStatus;
use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A one-off event (workshop, talk, retreat, circle, training). */
#[Fillable([
    'name',
    'description',
    'image',
    'location',
    'starts_at',
    'ends_at',
    'price',
    'capacity',
    'status',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'price' => MoneyCast::class,
            'capacity' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    /** Facilitators leading this event. */
    public function facilitators(): BelongsToMany
    {
        return $this->belongsToMany(Practitioner::class)->withTimestamps();
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /** Registrations that still occupy a seat. */
    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->whereIn('status', [
            EventRegistrationStatus::Registered->value,
            EventRegistrationStatus::Attended->value,
        ]);
    }

    public function seatsTaken(): int
    {
        return $this->activeRegistrations()->count();
    }

    /** Seats left, or null when capacity is unlimited. */
    public function seatsAvailable(): ?int
    {
        if ($this->capacity === null) {
            return null;
        }

        return max(0, $this->capacity - $this->seatsTaken());
    }

    public function isFull(): bool
    {
        return $this->seatsAvailable() === 0;
    }
}
