<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = RoomType::all();
        $statuses = ['available', 'occupied', 'maintenance'];

        for($i = 1; $i <= 10; $i++){
            Room::create([
                'room_number' => '10' .$i,
                'floor' => rand(1,5),
                'status' => $statuses[array_rand($statuses)],
                'description' => 'Standard room description' .$i,
                'room_type_id' => $types->random()->id,
            ]);
        }
    }
}
