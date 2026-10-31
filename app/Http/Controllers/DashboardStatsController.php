<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\RoomStatusService;
use Illuminate\Http\JsonResponse;

/**
 * GET /dashboard/stats  (auth + active, same group as /dashboard)
 *
 * JSON for the live dashboard: counts + percentages for today and the five newest rooms,
 * all computed by RoomStatusService, so it always matches the Room Booking page.
 */
class DashboardStatsController extends Controller
{
    public function __invoke(RoomStatusService $status): JsonResponse
    {
        $day    = $status->day();
        $counts = $status->getStatusCounts($day);

        $recent = Room::with('roomType')->latest()->latest('id')->take(5)->get();
        $map    = $status->statusMap($day, $recent);

        return response()->json([
            'date'       => $day->toDateString(),
            'counts'     => $counts,
            'recent'     => $recent->map(fn (Room $r) => [
                'id'          => $r->id,
                'room_number' => $r->room_number,
                'floor'       => $r->floor,
                'type'        => $r->roomType?->type_name,
                'price'       => (float) ($r->roomType?->price_per_night ?? 0),
                'status'      => $map[$r->id],
                'label'       => ucfirst($map[$r->id]),
                'added'       => $r->created_at?->diffForHumans(),
            ])->values(),
            'updated_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }
}
