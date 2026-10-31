<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Services\HousekeepingService;
use Illuminate\Database\Seeder;

/**
 * OPTIONAL demo data: marks up to 3 available rooms as "needs cleaning" so the Housekeeping page has something to show.
 *   php artisan db:seed --class=HousekeepingDemoSeeder
 */
class HousekeepingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(HousekeepingService::class);

        Room::where('status', 'available')->orderBy('floor')->orderBy('room_number')->take(3)->get()
            ->each(fn (Room $room) => $service->apply($room, 'needs_cleaning', null, note: 'Demo data'));
    }
}
