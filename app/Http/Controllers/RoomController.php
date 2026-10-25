<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::with('roomType')
            ->when($request->search, fn($q, $s) => $q->where('room_number', 'like', "%{$s}%"))
            ->when($request->room_type_id, fn($q, $id) => $q->where('room_type_id', $id))
            ->when($request->status, fn($q, $st) => $q->where('status', $st))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $roomTypes = RoomType::orderBy('type_name')->get();
        
        if ($request->ajax()) {
            return view('rooms.partials.table', compact('rooms'))->render();
        }
        
        return view('rooms.index', compact('rooms', 'roomTypes'));
    }

    public function create()
    {
        $roomTypes = RoomType::orderBy('type_name')->get();
        return view('rooms.create', compact('roomTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_number'  => 'required|string|max:50|unique:rooms,room_number',
            'floor'        => 'required|integer|min:0',
            'status'       => 'required|in:available,occupied,maintenance',
            'description'  => 'nullable|string',
            'room_type_id' => 'required|exists:room_types,id',
        ]);

        Room::create($validated);

        return redirect()->route('rooms.index')->with('success', 'Room created successfully.');
    }

    public function edit(Room $room)
    {
        $roomTypes = RoomType::orderBy('type_name')->get();
        return view('rooms.edit', compact('room', 'roomTypes'));
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'room_number'  => 'required|string|max:50|unique:rooms,room_number,' . $room->id,
            'floor'        => 'required|integer|min:0',
            'status'       => 'required|in:available,occupied,maintenance',
            'description'  => 'nullable|string',
            'room_type_id' => 'required|exists:room_types,id',
        ]);

        $room->update($validated);

        return redirect()->route('rooms.index')->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        $room->delete();
        return redirect()->route('rooms.index')->with('success', 'Room deleted successfully.');
    }
}
