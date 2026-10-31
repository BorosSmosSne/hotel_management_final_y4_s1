<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\RoomStatusService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Room Booking screen: room grid, create booking, check-in / check-out,
 * and (admin only) edit / cancel / delete.
 *
 * Permissions are enforced here with BookingPolicy, not only hidden in the UI.
 * The price ALWAYS comes from the room type in the database, never from the request.
 */
class BookingController extends Controller
{
    /* =========================================================
     |  Room Booking page
     * ========================================================= */
    public function index(Request $request, RoomStatusService $status)
    {
        Gate::authorize('viewAny', Booking::class);

        $date = $this->parseDate($request->query('date')) ?? today();

        // Every room + the active booking (if any) that covers the chosen day.
        $rooms    = Room::with('roomType')->orderBy('floor')->orderBy('room_number')->get();
        $current  = $status->bookingsOn($date);   // the booking that decides each room's status (RoomStatusService)
        $upcoming = Booking::active()
            ->where('check_in', '>', $date->toDateString())
            ->orderBy('check_in')->get()
            ->unique('room_id')->keyBy('room_id');

        $cards = $rooms->map(fn (Room $room) => [
            'room'  => $room,
            'state' => $status->describe($room, $current->get($room->id)),
            'next'  => $upcoming->get($room->id),
        ]);

        // Status counts are for the whole hotel, before filters are applied.
        $counts = $status->summarise($cards->pluck('state.key'));

        // Filters. Floor must be a whole number (sidebar + dropdown both send ?floor=N); anything else is ignored.
        $search = trim((string) $request->query('search', ''));
        $floor  = ctype_digit((string) $request->query('floor', '')) ? (int) $request->query('floor') : null;
        $cards = $cards
            ->when($search !== '', fn ($c) => $c->filter(fn ($i) => Str::contains($i['room']->room_number, $search, true)))
            ->when($request->filled('room_type_id'), fn ($c) => $c->filter(fn ($i) => (string) $i['room']->room_type_id === (string) $request->query('room_type_id')))
            ->when($floor !== null, fn ($c) => $c->filter(fn ($i) => (int) $i['room']->floor === $floor))
            ->when($request->filled('status'), fn ($c) => $c->filter(fn ($i) => $i['state']['key'] === $request->query('status')))
            ->values();

        $bookings = Booking::with('room.roomType')
            ->withPaymentTotals()
            ->latest('id')
            ->paginate(8, ['*'], 'bookings')
            ->withQueryString();

        return view('bookings.index', [
            'cards'     => $cards,
            'counts'    => $counts,
            'totalRooms'=> $rooms->count(),
            'date'      => $date,
            'roomTypes' => RoomType::orderBy('type_name')->get(),
            'floors'    => $rooms->pluck('floor')->unique()->sort()->values(),
            'activeFloor' => $floor,              // active floor filter (or null)
            'allRooms'  => $rooms->keyBy('id'),   // used to re-open the modal after a validation error
            'bookings'  => $bookings,
        ]);
    }

