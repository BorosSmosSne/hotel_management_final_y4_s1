<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $counts = Room::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $totalRooms  = array_sum($counts);
        $available   = $counts['available'] ?? 0;
        $occupied    = $counts['occupied'] ?? 0;
        $maintenance = $counts['maintenance'] ?? 0;

        $pct = fn (int $n) => $totalRooms ? round($n / $totalRooms * 100, 1) : 0;

        return view('dashboard', [
            'totalRooms'     => $totalRooms,
            'available'      => $available,
            'occupied'       => $occupied,
            'maintenance'    => $maintenance,
            'availablePct'   => $pct($available),
            'occupiedPct'    => $pct($occupied),
            'maintenancePct' => $pct($maintenance),
            'roomTypeCount'  => RoomType::count(),
            'recentRooms'    => Room::with('roomType')->latest()->take(5)->get(),
            'typeBreakdown'  => RoomType::withCount('room')->orderByDesc('room_count')->get(),
        ]);
    }
}
