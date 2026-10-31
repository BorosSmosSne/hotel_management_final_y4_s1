@extends('layouts.app')
@section('title', 'Payments')
@section('content')

@php
    $isAdmin = auth()->user()->isAdmin();
    $typeChip = ['deposit' => 'bg-sky-50 text-sky-700', 'payment' => 'bg-emerald-50 text-emerald-700', 'refund' => 'bg-red-50 text-red-700'];
@endphp

<x-page-hero eyebrow="Billing" title="Payments" subtitle="Every deposit, payment and refund. Record new ones from a booking." />

<div class="mt-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Collected" :value="'$' . number_format($collected, 2)" hint="deposits + payments" icon="dollar" tone="green" />
    <x-stat-card label="Refunded" :value="'$' . number_format($refunded, 2)" icon="refresh" tone="red" />
    <x-stat-card label="Net" :value="'$' . number_format($collected - $refunded, 2)" hint="for the filters below" icon="check" />
</div>

<form method="GET" action="{{ route('payments.index') }}" class="card mt-6 p-4" data-filter-form>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_10rem_10rem_10rem_10rem_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="p_search" class="sr-only">Search payments</label>
            <input type="text" id="p_search" name="search" class="field !pl-12" placeholder="Guest, room or reference…" value="{{ request('search') }}" autocomplete="off" data-filter-text>
        </div>
        <div>
            <label for="p_type" class="sr-only">Type</label>
            <select id="p_type" name="type" class="field" data-filter-auto>
                <option value="">All Types</option>
                @foreach (\App\Models\Payment::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div>
            <label for="p_method" class="sr-only">Method</label>
            <select id="p_method" name="method" class="field" data-filter-auto>
                <option value="">All Methods</option>
                @foreach (\App\Models\Payment::METHODS as $k => $v)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div><label for="p_from" class="sr-only">From</label><input type="date" id="p_from" name="from" class="field" value="{{ request('from') }}" title="From date" data-filter-auto></div>
        <div><label for="p_to" class="sr-only">To</label><input type="date" id="p_to" name="to" class="field" value="{{ request('to') }}" title="To date" data-filter-auto></div>
        <a href="{{ route('payments.index') }}" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset</a>
    </div>
</form>

<div class="card mt-6 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[60rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-4">Date</th>
                    <th scope="col" class="px-6 py-4">Guest / booking</th>
                    <th scope="col" class="px-6 py-4">Type</th>
                    <th scope="col" class="px-6 py-4">Method</th>
                    <th scope="col" class="px-6 py-4">Reference</th>
                    <th scope="col" class="px-6 py-4 text-right">Amount</th>
                    <th scope="col" class="px-6 py-4">Received by</th>
                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $pay)
                    <tr class="border-t border-slate-100 transition hover:bg-tint/40">
                        <td class="px-6 py-4 text-slate-600">{{ $pay->paid_at->format('d M Y') }}<span class="block text-xs text-slate-500">{{ $pay->paid_at->format('H:i') }}</span></td>
                        <td class="px-6 py-4">
                            <a href="{{ route('bookings.show', $pay->booking_id) }}" class="font-semibold hover:text-primary">{{ $pay->booking->guest_name }}</a>
                            <span class="block text-xs text-slate-500">Booking #{{ $pay->booking_id }} · Room {{ $pay->booking->room->room_number }}</span>
                        </td>
                        <td class="px-6 py-4"><span class="chip {{ $typeChip[$pay->type] ?? 'bg-slate-100 text-slate-600' }}">{{ $pay->typeLabel() }}</span></td>
                        <td class="px-6 py-4 text-slate-600">{{ $pay->methodLabel() }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $pay->reference ?: '—' }}</td>
                        <td class="px-6 py-4 text-right font-bold {{ $pay->isRefund() ? 'text-red-600' : '' }}">{{ $pay->isRefund() ? '−' : '' }}${{ number_format($pay->amount, 2) }}</td>
                        <td class="px-6 py-4 text-slate-600">{{ $pay->receiver?->name ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('invoices.show', $pay->booking_id) }}" target="_blank" rel="noopener" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Open invoice" title="Invoice"><x-icon name="receipt" class="size-4" /></a>
                                @if ($isAdmin)
                                    <a href="{{ route('payments.edit', $pay) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit payment" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                    <form action="{{ route('payments.destroy', $pay) }}" method="POST" class="inline"
                                          data-confirm-delete data-heading="Delete this payment?"
                                          data-title="${{ number_format($pay->amount, 2) }} · {{ $pay->typeLabel() }} · {{ $pay->booking->guest_name }}"
                                          data-detail="The booking balance is recalculated.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete payment" title="Delete"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-6 py-14 text-center text-slate-500">
                        <p class="font-medium text-ink">No payments found.</p>
                        <p class="mt-1 text-sm">Open a booking on <a href="{{ route('bookings.index') }}" class="font-semibold text-primary hover:underline">Room Booking</a> to record the first one.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
        <p class="text-sm text-slate-500">@if ($payments->total())Showing <b class="text-ink">{{ $payments->firstItem() }}</b> to <b class="text-ink">{{ $payments->lastItem() }}</b> of <b class="text-ink">{{ $payments->total() }}</b> payments @endif</p>
        {{ $payments->links() }}
    </div>
</div>

@endsection
