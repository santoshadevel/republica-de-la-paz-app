<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\AppointmentStatus;
use App\Support\Money;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An individual session (acompañamiento). Managed by admin as agenda slots;
 * a student can be booked into an available slot. Paid per session — does not
 * consume membership credits. See docs/REQUISITOS.md (2.5/2.6).
 */
#[Fillable([
    'practitioner_id',
    'activity_id',
    'student_id',
    'room_id',
    'starts_at',
    'ends_at',
    'status',
    'price',
    'cancellation_fee',
    'notes',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
            'price' => MoneyCast::class,
            'cancellation_fee' => MoneyCast::class,
        ];
    }

    public function practitioner(): BelongsTo
    {
        return $this->belongsTo(Practitioner::class);
    }

    /** The specialty/service for this session. */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /** Whether this slot can still be booked by a student. */
    public function isBookable(): bool
    {
        return $this->status === AppointmentStatus::Available
            && $this->starts_at->isFuture();
    }

    /** Book this slot for a student. */
    public function assignTo(Student $student): self
    {
        $this->update([
            'student_id' => $student->getKey(),
            'status' => AppointmentStatus::Booked,
        ]);

        return $this;
    }

    /** Cancel this appointment, storing any late-cancellation fee owed. */
    public function markCancelled(?Money $fee = null): self
    {
        $this->update([
            'status' => AppointmentStatus::Cancelled,
            'cancellation_fee' => $fee,
        ]);

        return $this;
    }

    /** Mark a booked appointment as completed. */
    public function markCompleted(): self
    {
        $this->update(['status' => AppointmentStatus::Completed]);

        return $this;
    }
}
