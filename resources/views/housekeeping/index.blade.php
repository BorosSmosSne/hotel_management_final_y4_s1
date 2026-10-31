@extends('layouts.app')
@section('title', 'Housekeeping')
@section('content')

@php
    // status -> look (presentation only)
    $look = [
        'cleaning'    => ['label' => 'Needs cleaning', 'card' => 'bg-sky-50/70 ring-sky-200',         'chip' => 'bg-sky-100 text-sky-700',         'dot' => 'bg-sky-500'],
        'available'   => ['label' => 'Clean',          'card' => 'bg-emerald-50/70 ring-emerald-200', 'chip' => 'bg-emerald-100 text-emerald-700', 'dot' => 'bg-emerald-500'],
        'maintenance' => ['label' => 'Maintenance',    'card' => 'bg-slate-100 ring-slate-200',       'chip' => 'bg-slate-200 text-slate-600',     'dot' => 'bg-slate-400'],
    ];
    $tabs = [
        'cleaning'    => ['Needs cleaning', $counts['cleaning'] ?? 0],
        'available'   => ['Clean',          $counts['available'] ?? 0],
        'maintenance' => ['Maintenance',    $counts['maintenance'] ?? 0],
        'all'         => ['All',            array_sum($counts)],
    ];
    // keep floor / search when switching tabs
    $tabUrl = fn (string $t) => route('housekeeping.index', array_filter(
        ['status' => $t, 'floor' => $floor, 'search' => request('search')],
        fn ($v) => $v !== null && $v !== ''
    ));
    $emptyText = [
        'cleaning'    => ['No rooms need cleaning.', 'Rooms appear here automatically when a guest checks out.'],
        'available'   => ['No clean rooms here.', 'Rooms you mark clean show up in this tab.'],
        'maintenance' => ['No rooms in maintenance.', 'Use the Maintenance button on a room card to take it out of service.'],
        'all'         => ['No rooms match these filters.', 'Try another floor or search.'],
    ];
    $actionChip = [
        'checked_out' => 'bg-orange-50 text-orange-700', 'cleaned' => 'bg-emerald-50 text-emerald-700',
        'needs_cleaning' => 'bg-sky-50 text-sky-700', 'maintenance_started' => 'bg-amber-50 text-amber-700',
        'maintenance_ended' => 'bg-emerald-50 text-emerald-700',
    ];
@endphp

<x-page-hero eyebrow="Operations" title="Housekeeping" subtitle="Rooms are marked for cleaning at check-out. Mark them clean to make them bookable again." />

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Needs cleaning" :value="$counts['cleaning'] ?? 0" hint="not bookable yet" icon="sparkles" tone="slate" />
    <x-stat-card label="Clean" :value="$counts['available'] ?? 0" hint="ready for guests" icon="check" tone="green" />
    <x-stat-card label="Maintenance" :value="$counts['maintenance'] ?? 0" hint="out of service" icon="wrench" tone="amber" />
</div>

{{-- Filters: floor + search (GET form; the status tab is kept in a hidden field) --}}
<form method="GET" action="{{ route('housekeeping.index') }}" class="card mt-6 p-4" data-filter-form>
    <input type="hidden" name="status" value="{{ $tab }}">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_12rem_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="h_search" class="sr-only">Search room number</label>
            <input type="text" id="h_search" name="search" class="field !pl-12" placeholder="Search room number…" value="{{ request('search') }}" autocomplete="off" data-filter-text>
        </div>
        <div>
            <label for="h_floor" class="sr-only">Floor</label>
            <select id="h_floor" name="floor" class="field" data-filter-auto>
                <option value="">All Floors</option>
                @foreach ($floors as $f)
                    <option value="{{ $f }}" @selected($floor !== null && (int) $f === $floor)>Floor {{ $f }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('housekeeping.index') }}" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset</a>
    </div>
</form>

