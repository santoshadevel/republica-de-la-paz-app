<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\ScheduledSession;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'scheduled_session_id' => ScheduledSession::factory(),
            'student_membership_id' => null,
            'status' => BookingStatus::Booked,
            'booked_at' => now(),
        ];
    }
}
