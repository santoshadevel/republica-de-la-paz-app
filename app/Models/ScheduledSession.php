<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use Database\Factories\ScheduledSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A dated occurrence of a group activity. Holds the facilitator, room, time and
 * seat capacity for that specific class. See docs/REQUISITOS.md (2.2 design note).
 */
#[Fillable([
    'activity_id',
    'practitioner_id',
    'room_id',
    'starts_at',
    'ends_at',
    'capacity',
    'status',
    'notes',
])]
class ScheduledSession extends Model
{
    /** @use HasFactory<ScheduledSessionFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'capacity' => 'integer',
            'status' => SessionStatus::class,
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /** The facilitator leading this occurrence (may be a substitute). */
    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Bookings that still occupy a seat (reserved or attended). */
    public function activeBookings(): HasMany
    {
        return $this->bookings()->whereIn('status', [
            BookingStatus::Booked->value,
            BookingStatus::Attended->value,
        ]);
    }

    /** Seats currently taken. */
    public function seatsTaken(): int
    {
        return $this->activeBookings()->count();
    }

    /** Seats still available for booking. */
    public function seatsAvailable(): int
    {
        return max(0, $this->capacity - $this->seatsTaken());
    }

    public function isFull(): bool
    {
        return $this->seatsAvailable() <= 0;
    }

    /** Persist a new dated occurrence of a group activity. */
    public static function schedule(array $attributes): self
    {
        return static::create($attributes);
    }
}
