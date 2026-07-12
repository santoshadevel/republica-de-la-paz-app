<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Enums\AppointmentStatus;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\Practitioner;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDays(2)->setTime(15, 0);

        return [
            'practitioner_id' => Practitioner::factory(),
            'activity_id' => Activity::factory()->state(['type' => ActivityType::IndividualSession]),
            'student_id' => null,
            'room_id' => null,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'status' => AppointmentStatus::Available,
            'price' => 150000,
        ];
    }

    /** A slot already booked by a student. */
    public function booked(): static
    {
        return $this->state(fn () => [
            'student_id' => Student::factory(),
            'status' => AppointmentStatus::Booked,
        ]);
    }

    /** A slot starting soon (inside the 24h cancellation window). */
    public function startingSoon(): static
    {
        $startsAt = now()->addHours(2);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
        ]);
    }
}
