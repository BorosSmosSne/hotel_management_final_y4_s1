@extends('layouts.app')
@section('title', 'Room Booking')
@section('content')

@php
    // presentation only: effective room status -> colours
    $look = [
        'available'   => ['card' => 'bg-emerald-50/70 ring-emerald-200', 'chip' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'reserved'    => ['card' => 'bg-orange-50/70 ring-orange-200',   'chip' => 'bg-orange-100 text-orange-700',   'dot' => 'bg-orange-500'],
        'occupied'    => ['card' => 'bg-red-50/70 ring-red-200',         'chip' => 'bg-red-100 text-red-700',         'dot' => 'bg-red-500'],
        'maintenance' => ['card' => 'bg-slate-100 ring-slate-200',       'chip' => 'bg-slate-200 text-slate-600',     'dot' => 'bg-slate-400'],
    ];
    // booking record status -> chip
    $bLook = [
        'confirmed'   => ['Reserved',    'bg-orange-50 text-orange-700', 'bg-orange-500'],
        'checked_in'  => ['Checked in',  'bg-red-50 text-red-700',       'bg-red-500'],
        'checked_out' => ['Checked out', 'bg-slate-100 text-slate-600',  'bg-slate-400'],
        'cancelled'   => ['Cancelled',   'bg-slate-100 text-slate-500',   'bg-slate-300'],
    ];
    $isAdmin  = auth()->user()->isAdmin();
    $filtered = request()->hasAny(['search', 'status', 'room_type_id', 'floor']) || ! $date->isToday();

    // Re-open the modal with the user's input when the server rejected the booking
    $reopen = (old('room_id') && $errors->any()) ? $allRooms->get((int) old('room_id')) : null;
@endphp

<x-page-hero eyebrow="Front desk" title="Room Booking" subtitle="Pick an available room, enter the guest details and confirm the stay." />

{{-- Status summary for the selected day --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Available" :value="$counts['available'] ?? 0" :hint="'on ' . $date->format('d M Y')" icon="check" tone="green" />
    <x-stat-card label="Reserved" :value="$counts['reserved'] ?? 0" hint="booked, not checked in" icon="calendar" tone="orange" />
    <x-stat-card label="Occupied" :value="$counts['occupied'] ?? 0" hint="guest in the room" icon="users" tone="red" />
    <x-stat-card label="Maintenance" :value="$counts['maintenance'] ?? 0" hint="cleaning / repair" icon="wrench" tone="slate" />
</div>

{{-- Filters (GET form: shareable URLs, works without JS; JS submits on change) --}}
<form method="GET" action="{{ route('bookings.index') }}" id="roomFilters" class="card mt-6 p-4" data-filter-form>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <div class="relative sm:col-span-2">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="f_search" class="sr-only">Search room number</label>
            <input type="text" id="f_search" name="search" class="field !pl-12" placeholder="Search room number…"
                   value="{{ request('search') }}" autocomplete="off" data-filter-text>
        </div>
        <div>
            <label for="f_status" class="sr-only">Status</label>
            <select id="f_status" name="status" class="field" data-filter-auto>
                <option value="">All Status</option>
                @foreach (['available' => 'Available', 'reserved' => 'Reserved', 'occupied' => 'Occupied', 'maintenance' => 'Maintenance'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f_type" class="sr-only">Room type</label>
            <select id="f_type" name="room_type_id" class="field" data-filter-auto>
                <option value="">All Types</option>
                @foreach ($roomTypes as $type)
                    <option value="{{ $type->id }}" @selected(request('room_type_id') == $type->id)>{{ $type->type_name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f_floor" class="sr-only">Floor</label>
            <select id="f_floor" name="floor" class="field" data-filter-auto>
                <option value="">All Floors</option>
                @foreach ($floors as $floor)
                    <option value="{{ $floor }}" @selected((string) request('floor') === (string) $floor)>Floor {{ $floor }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="f_date" class="sr-only">Stay date</label>
            <input type="date" id="f_date" name="date" class="field" value="{{ $date->toDateString() }}" title="Show room status for this day" data-filter-auto>
        </div>
    </div>
    @if ($filtered)
        <div class="mt-3 flex justify-end">
            <a href="{{ route('bookings.index') }}" class="btn btn-soft !py-2"><x-icon name="refresh" class="size-4" />Reset filters</a>
        </div>
    @endif
</form>

{{-- Room grid --}}
<section class="card mt-6 p-5 sm:p-6" aria-labelledby="gridTitle">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="gridTitle" class="text-lg font-bold">Rooms</h2>
            <p class="text-sm text-slate-500">
                Showing {{ $cards->count() }} of {{ $totalRooms }} rooms for <b class="text-ink">{{ $date->isToday() ? 'today' : $date->format('d M Y') }}</b>.
                Click a green room to book it.
            </p>
        </div>
        <ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-medium text-slate-600" aria-label="Legend">
            @foreach (['available' => 'Available', 'reserved' => 'Reserved', 'occupied' => 'Occupied', 'maintenance' => 'Maintenance'] as $k => $v)
                <li class="flex items-center gap-1.5"><span class="size-2.5 rounded-full {{ $look[$k]['dot'] }}"></span>{{ $v }}</li>
            @endforeach
        </ul>
    </div>

    <div id="roomGrid" class="mt-5 grid grid-cols-1 gap-4 transition-opacity min-[520px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
        @forelse ($cards as $c)
            @php
                $room  = $c['room'];
                $state = $c['state'];
                $l     = $look[$state['key']];
                $type  = $room->roomType;
                $isAvailable = $state['key'] === 'available';
            @endphp

            @if ($isAvailable)
                {{-- Selectable card --}}
                <button type="button" data-book-room
                        data-room-id="{{ $room->id }}" data-room-number="{{ $room->room_number }}"
                        data-room-type="{{ $type->type_name ?? 'N/A' }}" data-room-floor="{{ $room->floor }}"
                        data-room-price="{{ $type->price_per_night ?? 0 }}" data-room-capacity="{{ $type->capacity ?? '' }}"
                        aria-label="Book room {{ $room->room_number }}"
                        class="group flex flex-col rounded-2xl p-4 text-left ring-1 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-2 hover:ring-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary {{ $l['card'] }}">
            @else
                {{-- Not selectable: explains why instead --}}
                <div aria-disabled="true" class="flex cursor-not-allowed flex-col rounded-2xl p-4 text-left ring-1 {{ $l['card'] }}" title="{{ $state['reason'] }}">
            @endif

                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-medium text-slate-500">Room</p>
                        <p class="text-2xl font-bold leading-tight">{{ $room->room_number }}</p>
                    </div>
                    <span class="chip {{ $l['chip'] }}"><span class="size-1.5 rounded-full {{ $l['dot'] }}"></span>{{ $state['label'] }}</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="chip bg-white/80 text-primary">{{ $type->type_name ?? 'N/A' }}</span>
                    <span class="text-slate-600">Floor {{ $room->floor }}</span>
                    @if ($type)<span class="text-slate-500">· up to {{ $type->capacity }} {{ \Illuminate\Support\Str::plural('guest', $type->capacity) }}</span>@endif
                </div>

                <p class="mt-3 text-lg font-bold">${{ number_format($type->price_per_night ?? 0, 2) }}<span class="text-xs font-normal text-slate-500"> / night</span></p>

                <div class="mt-3 border-t border-black/5 pt-3 text-xs">
                    @if ($isAvailable)
                        <span class="flex items-center justify-between font-semibold text-primary">
                            <span>@if ($c['next'])Free until {{ $c['next']->check_in->format('d M') }}@else Ready to book @endif</span>
                            <span class="flex items-center gap-1">Book <x-icon name="chevron-right" class="size-3.5 transition group-hover:translate-x-0.5" /></span>
                        </span>
                    @else
                        <p class="flex items-start gap-1.5 text-slate-600"><x-icon name="ban" class="mt-0.5 size-3.5 shrink-0" /><span>{{ $state['reason'] }}</span></p>
                    @endif
                </div>

            @if ($isAvailable) </button> @else </div> @endif
        @empty
            <div class="col-span-full rounded-2xl bg-tint px-6 py-14 text-center text-slate-500">
                <p class="font-medium text-ink">No rooms match these filters.</p>
                <p class="mt-1 text-sm">Try another status, type or floor, or <a href="{{ route('bookings.index') }}" class="font-semibold text-primary hover:underline">reset the filters</a>.</p>
            </div>
        @endforelse
    </div>
</section>

{{-- Bookings list --}}
<section class="card mt-6 overflow-hidden" aria-labelledby="bookingsTitle">
    <div class="p-6 pb-4">
        <h2 id="bookingsTitle" class="text-lg font-bold">Bookings</h2>
        <p class="text-sm text-slate-500">Latest bookings first. Check guests in and out here.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[56rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-3">Guest</th>
                    <th scope="col" class="px-6 py-3">Room</th>
                    <th scope="col" class="px-6 py-3">Stay</th>
                    <th scope="col" class="px-6 py-3">Guests</th>
                    <th scope="col" class="px-6 py-3">Total</th>
                    <th scope="col" class="px-6 py-3">Status</th>
                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($bookings as $bk)
                    @php [$bLabel, $bChip, $bDot] = $bLook[$bk->status] ?? [ucfirst($bk->status), 'bg-slate-100 text-slate-600', 'bg-slate-400']; @endphp
                    <tr class="border-t border-slate-100 transition hover:bg-tint/40">
                        <td class="px-6 py-4">
                            <p class="font-semibold">{{ $bk->guest_name }}</p>
                            <p class="text-xs text-slate-500">{{ $bk->phone }} · ID {{ $bk->id_number }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="chip bg-tint text-primary">{{ $bk->room->room_number }}</span>
                            <span class="ml-1 text-xs text-slate-500">{{ $bk->room->roomType->type_name ?? '' }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $bk->check_in->format('d M') }} → {{ $bk->check_out->format('d M Y') }}
                            <span class="block text-xs text-slate-500">{{ $bk->nights }} {{ \Illuminate\Support\Str::plural('night', $bk->nights) }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $bk->guests }}</td>
                        <td class="px-6 py-4 font-bold">${{ number_format($bk->total_price, 2) }}</td>
                        <td class="px-6 py-4"><span class="chip {{ $bChip }}"><span class="size-1.5 rounded-full {{ $bDot }}"></span>{{ $bLabel }}</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Admin + staff --}}
                                @if ($bk->canCheckIn())
                                    <form action="{{ route('bookings.check-in', $bk) }}" method="POST" class="inline" data-loading-form>
                                        @csrf
                                        <button class="btn btn-soft !px-3 !py-2" data-submit><x-icon name="login" class="size-4" /><span data-submit-text>Check in</span></button>
                                    </form>
                                @endif
                                @if ($bk->canCheckOut())
                                    <form action="{{ route('bookings.check-out', $bk) }}" method="POST" class="inline" data-loading-form>
                                        @csrf
                                        <button class="btn btn-primary !px-3 !py-2" data-submit><x-icon name="logout" class="size-4" /><span data-submit-text>Check out</span></button>
                                    </form>
                                @endif

                                {{-- Admin only (also enforced by BookingPolicy in the controller) --}}
                                @can('update', $bk)
                                    @if ($bk->isEditable())
                                        <a href="{{ route('bookings.edit', $bk) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit booking for {{ $bk->guest_name }}"><x-icon name="pencil" class="size-4" /></a>
                                    @endif
                                @endcan
                                @can('cancel', $bk)
                                    @if ($bk->canCancel())
                                        <form action="{{ route('bookings.cancel', $bk) }}" method="POST" class="inline"
                                              data-confirm-delete data-confirm-label="Yes, cancel booking"
                                              data-heading="Cancel this booking?"
                                              data-title="{{ $bk->guest_name }} — Room {{ $bk->room->room_number }}"
                                              data-detail="{{ $bk->check_in->format('d M') }} to {{ $bk->check_out->format('d M Y') }}. The room becomes available again.">
                                            @csrf
                                            <button class="icon-btn bg-amber-50 text-amber-700 hover:bg-amber-100" aria-label="Cancel booking for {{ $bk->guest_name }}"><x-icon name="ban" class="size-4" /></button>
                                        </form>
                                    @endif
                                @endcan
                                @can('delete', $bk)
                                    <form action="{{ route('bookings.destroy', $bk) }}" method="POST" class="inline"
                                          data-confirm-delete data-heading="Delete this booking?"
                                          data-title="{{ $bk->guest_name }} — Room {{ $bk->room->room_number }}"
                                          data-detail="The booking record is removed permanently.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete booking for {{ $bk->guest_name }}"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-14 text-center text-slate-500">
                        <p class="font-medium text-ink">No bookings yet.</p>
                        <p class="mt-1 text-sm">Click an available room above to create the first one.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
        <p class="text-sm text-slate-500">
            @if ($bookings->total())Showing <b class="text-ink">{{ $bookings->firstItem() }}</b> to <b class="text-ink">{{ $bookings->lastItem() }}</b> of <b class="text-ink">{{ $bookings->total() }}</b> bookings @endif
        </p>
        {{ $bookings->links() }}
    </div>
</section>

{{-- Booking modal --}}
<dialog id="bookingDialog" aria-labelledby="bookingHeading" @if ($reopen) data-open @endif
        class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-3xl bg-white p-0 text-ink shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm">
    <form method="POST" action="{{ route('bookings.store') }}" data-booking-form data-submit-label="Booking…"
          data-default-date="{{ max($date->toDateString(), today()->toDateString()) }}"
          data-price="{{ $reopen?->roomType?->price_per_night }}" data-capacity="{{ $reopen?->roomType?->capacity }}">
        @csrf
        <input type="hidden" name="room_id" value="{{ $reopen?->id ?? old('room_id') }}" data-bk-room-id>

        <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-6">
            <div class="flex items-center gap-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary text-white"><x-icon name="door" /></span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">New booking</p>
                    <h2 id="bookingHeading" class="text-xl font-bold">
                        Room <span data-bk-room-number>{{ $reopen?->room_number }}</span>
                    </h2>
                    <p class="text-sm text-slate-600">
                        <span data-bk-room-type>{{ $reopen?->roomType?->type_name }}</span> ·
                        Floor <span data-bk-room-floor>{{ $reopen?->floor }}</span> ·
                        $<span data-bk-room-price>{{ number_format((float) ($reopen?->roomType?->price_per_night ?? 0), 2) }}</span> / night
                    </p>
                </div>
            </div>
            <button type="button" data-bk-close class="icon-btn text-slate-500 hover:bg-tint" aria-label="Close"><x-icon name="x" /></button>
        </div>

        <div class="p-6">
            <x-error-summary />
            @include('bookings.partials.fields', ['booking' => null, 'idp' => 'n_'])
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 p-6">
            <button type="button" data-bk-close class="btn btn-soft">Cancel</button>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="check" class="size-4" /><span data-submit-text>Confirm booking</span></button>
        </div>
    </form>
</dialog>

@endsection
