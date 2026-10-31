@extends('layouts.app')
@section('title', 'Activity Log')
@section('content')

@php
    $chip = [
        'created'      => 'bg-emerald-50 text-emerald-700',
        'updated'      => 'bg-sky-50 text-sky-700',
        'deleted'      => 'bg-red-50 text-red-700',
        'login'        => 'bg-indigo-50 text-indigo-700',
        'logout'       => 'bg-slate-100 text-slate-600',
        'login_failed' => 'bg-amber-50 text-amber-700',
    ];
    $filtered = request()->hasAny(['user_id', 'action', 'from', 'to', 'search']);
@endphp

<x-page-hero eyebrow="Admin" title="Activity Log" subtitle="Who did what, and when. This list is read-only: entries cannot be edited or deleted." />

<form method="GET" action="{{ route('activity-log.index') }}" class="card mt-6 p-4">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_12rem_12rem_10rem_10rem_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="a_search" class="sr-only">Search the description</label>
            <input type="text" id="a_search" name="search" class="field !pl-12" placeholder="Search description…" value="{{ request('search') }}" autocomplete="off">
        </div>
        <div>
            <label for="a_user" class="sr-only">User</label>
            <select id="a_user" name="user_id" class="field">
                <option value="">All users</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" @selected((string) request('user_id') === (string) $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="a_action" class="sr-only">Action</label>
            <select id="a_action" name="action" class="field">
                <option value="">All actions</option>
                @foreach ($actions as $key => $label)
                    <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="a_from" class="sr-only">From date</label>
            <input type="date" id="a_from" name="from" class="field" value="{{ request('from') }}" title="From">
        </div>
        <div>
            <label for="a_to" class="sr-only">To date</label>
            <input type="date" id="a_to" name="to" class="field" value="{{ request('to') }}" title="To">
        </div>
        <div class="flex gap-2">
            <button class="btn btn-primary">Filter</button>
            @if ($filtered)<a href="{{ route('activity-log.index') }}" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset</a>@endif
        </div>
    </div>
</form>

<section class="card mt-6 overflow-hidden" aria-label="Activity">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[52rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-3">When</th>
                    <th scope="col" class="px-6 py-3">User</th>
                    <th scope="col" class="px-6 py-3">Action</th>
                    <th scope="col" class="px-6 py-3">Record</th>
                    <th scope="col" class="px-6 py-3">Description</th>
                    <th scope="col" class="px-6 py-3">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="border-t border-slate-100 align-top">
                        <td class="whitespace-nowrap px-6 py-3 text-slate-600" title="{{ $log->created_at->diffForHumans() }}">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                        <td class="px-6 py-3">{{ $log->user?->name ?? 'System / not signed in' }}</td>
                        <td class="px-6 py-3"><span class="chip {{ $chip[$log->action] ?? 'bg-slate-100 text-slate-600' }}">{{ $log->actionLabel() }}</span></td>
                        <td class="whitespace-nowrap px-6 py-3 text-slate-600">{{ $log->model ? $log->model . ($log->model_id ? ' #' . $log->model_id : '') : '—' }}</td>
                        <td class="px-6 py-3 text-slate-700">{{ $log->description }}</td>
                        <td class="whitespace-nowrap px-6 py-3 text-xs text-slate-500">{{ $log->ip ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-slate-500">
                        <p class="font-medium text-ink">{{ $filtered ? 'No activity matches these filters.' : 'No activity yet.' }}</p>
                        <p class="mt-1 text-sm">{{ $filtered ? 'Try another user, action or date range.' : 'Bookings, rooms, users, payments and sign-ins appear here automatically.' }}</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-6 py-4">{{ $logs->links() }}</div>
</section>

@endsection
