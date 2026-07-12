<?php

namespace Database\Factories;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Sala '.fake()->unique()->word(),
            'type' => RoomType::Physical,
            'capacity' => fake()->numberBetween(6, 30),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    /** A virtual (online) room with a meeting link. */
    public function virtual(): static
    {
        return $this->state(fn () => [
            'type' => RoomType::Virtual,
            'capacity' => null,
            'meeting_url' => 'https://meet.example.com/'.fake()->unique()->slug(2),
        ]);
    }
}
