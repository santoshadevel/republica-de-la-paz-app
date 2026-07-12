<?php

namespace Database\Seeders;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Database\Seeder;

/**
 * Initial rooms for this brand. The PDF mentions two rooms running in parallel
 * plus consulting rooms for individual sessions; a virtual room is included to
 * show the physical/virtual distinction. Idempotent by name.
 */
class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Sala Principal',
                'type' => RoomType::Physical->value,
                'capacity' => 20,
                'color' => '#8FBC8F',
                'description' => 'Sala grande para prácticas grupales.',
            ],
            [
                'name' => 'Sala Secundaria',
                'type' => RoomType::Physical->value,
                'capacity' => 15,
                'color' => '#D2B48C',
                'description' => 'Segunda sala para prácticas en simultáneo.',
            ],
            [
                'name' => 'Consultorio',
                'type' => RoomType::Physical->value,
                'capacity' => 1,
                'color' => '#B0C4DE',
                'description' => 'Espacio para acompañamientos individuales.',
            ],
            [
                'name' => 'Sala Virtual',
                'type' => RoomType::Virtual->value,
                'capacity' => null,
                'meeting_url' => 'https://meet.example.com/santosha',
                'color' => '#C8A2C8',
                'description' => 'Clases y sesiones online.',
            ],
        ];

        foreach ($rooms as $room) {
            Room::updateOrCreate(['name' => $room['name']], $room);
        }
    }
}
