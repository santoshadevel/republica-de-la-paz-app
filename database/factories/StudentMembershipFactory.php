<?php

namespace Database\Factories;

use App\Enums\MembershipStatus;
use App\Models\Student;
use App\Models\StudentMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentMembership>
 */
class StudentMembershipFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = now()->startOfDay();

        return [
            'student_id' => Student::factory(),
            'membership_plan_id' => null,
            'credits_total' => 12,
            'is_unlimited' => false,
            'price_paid' => 400000,
            'currency_code' => 'PYG',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(30),
            'status' => MembershipStatus::Active,
        ];
    }

    /** Unlimited membership (no credit cap). */
    public function unlimited(): static
    {
        return $this->state(fn () => [
            'credits_total' => null,
            'is_unlimited' => true,
            'price_paid' => 480000,
        ]);
    }

    /** Already past its validity window. */
    public function expired(): static
    {
        $startsAt = now()->startOfDay()->subDays(40);

        return $this->state(fn () => [
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays(30),
        ]);
    }
}
