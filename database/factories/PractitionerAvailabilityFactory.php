<?php

namespace Database\Factories;

use App\Enums\Weekday;
use App\Models\Practitioner;
use App\Models\PractitionerAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PractitionerAvailability>
 */
class PractitionerAvailabilityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practitioner_id' => Practitioner::factory(),
            'day_of_week' => Weekday::Monday,
            'start_time' => '09:00',
            'end_time' => '13:00',
        ];
    }
}
