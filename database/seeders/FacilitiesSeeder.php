<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Room;
use Illuminate\Database\Seeder;

/**
 * Seed the default school facilities.
 */
class FacilitiesSeeder extends Seeder
{
    /**
     * The default buildings and their rooms.
     *
     * @var list<array{name: string, code: string, rooms: list<array{name: string, room_type: string, capacity: int}>}>
     */
    protected array $buildings = [
        [
            'name' => 'Main Building',
            'code' => 'BLD-001',
            'rooms' => [
                ['name' => 'Room 101', 'room_type' => 'classroom', 'capacity' => 45],
                ['name' => 'Room 102', 'room_type' => 'classroom', 'capacity' => 45],
                ['name' => 'Room 103', 'room_type' => 'classroom', 'capacity' => 40],
            ],
        ],
        [
            'name' => 'Science Building',
            'code' => 'BLD-002',
            'rooms' => [
                ['name' => 'Physics Laboratory', 'room_type' => 'laboratory', 'capacity' => 40],
                ['name' => 'Chemistry Laboratory', 'room_type' => 'laboratory', 'capacity' => 40],
                ['name' => 'Biology Laboratory', 'room_type' => 'laboratory', 'capacity' => 40],
            ],
        ],
        [
            'name' => 'Senior High School Building',
            'code' => 'BLD-003',
            'rooms' => [
                ['name' => 'Room 201', 'room_type' => 'classroom', 'capacity' => 45],
                ['name' => 'Room 202', 'room_type' => 'classroom', 'capacity' => 45],
            ],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->buildings as $building) {
            $buildingModel = Building::query()->firstOrCreate(
                ['name' => $building['name']],
                [
                    'code' => $building['code'],
                    'description' => null,
                    'is_active' => true,
                ]
            );

            foreach ($building['rooms'] as $room) {
                Room::query()->firstOrCreate(
                    ['name' => $room['name']],
                    [
                        'building_id' => $buildingModel->id,
                        'room_type' => $room['room_type'],
                        'capacity' => $room['capacity'],
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
