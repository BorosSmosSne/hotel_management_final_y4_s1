<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data fix, no schema change. From now on check-in sets rooms.status = 'occupied'.
     * Guests who were ALREADY checked in before that change still have their room on 'available',
     * so the Dashboard and Rooms list showed 0 occupied. Mark those rooms occupied once.
     */
    public function up(): void
    {
        $roomIds = DB::table('bookings')->where('status', 'checked_in')->pluck('room_id');

        if ($roomIds->isNotEmpty()) {
            DB::table('rooms')->whereIn('id', $roomIds)->where('status', 'available')->update(['status' => 'occupied']);
        }
    }

    public function down(): void
    {
        // Nothing to undo: a checked-in guest's room is legitimately occupied.
    }
};
