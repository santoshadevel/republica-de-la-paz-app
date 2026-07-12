<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => TransactionType::Income,
            'amount' => fake()->numberBetween(50_000, 500_000),
            'occurred_on' => now()->toDateString(),
            'description' => fake()->optional()->sentence(),
            'invoice_issued' => false,
        ];
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => TransactionType::Expense]);
    }
}
