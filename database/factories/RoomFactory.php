<?php

namespace Database\Factories;

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
            'capacity' => fake()->numberBetween(6, 30),
            'color' => fake()->hexColor(),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
