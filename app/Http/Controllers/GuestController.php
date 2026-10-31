<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveGuestRequest;
use App\Models\Booking;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Guests: Admin + Staff can list, search, add, edit and view. Only Admin can delete.
 */
class GuestController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('role:admin', only: ['destroy'])];
    }

    public function index(Request $request)
    {
        $guests = Guest::query()
            ->search($request->query('search'))
            ->withCount('bookings')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('guests.index', ['guests' => $guests, 'total' => Guest::count()]);
    }

    /** JSON for the "find existing guest" box in the booking form. */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Guest::search($term)->orderBy('name')->limit(8)
                ->get(['id', 'name', 'phone', 'email', 'id_number', 'nationality'])
        );
    }

    public function create()
    {
        return view('guests.create');
    }

    public function store(SaveGuestRequest $request): RedirectResponse
    {
        $guest = Guest::create($request->validated());

        return redirect()->route('guests.show', $guest)->with('success', "Guest {$guest->name} was added.");
    }

    public function show(Guest $guest)
    {
        $bookings = $guest->bookings()
            ->with('room.roomType')
            ->withPaymentTotals()
            ->latest('check_in')
            ->paginate(8, ['*'], 'bookings')
            ->withQueryString();

        $stays = $guest->bookings()->where('status', '!=', Booking::CANCELLED);

        return view('guests.show', [
            'guest'    => $guest,
            'bookings' => $bookings,
            'stayCount'=> (clone $stays)->count(),
            'nights'   => (int) (clone $stays)->sum('nights'),
            'billed'   => (float) (clone $stays)->sum('total_price'),
        ]);
    }

    public function edit(Guest $guest)
    {
        return view('guests.edit', compact('guest'));
    }

    public function update(SaveGuestRequest $request, Guest $guest): RedirectResponse
    {
        $guest->update($request->validated());

        return redirect()->route('guests.show', $guest)->with('success', 'Guest details were updated.');
    }

    public function destroy(Guest $guest): RedirectResponse
    {
        // Bookings are kept: they carry their own name / phone / ID snapshot.
        $guest->delete();

        return redirect()->route('guests.index')->with('success', "Guest {$guest->name} was deleted. Their bookings were kept.");
    }
}
