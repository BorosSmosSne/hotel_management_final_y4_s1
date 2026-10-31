<?php

namespace App\Services;

use App\Exceptions\HousekeepingException;
use App\Models\Booking;
use App\Models\HousekeepingLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The ONE place that changes a room between available / cleaning / maintenance.
 * Used by the Housekeeping page and by check-out, so every change is validated and logged the same way.
 *
 *   available ──needs_cleaning──▶ cleaning ──cleaned──▶ available
 *   available|cleaning ──maintenance_started──▶ maintenance ──maintenance_ended──▶ available
 *   (any) ──checked_out──▶ cleaning      (automatic, when a guest checks out)
 */
class HousekeepingService
{
    private const TRANSITIONS = [
        'cleaned'             => ['from' => ['cleaning'],                                         'to' => 'available'],
        'needs_cleaning'      => ['from' => ['available'],                                        'to' => 'cleaning'],
        'maintenance_started' => ['from' => ['available', 'cleaning'],                            'to' => 'maintenance'],
        'maintenance_ended'   => ['from' => ['maintenance'],                                      'to' => 'available'],
        'checked_out'         => ['from' => ['available', 'cleaning', 'occupied', 'maintenance'], 'to' => 'cleaning'],
    ];

    /**
     * @throws HousekeepingException when the change is not allowed
     */
    public function apply(Room $room, string $action, ?User $user, ?Booking $booking = null, ?string $note = null): HousekeepingLog
    {
        $rule = self::TRANSITIONS[$action] ?? throw new HousekeepingException('Unknown housekeeping action.');

        return DB::transaction(function () use ($room, $action, $user, $booking, $note, $rule) {
            // Lock the row so two people cannot change the same room at the same moment
            $locked = Room::whereKey($room->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, $rule['from'], true)) {
                throw new HousekeepingException(
                    "Room {$locked->room_number} is {$locked->status}, so this change is not possible. Refresh the page."
                );
            }

            // Never touch a room with a guest inside (except the check-out itself)
            if ($action !== 'checked_out' && Booking::where('room_id', $locked->id)->where('status', Booking::CHECKED_IN)->exists()) {
                throw new HousekeepingException("Room {$locked->room_number} has a guest checked in.");
            }

            $from = $locked->status;
            $locked->status = $rule['to'];

            if ($action === 'cleaned') {
                $locked->last_cleaned_at = now();
                $locked->last_cleaned_by = $user?->id;
            }

            $locked->save();

            return HousekeepingLog::create([
                'room_id'     => $locked->id,
                'user_id'     => $user?->id,
                'booking_id'  => $booking?->id,
                'action'      => $action,
                'from_status' => $from,
                'to_status'   => $rule['to'],
                'note'        => $note ?: null,
            ]);
        });
    }
}
