<?php

namespace Database\Factories;

use App\Enums\MembershipOrderStatus;
use App\Models\MembershipOrder;
use App\Models\MembershipPlan;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MembershipOrder>
 */
class MembershipOrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'membership_plan_id' => MembershipPlan::factory(),
            'status' => MembershipOrderStatus::Pending,
            'price' => 350000,
        ];
    }
}
