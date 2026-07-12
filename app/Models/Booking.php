<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A student's reservation for a group session. Consuming/refunding credits is
 * recorded in the membership ledger (credit_movements.booking_id).
 */
#[Fillable([
    'student_id',
    'scheduled_session_id',
    'student_membership_id',
    'status',
    'booked_at',
    'cancelled_at',
])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'booked_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ScheduledSession::class, 'scheduled_session_id');
    }

    /** The membership that paid for this booking (null for unlimited/manual). */
    public function membership(): BelongsTo
    {
        return $this->belongsTo(StudentMembership::class, 'student_membership_id');
    }

    /** Credit ledger entries tied to this booking (consumption/refund). */
    public function creditMovements(): HasMany
    {
        return $this->hasMany(CreditMovement::class, 'booking_id');
    }
}
