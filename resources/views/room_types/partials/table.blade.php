{{-- CHANGED: Bootstrap table -> Tailwind. Same loop, same routes, same @csrf/@method('DELETE').
     Row ID now shows $type->id (was $loop->iteration, which restarted on every page).
     "Rooms" column uses $type->room_count, which exists only if the controller adds withCount('room') (see notes). --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[44rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-4">ID</th>
                    <th scope="col" class="px-6 py-4">Type name</th>
                    <th scope="col" class="px-6 py-4">Price / night</th>
                    <th scope="col" class="px-6 py-4">Capacity</th>
                    <th scope="col" class="px-6 py-4">Rooms</th>
                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roomTypes as $type)
                    <tr class="border-t border-slate-100 transition hover:bg-tint/40">
                        <td class="px-6 py-4 text-slate-500">#{{ $type->id }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-tint text-primary"><x-icon name="bed" /></span>
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $type->type_name }}</p>
                                    <p class="max-w-xs truncate text-xs text-slate-500">{{ $type->description }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4"><span class="text-base font-bold text-primary">${{ number_format($type->price_per_night, 2) }}</span> <span class="text-xs text-slate-500">/ night</span></td>
                        <td class="px-6 py-4"><span class="chip bg-tint text-primary"><x-icon name="user" class="size-3.5" />{{ $type->capacity }} {{ $type->capacity == 1 ? 'guest' : 'guests' }}</span></td>
                        <td class="px-6 py-4 font-medium">{{ $type->room_count ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('room-types.edit', $type) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit {{ $type->type_name }}"><x-icon name="pencil" class="size-4" /></a>
                                <form action="{{ route('room-types.destroy', $type) }}" method="POST" class="inline"
                                      data-confirm-delete
                                      data-heading="Delete this room type?"
                                      data-title="{{ $type->type_name }}"
                                      data-detail="Rooms assigned to this type are deleted along with it.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete {{ $type->type_name }}"><x-icon name="trash" class="size-4" /></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-14 text-center text-slate-500">
                            <p class="font-medium text-ink">No room types found.</p>
                            <p class="mt-1 text-sm">Try a different search, or <a href="{{ route('room-types.create') }}" class="font-semibold text-primary hover:underline">add a room type</a>.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
        <p class="text-sm text-slate-500">
            @if($roomTypes->total())Showing <b class="text-ink">{{ $roomTypes->firstItem() }}</b> to <b class="text-ink">{{ $roomTypes->lastItem() }}</b> of <b class="text-ink">{{ $roomTypes->total() }}</b> room types @endif
        </p>
        {{ $roomTypes->links() }}
    </div>
</div>
