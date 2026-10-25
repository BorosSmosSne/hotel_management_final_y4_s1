@extends('layouts.app')
@section('title', 'Rooms')
@section('content')

<x-page-hero eyebrow="Inventory" title="Rooms" subtitle="Search, filter and manage every room in the hotel.">
    <x-slot:actions>
        <a href="{{ route('rooms.create') }}" class="btn btn-white"><x-icon name="plus" class="size-5" />Add Room</a>
    </x-slot:actions>
</x-page-hero>

{{-- NEW stat cards. $statusCounts is an OPTIONAL controller variable, e.g. ['available' => 5, 'occupied' => 3, 'maintenance' => 2] (see notes).
     Nothing renders if it isn't passed, so the page works before the controller is touched. --}}
@isset($statusCounts)
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Total rooms" :value="array_sum($statusCounts)" icon="door" />
        <x-stat-card label="Available" :value="$statusCounts['available'] ?? 0" icon="check" tone="green" />
        <x-stat-card label="Occupied" :value="$statusCounts['occupied'] ?? 0" icon="users" tone="red" />
        <x-stat-card label="Maintenance" :value="$statusCounts['maintenance'] ?? 0" icon="wrench" tone="amber" />
    </div>
@endisset

{{-- CHANGED: styling only. IDs searchInput / typeFilter / statusFilter / resetBtn / tableContainer unchanged. --}}
<div class="card mt-6 p-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_14rem_14rem_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="searchInput" class="sr-only">Search room number</label>
            <input type="text" id="searchInput" class="field !pl-12"
                   placeholder="Search room number..." value="{{ request('search') }}" autocomplete="off">
        </div>
        <div>
            <label for="typeFilter" class="sr-only">Room type</label>
            <select id="typeFilter" class="field">
                <option value="">All Types</option>
                @foreach($roomTypes as $type)
                    <option value="{{ $type->id }}" {{ request('room_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->type_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="statusFilter" class="sr-only">Status</label>
            <select id="statusFilter" class="field">
                <option value="">All Status</option>
                @foreach(['available', 'occupied', 'maintenance'] as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <button id="resetBtn" type="button" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset Filters</button>
    </div>
</div>

<div id="tableContainer" class="mt-6">
    @include('rooms.partials.table')
</div>

<script>
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const tableContainer = document.getElementById('tableContainer');
    const resetBtn = document.getElementById('resetBtn');
    let searchTimeout;

    function buildUrl() {
        const params = new URLSearchParams();
        if (searchInput.value) params.set('search', searchInput.value);
        if (typeFilter.value) params.set('room_type_id', typeFilter.value);
        if (statusFilter.value) params.set('status', statusFilter.value);
        return `{{ route('rooms.index') }}?${params.toString()}`;
    }

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
            const url = buildUrl();
            fetchResults(url);
            window.history.pushState({}, '', url);
        }, 300);
    });

    typeFilter.addEventListener('change', function () {
        const url = buildUrl();
        fetchResults(url);
        window.history.pushState({}, '', url);
    });

    statusFilter.addEventListener('change', function () {
        const url = buildUrl();
        fetchResults(url);
        window.history.pushState({}, '', url);
    });

    resetBtn.addEventListener('click', function () {
        searchInput.value = '';
        typeFilter.value = '';
        statusFilter.value = '';
        const url = `{{ route('rooms.index') }}`;
        fetchResults(url);
        window.history.pushState({}, '', url);
    });

    bindPaginationLinks();
</script>

@endsection
