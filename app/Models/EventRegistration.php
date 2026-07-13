<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's registration for an event. */
#[Fillable([
    'event_id',
    'student_id',
    'status',
    'registered_at',
    'cancelled_at',
])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EventRegistrationStatus::class,
            'registered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** Register a student for an event (seat taken). */
    public static function place(Event $event, Student $student): self
    {
        return $event->registrations()->create([
            'student_id' => $student->getKey(),
            'status' => EventRegistrationStatus::Registered,
            'registered_at' => now(),
        ]);
    }

    /** Record attendance (or a no-show) for this registration. */
    public function markAttendance(bool $attended): self
    {
        $this->update([
            'status' => $attended ? EventRegistrationStatus::Attended : EventRegistrationStatus::NoShow,
        ]);

        return $this;
    }

    /** Cancel this registration and free the seat. */
    public function cancel(): self
    {
        $this->update([
            'status' => EventRegistrationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);

        return $this;
    }
}
