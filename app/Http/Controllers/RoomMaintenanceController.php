<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Front-desk shortcut on the Room Booking page: put a room under maintenance, or put it back.
 *
 * Admin + Staff (the route sits in the auth + active group). The room row is locked inside a
 * transaction, the same way BookingController::store() does, so a booking and a maintenance
 * switch can never both win for the same room at the same moment.
 */
class RoomMaintenanceController extends Controller
{
    public function update(Request $request, Room $room, RoomStatusService $status): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'in:maintenance,available'],
        ]);

        [$type, $message] = DB::transaction(function () use ($room, $data, $status) {
            $room = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();

            // Already in the requested state (double click, second tab): nothing to do.
            if ($room->status === $data['to']) {
                return ['success', "Room {$room->room_number} is already {$data['to']}."];
            }

            if ($data['to'] === 'available') {
                $room->update(['status' => 'available']);

                return ['success', "Room {$room->room_number} is back to available."];
            }

            // available -> maintenance: refuse while a guest is in it or a stay covers today (same rules as everywhere else).
            if (in_array($status->getRoomStatus($room), [RoomStatusService::OCCUPIED, RoomStatusService::RESERVED], true)) {
                return ['error', "Room {$room->room_number} has a guest or a booking today, so it cannot go under maintenance. "
                    . 'Check the guest out or cancel the booking first.'];
            }

            $room->update(['status' => 'maintenance']);

            return ['success', "Room {$room->room_number} is now under maintenance and cannot be booked."];
        });

        return back()->with($type, $message);
    }
}
