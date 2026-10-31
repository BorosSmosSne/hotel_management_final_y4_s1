<?php

namespace App\Http\Controllers;

use App\Exceptions\HousekeepingException;
use App\Http\Requests\HousekeepingActionRequest;
use App\Models\Booking;
use App\Models\HousekeepingLog;
use App\Models\Room;
use App\Services\HousekeepingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Housekeeping: Admin + Staff. Lists rooms that need cleaning / are clean / are in maintenance
 * and lets anyone signed in move a room between those states. Every change is logged.
 */
class HousekeepingController extends Controller
{
    private const TABS = ['cleaning', 'available', 'maintenance'];

    public function __construct(private HousekeepingService $service)
    {
    }

    public function index(Request $request)
    {
        $tab    = in_array($request->query('status'), [...self::TABS, 'all'], true) ? $request->query('status') : 'cleaning';
        $floor  = ctype_digit((string) $request->query('floor', '')) ? (int) $request->query('floor') : null;
        $search = trim((string) $request->query('search', ''));

        // Rooms housekeeping can work on: not occupied by a checked-in guest, not manually "occupied".
        $base = fn (): Builder => Room::query()
            ->whereIn('status', self::TABS)
            ->whereDoesntHave('bookings', fn ($b) => $b->where('status', Booking::CHECKED_IN))
            ->when($floor !== null, fn ($q) => $q->where('floor', $floor))
            ->when($search !== '', fn ($q) => $q->where('room_number', 'like', "%{$search}%"));

        $counts = $base()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();

        $rooms = $base()
            ->when($tab !== 'all', fn ($q) => $q->where('status', $tab))
            ->with(['roomType', 'lastCleaner'])
            ->orderByRaw("case status when 'cleaning' then 0 when 'maintenance' then 1 else 2 end")
            ->orderBy('floor')->orderBy('room_number')
            ->paginate(12)
            ->withQueryString();

        $log = HousekeepingLog::with(['room', 'user'])
            ->latest('id')
            ->paginate(8, ['*'], 'log')
            ->withQueryString();

        return view('housekeeping.index', [
            'rooms'  => $rooms,
            'counts' => $counts,
            'tab'    => $tab,
            'floor'  => $floor,
            'floors' => Room::query()->distinct()->orderBy('floor')->pluck('floor'),
            'log'    => $log,
        ]);
    }

    /** Cleaning -> Available. Records who cleaned it and when. */
    public function clean(HousekeepingActionRequest $request, Room $room): RedirectResponse
    {
        return $this->run($request, $room, 'cleaned', "Room {$room->room_number} is clean and available again.");
    }

    /** Available -> Cleaning. */
    public function dirty(HousekeepingActionRequest $request, Room $room): RedirectResponse
    {
        return $this->run($request, $room, 'needs_cleaning', "Room {$room->room_number} was marked for cleaning.");
    }

    /** Available / Cleaning -> Maintenance. */
    public function maintenance(HousekeepingActionRequest $request, Room $room): RedirectResponse
    {
        return $this->run($request, $room, 'maintenance_started', "Room {$room->room_number} was sent to maintenance.");
    }

    /** Maintenance -> Available. */
    public function service(HousekeepingActionRequest $request, Room $room): RedirectResponse
    {
        return $this->run($request, $room, 'maintenance_ended', "Room {$room->room_number} is back in service.");
    }

    private function run(HousekeepingActionRequest $request, Room $room, string $action, string $success): RedirectResponse
    {
        try {
            $this->service->apply($room, $action, $request->user(), note: $request->validated('note'));
        } catch (HousekeepingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
