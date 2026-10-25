@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')

@php
    // presentation only: donut gradient stops + chip colours
    $stopA = $availablePct;
    $stopO = $availablePct + $occupiedPct;
    $donut = $totalRooms
        ? "conic-gradient(#10b981 0 {$stopA}%, #ef4444 {$stopA}% {$stopO}%, #f59e0b {$stopO}% 100%)"
        : 'conic-gradient(#e2e8f0 0 100%)';
    $legend = [
        ['Available',   $available,   $availablePct,   'bg-emerald-500'],
        ['Occupied',    $occupied,    $occupiedPct,    'bg-red-500'],
        ['Maintenance', $maintenance, $maintenancePct, 'bg-amber-500'],
    ];
    $chips = [
        'available'   => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'occupied'    => ['bg-red-50 text-red-700', 'bg-red-500'],
        'maintenance' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
    ];
@endphp

<x-page-hero eyebrow="Live overview" title="Operations Dashboard" subtitle="Room inventory and availability across the hotel.">
    <x-slot:actions>
        <a href="{{ route('rooms.create') }}" class="btn btn-white"><x-icon name="plus" class="size-5" />Add Room</a>
        <a href="{{ route('room-types.index') }}" class="btn btn-glass"><x-icon name="grid" class="size-5" />Room Types</a>
    </x-slot:actions>
</x-page-hero>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total rooms" :value="$totalRooms" hint="in the inventory" icon="bed" />
    <x-stat-card label="Available" :value="$available" :hint="$availablePct . '% of rooms'" icon="check" tone="green" />
    <x-stat-card label="Occupied" :value="$occupied" :hint="$occupiedPct . '% occupancy'" icon="users" tone="red" />
    <x-stat-card label="Room types" :value="$roomTypeCount" hint="categories" icon="grid" />
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-[24rem_1fr]">

    {{-- Status breakdown --}}
    <section class="card p-6" aria-labelledby="statusTitle">
        <h2 id="statusTitle" class="text-lg font-bold">Room status</h2>
        <p class="text-sm text-slate-500">Current allocation across {{ $totalRooms }} rooms</p>

        <div class="mt-6 grid place-items-center">
            <div class="grid size-52 place-items-center rounded-full" style="background: {{ $donut }}" role="img"
                 aria-label="{{ $available }} available, {{ $occupied }} occupied, {{ $maintenance }} under maintenance">
                <div class="grid size-36 place-items-center rounded-full bg-white text-center">
                    <div>
                        <p class="text-4xl font-bold">{{ $totalRooms }}</p>
                        <p class="text-xs text-slate-500">total rooms</p>
                    </div>
                </div>
            </div>
        </div>

        <ul class="mt-6 space-y-2">
            @foreach($legend as [$label, $count, $percent, $dot])
                <li class="flex items-center justify-between rounded-xl bg-tint px-4 py-3 text-sm">
                    <span class="flex items-center gap-2.5"><span class="size-2.5 rounded-full {{ $dot }}"></span>{{ $label }}</span>
                    <span class="font-semibold">{{ $count }} <span class="font-normal text-slate-500">({{ $percent }}%)</span></span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Recently added rooms --}}
    <section class="card overflow-hidden" aria-labelledby="recentTitle">
        <div class="flex flex-wrap items-center justify-between gap-3 p-6 pb-4">
            <div>
                <h2 id="recentTitle" class="text-lg font-bold">Recently added rooms</h2>
                <p class="text-sm text-slate-500">The latest 5 rooms in the inventory</p>
            </div>
            <a href="{{ route('rooms.index') }}" class="btn btn-soft">View all rooms <x-icon name="chevron-right" class="size-4" /></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[34rem] text-left text-sm">
                <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr>
                        <th scope="col" class="px-6 py-3">Room</th>
                        <th scope="col" class="px-6 py-3">Type</th>
                        <th scope="col" class="px-6 py-3">Rate</th>
                        <th scope="col" class="px-6 py-3">Status</th>
                        <th scope="col" class="px-6 py-3">Added</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRooms as $room)
                        @php $chip = $chips[$room->status] ?? ['bg-slate-100 text-slate-700', 'bg-slate-400']; @endphp
                        <tr class="border-t border-slate-100 hover:bg-tint/40">
                            <td class="px-6 py-4">
                                <a href="{{ route('rooms.edit', $room) }}" class="flex items-center gap-3 font-semibold hover:text-primary">
                                    <span class="grid size-9 place-items-center rounded-lg bg-tint text-primary"><x-icon name="door" class="size-4" /></span>
                                    <span>Room {{ $room->room_number }}<span class="block text-xs font-normal text-slate-500">Floor {{ $room->floor }}</span></span>
                                </a>
                            </td>
                            <td class="px-6 py-4">{{ $room->roomType->type_name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 font-bold">${{ number_format($room->roomType->price_per_night ?? 0, 2) }}<span class="text-xs font-normal text-slate-500"> /night</span></td>
                            <td class="px-6 py-4"><span class="chip {{ $chip[0] }}"><span class="size-1.5 rounded-full {{ $chip[1] }}"></span>{{ ucfirst($room->status) }}</span></td>
                            <td class="px-6 py-4 text-slate-500">{{ $room->created_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">No rooms yet. <a href="{{ route('rooms.create') }}" class="font-semibold text-primary hover:underline">Add your first room</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

{{-- Rooms by type --}}
<section class="card mt-6 p-6" aria-labelledby="typeTitle">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="typeTitle" class="text-lg font-bold">Rooms by type</h2>
            <p class="text-sm text-slate-500">How the inventory is split across room types</p>
        </div>
        <a href="{{ route('room-types.index') }}" class="btn btn-soft">Manage room types <x-icon name="chevron-right" class="size-4" /></a>
    </div>

    <div class="mt-6 grid gap-x-10 gap-y-5 md:grid-cols-2">
        @forelse($typeBreakdown as $type)
            @php $share = $totalRooms ? round($type->room_count / $totalRooms * 100) : 0; @endphp
            <div>
                <div class="mb-1.5 flex items-baseline justify-between text-sm">
                    <span class="font-semibold">{{ $type->type_name }} <span class="font-normal text-slate-500">· ${{ number_format($type->price_per_night, 0) }}/night</span></span>
                    <span class="font-semibold">{{ $type->room_count }} <span class="font-normal text-slate-500">rooms</span></span>
                </div>
                <div class="h-2.5 overflow-hidden rounded-full bg-tint" role="progressbar" aria-valuenow="{{ $share }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ $type->type_name }} share of rooms">
                    <div class="h-full rounded-full bg-linear-to-r from-primary to-secondary" style="width: {{ $share }}%"></div>
                </div>
            </div>
        @empty
            <p class="text-sm text-slate-500">No room types yet.</p>
        @endforelse
    </div>
</section>

@endsection
