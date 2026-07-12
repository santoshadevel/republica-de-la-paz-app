<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => ActivityType::GroupClass,
            'description' => fake()->optional()->sentence(),
            'default_duration_minutes' => fake()->randomElement([60, 75, 90]),
            'color' => fake()->hexColor(),
            'default_room_id' => null,
            'is_active' => true,
        ];
    }
}
