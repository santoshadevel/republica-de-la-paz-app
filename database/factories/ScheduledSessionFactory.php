<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Enums\SessionStatus;
use App\Models\Activity;
use App\Models\Practitioner;
use App\Models\Room;
use App\Models\ScheduledSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledSession>
 */
class ScheduledSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addDay()->setTime(10, 0);

        return [
            'activity_id' => Activity::factory()->state(['type' => ActivityType::GroupClass]),
            'practitioner_id' => Practitioner::factory(),
            'room_id' => Room::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'capacity' => 10,
            'status' => SessionStatus::Scheduled,
        ];
    }

    /** A session starting soon (within the cancellation window). */
    public function startingSoon(): static
    {
        $startsAt = now()->addMinutes(30);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
        ]);
    }

    /** A session with a single seat. */
    public function withCapacity(int $capacity): static
    {
        return $this->state(fn () => ['capacity' => $capacity]);
    }
}
