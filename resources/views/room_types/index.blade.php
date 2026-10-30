@extends('layouts.app')
@section('title', 'Room Types')
@section('content')

<x-page-hero eyebrow="Pricing and capacity" title="Room Types" subtitle="Configure room categories, nightly rates and guest capacity.">
    <x-slot:actions>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('room-types.create') }}" class="btn btn-white"><x-icon name="plus" class="size-5" />Add Room Type</a>
        @endif
    </x-slot:actions>
</x-page-hero>

{{-- NEW stat cards. $roomTypes->total() already exists; $avgRate and $totalRooms are OPTIONAL controller variables (see notes). --}}
<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Room types" :value="$roomTypes->total()" icon="grid" />
    @isset($avgRate)
        <x-stat-card label="Average base rate" :value="'$' . number_format($avgRate, 0)" hint="per night" icon="dollar" />
    @endisset
    @isset($totalRooms)
        <x-stat-card label="Rooms assigned" :value="$totalRooms" icon="door" />
    @endisset
</div>

{{-- CHANGED: styling only. IDs searchInput / tableContainer unchanged (used by the script below). --}}
<div class="card mt-6 p-4">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-60 flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="searchInput" class="sr-only">Search by type name</label>
            <input type="text" id="searchInput" class="field !pl-12"
                   placeholder="Search by type name..." value="{{ request('search') }}" autocomplete="off">
        </div>
        <button type="button" id="resetBtn" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset</button>
    </div>
</div>

<div id="tableContainer" class="mt-6">
    @include('room_types.partials.table')
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const tableContainer = document.getElementById('tableContainer');
    const resetBtn = document.getElementById('resetBtn');
    let searchTimeout;

    function fetchResults(url) {
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.text())
        .then(html => {
            tableContainer.innerHTML = html;
            bindPaginationLinks();
        });
    }

    function bindPaginationLinks() {
        // CHANGED: selector was '.pagination a' (Bootstrap); now matches the new pagination view
        tableContainer.querySelectorAll('[data-pagination] a').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                fetchResults(this.href);
                window.history.pushState({}, '', this.href);
            });
        });
    }

    searchInput.addEventListener('keyup', function () {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const url = `{{ route('room-types.index') }}?search=${encodeURIComponent(searchInput.value)}`;
            fetchResults(url);
            window.history.pushState({}, '', url);
        }, 300); // waits 300ms after typing stops
    });

    // NEW (UI only): Reset clears the search box and reloads the full list
    resetBtn.addEventListener('click', function () {
        searchInput.value = '';
        const url = `{{ route('room-types.index') }}`;
        fetchResults(url);
        window.history.pushState({}, '', url);
    });

    bindPaginationLinks();
</script>

@endsection
