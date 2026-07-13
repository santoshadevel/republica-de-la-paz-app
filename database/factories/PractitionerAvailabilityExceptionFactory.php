<?php

namespace Database\Factories;

use App\Models\Practitioner;
use App\Models\PractitionerAvailabilityException;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PractitionerAvailabilityException>
 */
class PractitionerAvailabilityExceptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'practitioner_id' => Practitioner::factory(),
            'date' => now()->addWeek()->toDateString(),
            'is_available' => false,
            'start_time' => null,
            'end_time' => null,
            'reason' => 'No disponible',
        ];
    }

    /** A special-hours exception (open with a custom block). */
    public function specialHours(string $start = '10:00', string $end = '14:00'): static
    {
        return $this->state(fn () => [
            'is_available' => true,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }
}