    /* =========================================================
     |  Booking detail (totals, payments, record payment)
     * ========================================================= */
    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking);

        $booking->load(['room.roomType', 'guest', 'creator', 'payments.receiver']);

        return view('bookings.show', compact('booking'));
    }

    /* =========================================================
     |  Create booking
     * ========================================================= */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Booking::class);

        $data = $request->validate(
            $this->rules() + [
                'room_id'  => ['required', 'exists:rooms,id'],
                'guest_id' => ['nullable', 'integer', 'exists:guests,id'],   // picked from "find existing guest"
            ],
            $this->messages()
        );

        $in  = Carbon::parse($data['check_in'])->startOfDay();
        $out = Carbon::parse($data['check_out'])->startOfDay();

        // Transaction + row lock: two receptionists cannot book the same room at the same moment.
        $booking = DB::transaction(function () use ($data, $in, $out, $request) {
            $room = Room::with('roomType')->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();

            if ($room->status === 'maintenance') {
                throw ValidationException::withMessages(['room_id' => "Room {$room->room_number} is under maintenance and cannot be booked."]);
            }
            // No 'occupied' check here: a guest checked in today must not stop a booking for later dates.
            // assertCapacityAndFree() below rejects any stay that overlaps an active booking.

            $this->assertCapacityAndFree($room, (int) $data['guests'], $in, $out);

            // Existing guest (picked, or matched by ID number) or a new one created inline.
            // The booking keeps its own copy of name / phone / ID as a snapshot.
            $guest = ! empty($data['guest_id'])
                ? Guest::findOrFail($data['guest_id'])
                : Guest::firstOrCreate(
                    ['id_number' => $data['id_number']],
                    ['name' => $data['guest_name'], 'phone' => $data['phone']]
                );

            $nights = Booking::nightsBetween($in, $out);
            $price  = (float) $room->roomType->price_per_night;      // server-side price only

            return Booking::create([
                'room_id'         => $room->id,
                'guest_id'        => $guest->id,
                'created_by'      => $request->user()->id,
                'guest_name'      => $guest->name,
                'phone'           => $guest->phone,
                'id_number'       => $guest->id_number,
                'guests'          => $data['guests'],
                'notes'           => $data['notes'] ?? null,
                'check_in'        => $in,
                'check_out'       => $out,
                'nights'          => $nights,
                'price_per_night' => $price,
                'total_price'     => round($nights * $price, 2),
                'status'          => Booking::CONFIRMED,
            ]);
        });

        $booking->load('room');

        return back()->with('success', sprintf(
            'Room %s booked for %s: %s to %s (%d %s), total $%s.',
            $booking->room->room_number,
            $booking->guest_name,
            $booking->check_in->format('d M'),
            $booking->check_out->format('d M Y'),
            $booking->nights,
            Str::plural('night', $booking->nights),
            number_format((float) $booking->total_price, 2)
        ));
    }

    /* =========================================================
     |  Check in / check out  (admin + staff)
     * ========================================================= */
    public function checkIn(Booking $booking): RedirectResponse
    {
        Gate::authorize('checkIn', $booking);

        if (! $booking->canCheckIn()) {
            return back()->with('error', 'This guest can only be checked in during the stay (from the arrival date).');
        }

        $blocked = DB::transaction(function () use ($booking) {
            // Same row lock as store(), so check-in and a maintenance switch cannot race.
            $room = Room::whereKey($booking->room_id)->lockForUpdate()->firstOrFail();

            if ($room->status === 'maintenance') {
                return "Room {$room->room_number} is under maintenance. Set it back to available before checking the guest in.";
            }

            $booking->update(['status' => Booking::CHECKED_IN, 'checked_in_at' => now()]);
            $room->update(['status' => 'occupied']);   // keeps the dashboard + Rooms list in step

            return null;
        });

        if ($blocked) {
            return back()->with('error', $blocked);
        }

        return back()->with('success', "{$booking->guest_name} checked in to Room {$booking->room->room_number}.");
    }

    public function checkOut(Booking $booking): RedirectResponse
    {
        Gate::authorize('checkOut', $booking);

        if (! $booking->canCheckOut()) {
            return back()->with('error', 'Only a guest who is checked in can be checked out.');
        }

        DB::transaction(function () use ($booking) {
            $room = Room::whereKey($booking->room_id)->lockForUpdate()->firstOrFail();

            $booking->update(['status' => Booking::CHECKED_OUT, 'checked_out_at' => now()]);

            // Free the room again (a room someone put under maintenance in the meantime stays that way).
            if ($room->status === 'occupied') {
                $room->update(['status' => 'available']);
            }
        });

        return back()->with('success', "{$booking->guest_name} checked out of Room {$booking->room->room_number}.");
    }

    /* =========================================================
     |  Edit / cancel / delete  (admin only)
     * ========================================================= */
    public function edit(Booking $booking)
    {
        Gate::authorize('update', $booking);

        if (! $booking->isEditable()) {
            return redirect()->route('bookings.index')->with('error', 'Only active bookings can be edited.');
        }

        $booking->load('room.roomType');

        return view('bookings.edit', compact('booking'));
    }

    public function update(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('update', $booking);

        if (! $booking->isEditable()) {
            return redirect()->route('bookings.index')->with('error', 'Only active bookings can be edited.');
        }

        // On edit the arrival date may already be in the past (guest is in-house).
        $data = $request->validate($this->rules(allowPast: true), $this->messages());

        $in  = Carbon::parse($data['check_in'])->startOfDay();
        $out = Carbon::parse($data['check_out'])->startOfDay();

        DB::transaction(function () use ($booking, $data, $in, $out) {
            $room = Room::with('roomType')->whereKey($booking->room_id)->lockForUpdate()->firstOrFail();

            $this->assertCapacityAndFree($room, (int) $data['guests'], $in, $out, ignoreBookingId: $booking->id);

            $nights = Booking::nightsBetween($in, $out);

            $booking->update([
                'guest_name'  => $data['guest_name'],
                'phone'       => $data['phone'],
                'id_number'   => $data['id_number'],
                'guests'      => $data['guests'],
                'notes'       => $data['notes'] ?? null,
                'check_in'    => $in,
                'check_out'   => $out,
                'nights'      => $nights,
                // keeps the rate that was agreed when the booking was made
                'total_price' => round($nights * (float) $booking->price_per_night, 2),
            ]);
        });

        return redirect()->route('bookings.index')->with('success', 'Booking updated successfully.');
    }

    public function cancel(Booking $booking): RedirectResponse
    {
        Gate::authorize('cancel', $booking);

        if (! $booking->canCancel()) {
            return back()->with('error', 'Only a booking that has not checked in can be cancelled.');
        }

        $booking->update(['status' => Booking::CANCELLED, 'cancelled_at' => now()]);

        return back()->with('success', "Booking for {$booking->guest_name} was cancelled. Room {$booking->room->room_number} is free again.");
    }

    public function destroy(Booking $booking): RedirectResponse
    {
        Gate::authorize('delete', $booking);

        DB::transaction(function () use ($booking) {
            if ($booking->status === Booking::CHECKED_IN) {
                Room::whereKey($booking->room_id)->where('status', 'occupied')->update(['status' => 'available']);
            }

            $booking->delete();
        });

        return back()->with('success', 'Booking deleted.');
    }

    /* =========================================================
     |  Helpers
     * ========================================================= */

    /** Shared validation rules for create + edit. */
    private function rules(bool $allowPast = false): array
    {
        return [
            'guest_name' => ['required', 'string', 'max:255'],
            'phone'      => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-.\s]{6,30}$/'],
            'id_number'  => ['required', 'string', 'max:50'],
            'check_in'   => ['required', 'date', $allowPast ? 'date' : 'after_or_equal:today'],
            'check_out'  => ['required', 'date', 'after:check_in'],
            'guests'     => ['required', 'integer', 'min:1', 'max:20'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function messages(): array
    {
        return [
            'guest_name.required'     => 'Please enter the customer name.',
            'phone.required'          => 'Please enter a phone number.',
            'phone.regex'             => 'The phone number may only contain digits, spaces, + ( ) - .',
            'id_number.required'      => 'Please enter the ID / passport number.',
            'check_in.after_or_equal' => 'Check-in cannot be in the past.',
            'check_out.after'         => 'Check-out must be after the check-in date.',
            'guests.min'              => 'At least one guest is required.',
        ];
    }

    /**
     * Room must fit the party and must not overlap another active booking.
     * Same-day turnover (check-out day = next check-in day) is allowed.
     */
    private function assertCapacityAndFree(Room $room, int $guests, Carbon $in, Carbon $out, ?int $ignoreBookingId = null): void
    {
        $capacity = (int) ($room->roomType->capacity ?? 0);
        if ($capacity > 0 && $guests > $capacity) {
            throw ValidationException::withMessages([
                'guests' => "Room {$room->room_number} ({$room->roomType->type_name}) sleeps up to {$capacity} " . Str::plural('guest', $capacity) . '.',
            ]);
        }

        $clash = Booking::active()
            ->where('room_id', $room->id)
            ->overlapping($in, $out)
            ->when($ignoreBookingId, fn ($q) => $q->whereKeyNot($ignoreBookingId))
            ->orderBy('check_in')
            ->first();

        if ($clash) {
            throw ValidationException::withMessages([
                'check_in' => "Room {$room->room_number} is already booked from {$clash->check_in->format('d M Y')} to {$clash->check_out->format('d M Y')}. Please choose different dates.",
            ]);
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
