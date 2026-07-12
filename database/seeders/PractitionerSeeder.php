<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Practitioner;
use Illuminate\Database\Seeder;

/**
 * A few sample "referentes" (practitioners) with their specialties attached, so
 * the activity↔practitioner relationship is visible out of the box. Names are
 * fictitious; specialties map to activities seeded by ActivitySeeder (run first).
 * Idempotent by email.
 */
class PractitionerSeeder extends Seeder
{
    public function run(): void
    {
        $referentes = [
            [
                'first_name' => 'Valentina',
                'last_name' => 'Ríos',
                'email' => 'valentina.rios@example.test',
                'bio' => 'Facilitadora de yoga y prácticas de presencia.',
                'specialties' => ['Hatha Vinyasa', 'Meditación', 'Respiración Consciente', 'Tarot'],
            ],
            [
                'first_name' => 'Camila',
                'last_name' => 'Torres',
                'email' => 'camila.torres@example.test',
                'bio' => 'Terapeuta de sonido y energía.',
                'specialties' => ['Vinyasa', 'Sound Healing', 'Reiki', 'KAP'],
            ],
            [
                'first_name' => 'Mateo',
                'last_name' => 'Giménez',
                'email' => 'mateo.gimenez@example.test',
                'bio' => 'Yoga, meditación y lectura de Diseño Humano.',
                'specialties' => ['Hatha', 'Diseño Humano', 'Meditación'],
            ],
        ];

        foreach ($referentes as $data) {
            $specialties = $data['specialties'];
            unset($data['specialties']);

            $practitioner = Practitioner::updateOrCreate(['email' => $data['email']], $data);

            $activityIds = Activity::whereIn('name', $specialties)->pluck('id');
            $practitioner->activities()->sync($activityIds);
        }
    }
}
