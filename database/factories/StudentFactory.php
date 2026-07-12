<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'identity_number' => fake()->optional()->numerify('#######'),
            'tax_id' => fake()->optional()->numerify('########-#'),
            'birth_date' => fake()->optional()->dateTimeBetween('-70 years', '-16 years')?->format('Y-m-d'),
            'acquisition_source' => fake()->optional()->randomElement(['instagram', 'facebook', 'google', 'referral', 'event', 'walk_in', 'other']),
            'goals' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
