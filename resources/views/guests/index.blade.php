@extends('layouts.app')
@section('title', 'Guests')
@section('content')

<x-page-hero eyebrow="Front desk" title="Guests" subtitle="Everyone who has stayed, or will stay, at the hotel.">
    <x-slot:actions>
        <a href="{{ route('guests.create') }}" class="btn btn-white"><x-icon name="plus" class="size-5" />Add Guest</a>
    </x-slot:actions>
</x-page-hero>

<form method="GET" action="{{ route('guests.index') }}" class="card mt-6 p-4" data-filter-form>
    <div class="grid gap-3 sm:grid-cols-[1fr_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="g_search" class="sr-only">Search guests</label>
            <input type="text" id="g_search" name="search" class="field !pl-12" placeholder="Search name, phone or ID number…"
                   value="{{ request('search') }}" autocomplete="off" data-filter-text>
        </div>
        <a href="{{ route('guests.index') }}" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset</a>
    </div>
</form>

<div class="card mt-6 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[48rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-4">Guest</th>
                    <th scope="col" class="px-6 py-4">Phone</th>
                    <th scope="col" class="px-6 py-4">ID / passport</th>
                    <th scope="col" class="px-6 py-4">Nationality</th>
                    <th scope="col" class="px-6 py-4">Stays</th>
                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($guests as $g)
                    @php $initials = \Illuminate\Support\Str::of($g->name)->explode(' ')->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode(''); @endphp
                    <tr class="border-t border-slate-100 transition hover:bg-tint/40">
                        <td class="px-6 py-4">
                            <a href="{{ route('guests.show', $g) }}" class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-tint-2 text-sm font-bold text-primary" aria-hidden="true">{{ $initials }}</span>
                                <span class="min-w-0">
                                    <span class="block font-semibold hover:text-primary">{{ $g->name }}</span>
                                    @if ($g->email)<span class="block text-xs text-slate-500">{{ $g->email }}</span>@endif
                                </span>
                            </a>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $g->phone }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $g->id_number }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $g->nationality ?: '—' }}</td>
                        <td class="px-6 py-4"><span class="chip bg-tint text-primary">{{ $g->bookings_count }}</span></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('guests.show', $g) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="View {{ $g->name }}" title="View"><x-icon name="eye" class="size-4" /></a>
                                <a href="{{ route('guests.edit', $g) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit {{ $g->name }}" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                @if (auth()->user()->isAdmin())
                                    <form action="{{ route('guests.destroy', $g) }}" method="POST" class="inline"
                                          data-confirm-delete data-heading="Delete this guest?"
                                          data-title="{{ $g->name }} ({{ $g->id_number }})"
                                          data-detail="Their bookings are kept, but are no longer linked to a guest profile.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete {{ $g->name }}" title="Delete"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-slate-500">
                        @if (request()->filled('search'))
                            <p class="font-medium text-ink">No guests match “{{ request('search') }}”.</p>
                            <p class="mt-1 text-sm">Check the spelling, or <a href="{{ route('guests.create') }}" class="font-semibold text-primary hover:underline">add a new guest</a>.</p>
                        @else
                            <p class="font-medium text-ink">No guests yet.</p>
                            <p class="mt-1 text-sm">Guests are added here, or automatically when you make a booking.</p>
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
        <p class="text-sm text-slate-500">
            @if ($guests->total())Showing <b class="text-ink">{{ $guests->firstItem() }}</b> to <b class="text-ink">{{ $guests->lastItem() }}</b> of <b class="text-ink">{{ $guests->total() }}</b> guests @endif
        </p>
        {{ $guests->links() }}
    </div>
</div>

@endsection
