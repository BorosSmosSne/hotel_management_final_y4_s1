<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * THE single source of truth for "what is the status of a room on a given day?".
 * The Room Booking page, the Dashboard and its live JSON endpoint all call this class.
 *
 * Rules, in priority order (cancelled and checked-out bookings never count):
 *   1. maintenance : rooms.status = 'maintenance'  (manual flag, always wins)
 *   2. occupied    : a 'checked_in' booking covers the day
 *   3. reserved    : a 'confirmed' booking covers the day (guest has not been checked in yet)
 *   4. available   : none of the above
 *
 * "Covers the day" is Booking::scopeCovering(): check_in <= day < check_out. The check-out day is
 * exclusive, so a guest leaving today does not make the room occupied tonight, and a same-day
 * arrival is Reserved until staff press Check in.
 *
 * "Today" uses the app timezone: set APP_TIMEZONE=Asia/Phnom_Penh (config/app.php -> 'timezone').
 *
 * Performance: use statusMap() / getStatusCounts() for many rooms (2 queries in total, no N+1).
 * getRoomStatus() is for ONE room (1 query); do not call it inside a loop.
 */
class RoomStatusService
{
    public const AVAILABLE   = 'available';
    public const RESERVED    = 'reserved';
    public const OCCUPIED    = 'occupied';
    public const MAINTENANCE = 'maintenance';

    public const STATUSES = [self::AVAILABLE, self::RESERVED, self::OCCUPIED, self::MAINTENANCE];

    /** Normalise to the start of the day in the app timezone (default: today). */
    public function day(?Carbon $date = null): Carbon
    {
        return ($date ?? Carbon::now(config('app.timezone')))->copy()->startOfDay();
    }

    /** Status of ONE room (1 query). */
    public function getRoomStatus(Room $room, ?Carbon $date = null): string
    {
        if ($room->status === self::MAINTENANCE) {
            return self::MAINTENANCE;   // no booking query needed
        }

        $booking = $this->bookingsOn($this->day($date), [$room->id])->get($room->id);

        return $this->resolve($room, $booking);
    }

    /**
     * room_id => status for every room (or only $rooms). Two queries in total.
     *
     * @param  Collection<int, Room>|null  $rooms  pass an already loaded collection to avoid re-querying rooms
     * @return Collection<int, string>
     */
    public function statusMap(?Carbon $date = null, ?Collection $rooms = null): Collection
    {
        $day      = $this->day($date);
        $rooms  ??= Room::get(['id', 'status']);
        $bookings = $this->bookingsOn($day, $rooms->pluck('id')->all());

        return $rooms->mapWithKeys(fn (Room $r) => [$r->id => $this->resolve($r, $bookings->get($r->id))]);
    }

    /**
     * Counts + percentages for the whole hotel.
     * Returns: available, reserved, occupied, maintenance, total, percent[...] and occupancy (occupied / total).
     */
    public function getStatusCounts(?Carbon $date = null): array
    {
        return $this->summarise($this->statusMap($date)->values());
    }

    /** Turn a list of status keys into the counts array (also used by the Room Booking page). */
    public function summarise(Collection $keys): array
    {
        $total  = $keys->count();
        $counts = array_fill_keys(self::STATUSES, 0);

        foreach ($keys as $key) {
            if (isset($counts[$key])) {
                $counts[$key]++;
            }
        }

        $pct = fn (int $n) => $total > 0 ? round($n / $total * 100, 1) : 0.0;

        return $counts + [
            'total'     => $total,
            'percent'   => array_map($pct, $counts),
            'occupancy' => $pct($counts[self::OCCUPIED]),
        ];
    }

    /**
     * The booking that decides each room's status on $day: room_id => Booking.
     * If a room somehow has both, the checked-in booking wins over the confirmed one.
     *
     * @param  int[]|null  $roomIds
     * @return Collection<int, Booking>
     */
    public function bookingsOn(Carbon $day, ?array $roomIds = null): Collection
    {
        return Booking::active()
            ->covering($day)
            ->when($roomIds !== null, fn ($q) => $q->whereIn('room_id', $roomIds))
            ->get(['id', 'room_id', 'status', 'guest_name', 'check_in', 'check_out'])
            ->sortBy(fn (Booking $b) => $b->status === Booking::CHECKED_IN ? 0 : 1)
            ->unique('room_id')
            ->keyBy('room_id');
    }

    /** The four rules in one place. */
    public function resolve(Room $room, ?Booking $booking): string
    {
        if ($room->status === self::MAINTENANCE) {
            return self::MAINTENANCE;
        }
        if ($booking?->status === Booking::CHECKED_IN) {
            return self::OCCUPIED;
        }
        if ($booking?->status === Booking::CONFIRMED) {
            return self::RESERVED;
        }

        return self::AVAILABLE;
    }

    /** key / label / reason for a room card on the Room Booking page. */
    public function describe(Room $room, ?Booking $booking): array
    {
        $key = $this->resolve($room, $booking);

        $reason = match ($key) {
            self::MAINTENANCE => 'Under maintenance or cleaning - it cannot be booked until it is back in service.',
            self::OCCUPIED    => "Occupied - {$booking->guest_name} is staying until {$booking->check_out->format('d M Y')}.",
            self::RESERVED    => "Reserved - {$booking->guest_name} arrives {$booking->check_in->format('d M Y')} (not checked in yet).",
            default           => '',
        };

        return ['key' => $key, 'label' => ucfirst($key), 'reason' => $reason];
    }
}
