{{-- CHANGED: Bootstrap table -> Tailwind. Same loop, null-safe roomType access, routes and @csrf/@method('DELETE').
     Row ID now shows $room->id (was $loop->iteration, which restarted on every page). --}}
@php
    // presentation only: status -> chip colours
    $chips = [
        'available'   => ['bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
        'occupied'    => ['bg-red-50 text-red-700', 'bg-red-500'],
        'maintenance' => ['bg-amber-50 text-amber-700', 'bg-amber-500'],
    ];
@endphp
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[46rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-4">ID</th>
                    <th scope="col" class="px-6 py-4">Room</th>
                    <th scope="col" class="px-6 py-4">Type</th>
                    <th scope="col" class="px-6 py-4">Floor</th>
                    <th scope="col" class="px-6 py-4">Price / night</th>
                    <th scope="col" class="px-6 py-4">Status</th>
                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rooms as $room)
                    @php $chip = $chips[$room->status] ?? ['bg-slate-100 text-slate-700', 'bg-slate-400']; @endphp
                    <tr class="border-t border-slate-100 transition hover:bg-tint/40">
                        <td class="px-6 py-4 text-slate-500">#{{ $room->id }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-tint text-primary"><x-icon name="door" /></span>
                                <span class="font-semibold">Room {{ $room->room_number }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="chip bg-tint text-primary">{{ $room->roomType->type_name ?? 'N/A' }}</span></td>
                        <td class="px-6 py-4 text-slate-600">Floor {{ $room->floor }}</td>
                        <td class="px-6 py-4"><span class="font-bold">${{ number_format($room->roomType->price_per_night ?? 0, 2) }}</span> <span class="text-xs text-slate-500">/ night</span></td>
                        <td class="px-6 py-4">
                            <span class="chip {{ $chip[0] }}"><span class="size-1.5 rounded-full {{ $chip[1] }}"></span>{{ ucfirst($room->status) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('rooms.edit', $room) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit room {{ $room->room_number }}"><x-icon name="pencil" class="size-4" /></a>
                                <form action="{{ route('rooms.destroy', $room) }}" method="POST" class="inline"
                                      data-confirm-delete
                                      data-heading="Delete this room?"
                                      data-title="Room {{ $room->room_number }} — {{ $room->roomType->type_name ?? 'N/A' }} (Floor {{ $room->floor }})"
                                      data-detail="The room is removed from the inventory. Reassign any reservations for it manually.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete room {{ $room->room_number }}"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-14 text-center text-slate-500">
                            <p class="font-medium text-ink">No rooms found.</p>
                            <p class="mt-1 text-sm">Adjust your filters, or <a href="{{ route('rooms.create') }}" class="font-semibold text-primary hover:underline">add a room</a>.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
        <p class="text-sm text-slate-500">
            @if($rooms->total())Showing <b class="text-ink">{{ $rooms->firstItem() }}</b> to <b class="text-ink">{{ $rooms->lastItem() }}</b> of <b class="text-ink">{{ $rooms->total() }}</b> rooms @endif
        </p>
        {{ $rooms->links() }}
    </div>
</div>
