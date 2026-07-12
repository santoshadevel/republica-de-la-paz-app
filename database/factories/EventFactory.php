<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->addWeek()->setTime(18, 0);

        return [
            'name' => 'Taller de '.fake()->words(2, true),
            'description' => fake()->optional()->paragraph(),
            'location' => 'Sala Principal',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHours(2),
            'price' => 100000,
            'capacity' => 20,
            'status' => EventStatus::Scheduled,
        ];
    }

    public function withCapacity(int $capacity): static
    {
        return $this->state(fn () => ['capacity' => $capacity]);
    }

    public function unlimited(): static
    {
        return $this->state(fn () => ['capacity' => null]);
    }
}
