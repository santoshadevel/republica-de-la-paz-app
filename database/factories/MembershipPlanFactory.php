<?php

namespace Database\Factories;

use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipPlan>
 */
class MembershipPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucfirst($name),
            'slug' => str($name)->slug(),
            'description' => fake()->optional()->sentence(),
            'price' => fake()->numberBetween(0, 500000), // minor units (Gs)
            'rules' => [
                'credits' => fake()->numberBetween(1, 12),
                'unlimited' => false,
                'validity_days' => fake()->randomElement([7, 30, 60]),
            ],
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
