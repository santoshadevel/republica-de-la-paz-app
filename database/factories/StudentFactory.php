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
            'birth_date' => fake()->optional()->dateTimeBetween('-70 years', '-16 years')?->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
