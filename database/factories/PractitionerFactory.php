<?php

namespace Database\Factories;

use App\Models\Practitioner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Practitioner>
 */
class PractitionerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'identity_number' => fake()->optional()->numerify('#######'),
            'bio' => fake()->optional()->paragraph(),
            'is_active' => true,
        ];
    }
}
