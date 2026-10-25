<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;

class RoomTypeController extends Controller
{
    public function index(Request $request)
    {
        $roomTypes = RoomType::when($request->search, function ($q, $search) {
                $q->where('type_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
            
            // If it's an AJAX request, return only the table HTML
    if ($request->ajax()) {
        return view('room_types.partials.table', compact('roomTypes'))->render();
    }

        return view('room_types.index', compact('roomTypes'));
    }

    public function create()
    {
        return view('room_types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type_name'       => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'capacity'        => 'required|integer|min:1',
            'description'     => 'nullable|string',
        ]);

        RoomType::create($validated);

        return redirect()->route('room-types.index')->with('success', 'Room type created successfully.');
    }

    public function edit(RoomType $roomType)
    {
        return view('room_types.edit', compact('roomType'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        $validated = $request->validate([
            'type_name'       => 'required|string|max:255',
            'price_per_night' => 'required|numeric|min:0',
            'capacity'        => 'required|integer|min:1',
            'description'     => 'nullable|string',
        ]);

        $roomType->update($validated);

        return redirect()->route('room-types.index')->with('success', 'Room type updated successfully.');
    }

    public function destroy(RoomType $roomType)
    {
        $roomType->delete();
        return redirect()->route('room-types.index')->with('success', 'Room type deleted successfully.');
    }
}