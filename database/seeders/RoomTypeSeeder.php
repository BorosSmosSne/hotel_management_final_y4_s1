<?php

namespace Database\Seeders;

use App\Models\RoomType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['type_name' => 'Single', 'price_per_night' => 50, 'capacity' => 1, 'description' => 'Cozy room for one person'],
            ['type_name' => 'Double', 'price_per_night' => 80, 'capacity' => 2, 'description' => 'Comfortable Room for two'],
            ['type_name' => 'Deluxe', 'price_per_night' => 120, 'capacity' => 3, 'description' => 'Spacious deluxe room'],
            ['type_name' => 'Suite',  'price_per_night' => 150, 'capacity' => 4, 'description' => 'Luxury suite with living area'],
        ];

        foreach ($types as $type) {
            RoomType::create($type);
        }
    }
}
