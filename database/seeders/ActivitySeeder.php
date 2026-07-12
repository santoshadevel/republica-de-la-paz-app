<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Models\Activity;
use Illuminate\Database\Seeder;

/**
 * Initial activity catalog for this brand (Santosha), taken from the PDF:
 * weekly group classes, individual sessions/therapies and event types.
 * Idempotent by (name, type). White-label installs replace this catalog.
 */
class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $groupClasses = [
            ['Hatha', 60],
            ['Vinyasa', 60],
            ['Hatha Vinyasa', 75],
            ['Yogafitness', 60],
            ['Aero', 60],
            ['Slow / Yin / Restaurativo', 75],
            ['Meditación', 45],
            ['Respiración Consciente', 45],
        ];

        $individualSessions = [
            ['Reiki', 60],
            ['Sound Healing', 60],
            ['KAP', 60],
            ['Medicina Ayurvédica', 60],
            ['Masaje Ayurvédico', 60],
            ['Fisioterapia', 45],
            ['Psicología', 50],
            ['Tarot', 60],
            ['Diseño Humano', 60],
            ['Yoga Terapéutico', 60],
        ];

        // Event "kinds" from the Asamblea section (used as templates for events).
        $events = [
            ['Workshop', null],
            ['Charla', null],
            ['Retiro', null],
            ['Círculo', null],
            ['Formación', null],
        ];

        $this->seedGroup($groupClasses, ActivityType::GroupClass);
        $this->seedGroup($individualSessions, ActivityType::IndividualSession);
        $this->seedGroup($events, ActivityType::Event);
    }

    /**
     * @param  array<int, array{0: string, 1: int|null}>  $items
     */
    private function seedGroup(array $items, ActivityType $type): void
    {
        foreach ($items as [$name, $duration]) {
            Activity::updateOrCreate(
                ['name' => $name, 'type' => $type->value],
                ['default_duration_minutes' => $duration, 'is_active' => true],
            );
        }
    }
}