{{-- Status tabs --}}
<nav class="mt-6 flex flex-wrap gap-2" aria-label="Room status">
    @foreach ($tabs as $key => [$label, $n])
        <a href="{{ $tabUrl($key) }}" @if($tab === $key) aria-current="page" @endif
           class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold transition {{ $tab === $key ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-tint hover:text-primary' }}">
            {{ $label }}
            <span class="rounded-full px-2 py-0.5 text-[11px] {{ $tab === $key ? 'bg-white/20' : 'bg-tint text-primary' }}">{{ $n }}</span>
        </a>
    @endforeach
</nav>

{{-- Room cards --}}
<section class="mt-4" aria-label="Rooms">
    <div class="grid grid-cols-1 gap-4 min-[520px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
        @forelse ($rooms as $room)
            @php $l = $look[$room->status] ?? $look['available']; @endphp
            <article class="flex flex-col rounded-2xl p-4 ring-1 {{ $l['card'] }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-xs font-medium text-slate-500">Room</p>
                        <p class="text-2xl font-bold leading-tight">{{ $room->room_number }}</p>
                    </div>
                    <span class="chip {{ $l['chip'] }}"><span class="size-1.5 rounded-full {{ $l['dot'] }}"></span>{{ $l['label'] }}</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="chip bg-white/80 text-primary">{{ $room->roomType->type_name ?? 'N/A' }}</span>
                    <span class="text-slate-600">Floor {{ $room->floor }}</span>
                </div>

                <p class="mt-3 text-xs text-slate-600">
                    @if ($room->last_cleaned_at)
                        Last cleaned <b class="text-ink" title="{{ $room->last_cleaned_at->format('d M Y, H:i') }}">{{ $room->last_cleaned_at->diffForHumans() }}</b>
                        by <b class="text-ink">{{ $room->lastCleaner?->name ?? 'a former user' }}</b>
                    @else
                        No cleaning recorded yet.
                    @endif
                </p>

                <div class="mt-4 flex flex-wrap items-start gap-2 border-t border-black/5 pt-4">
                    @if ($room->status === 'cleaning')
                        <form action="{{ route('housekeeping.clean', $room) }}" method="POST" data-loading-form data-submit-label="Saving…">
                            @csrf
                            <button class="btn btn-primary !px-3 !py-2" data-submit><x-icon name="check" class="size-4" /><span data-submit-text>Mark clean</span></button>
                        </form>
                    @elseif ($room->status === 'available')
                        <form action="{{ route('housekeeping.dirty', $room) }}" method="POST" data-loading-form data-submit-label="Saving…">
                            @csrf
                            <button class="btn btn-soft !px-3 !py-2" data-submit><x-icon name="sparkles" class="size-4" /><span data-submit-text>Needs cleaning</span></button>
                        </form>
                    @elseif ($room->status === 'maintenance')
                        <form action="{{ route('housekeeping.service', $room) }}" method="POST" data-loading-form data-submit-label="Saving…">
                            @csrf
                            <button class="btn btn-primary !px-3 !py-2" data-submit><x-icon name="check" class="size-4" /><span data-submit-text>Back in service</span></button>
                        </form>
                    @endif

                    @if ($room->status !== 'maintenance')
                        {{-- optional note, then send to maintenance --}}
                        <details class="group w-full">
                            <summary class="btn btn-soft cursor-pointer list-none !px-3 !py-2"><x-icon name="wrench" class="size-4" />Maintenance</summary>
                            <form action="{{ route('housekeeping.maintenance', $room) }}" method="POST" class="mt-2 space-y-2" data-loading-form data-submit-label="Saving…">
                                @csrf
                                <label for="note_{{ $room->id }}" class="sr-only">Maintenance note</label>
                                <input type="text" id="note_{{ $room->id }}" name="note" maxlength="255" class="field !py-2 text-sm" placeholder="What needs fixing? (optional)">
                                <button class="btn btn-primary w-full !py-2" data-submit><span data-submit-text>Send to maintenance</span></button>
                            </form>
                        </details>
                    @endif
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-2xl bg-tint px-6 py-14 text-center text-slate-500">
                <p class="font-medium text-ink">{{ $emptyText[$tab][0] }}</p>
                <p class="mt-1 text-sm">{{ $emptyText[$tab][1] }}</p>
            </div>
        @endforelse
    </div>
    <div class="mt-4">{{ $rooms->links() }}</div>
</section>

{{-- Activity log: who did what, and when --}}
<section class="card mt-8 overflow-hidden" aria-labelledby="logTitle">
    <div class="p-6 pb-4">
        <h2 id="logTitle" class="text-lg font-bold">Recent activity</h2>
        <p class="text-sm text-slate-500">Every cleaning and maintenance change, newest first.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[40rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-3">When</th>
                    <th scope="col" class="px-6 py-3">Room</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                    <th scope="col" class="px-6 py-3">By</th>
                    <th scope="col" class="px-6 py-3">Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($log as $entry)
                    <tr class="border-t border-slate-100">
                        <td class="px-6 py-3 text-slate-600" title="{{ $entry->created_at->format('d M Y, H:i') }}">{{ $entry->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-3"><span class="chip bg-tint text-primary">{{ $entry->room->room_number }}</span></td>
                        <td class="px-6 py-3"><span class="chip {{ $actionChip[$entry->action] ?? 'bg-slate-100 text-slate-600' }}">{{ $entry->actionLabel() }}</span></td>
                        <td class="px-6 py-3 text-slate-600">{{ $entry->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-3 text-slate-600">{{ $entry->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-6 py-12 text-center text-slate-500">
                        <p class="font-medium text-ink">No activity yet.</p>
                        <p class="mt-1 text-sm">Check a guest out, or mark a room for cleaning, to see entries here.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-6 py-4">{{ $log->links() }}</div>
</section>

@endsection
