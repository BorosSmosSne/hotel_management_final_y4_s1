@extends('layouts.app')
@section('title', 'Booking #' . $booking->id)
@section('content')

@php
    $isAdmin = auth()->user()->isAdmin();
    $bLook = [
        'confirmed'   => ['Reserved',    'bg-orange-50 text-orange-700'],
        'checked_in'  => ['Checked in',  'bg-red-50 text-red-700'],
        'checked_out' => ['Checked out', 'bg-slate-100 text-slate-600'],
        'cancelled'   => ['Cancelled',   'bg-slate-100 text-slate-500'],
    ];
    [$sLabel, $sChip] = $bLook[$booking->status] ?? [ucfirst($booking->status), 'bg-slate-100 text-slate-600'];

    $paid    = $booking->netPaid();
    $balance = $booking->balanceDue();
    $pStatus = $booking->paymentStatus();
    $pLook   = ['unpaid' => ['Unpaid', 'bg-red-50 text-red-700'], 'partial' => ['Partial', 'bg-amber-50 text-amber-700'], 'paid' => ['Paid', 'bg-emerald-50 text-emerald-700']];
    [$pLabel, $pChip] = $pLook[$pStatus];
    $typeChip = ['deposit' => 'bg-sky-50 text-sky-700', 'payment' => 'bg-emerald-50 text-emerald-700', 'refund' => 'bg-red-50 text-red-700'];
    $cancelled = $booking->status === \App\Models\Booking::CANCELLED;
@endphp

<x-page-hero :eyebrow="'Booking #' . $booking->id" :title="$booking->guest_name"
             :subtitle="'Room ' . $booking->room->room_number . ' · ' . ($booking->room->roomType->type_name ?? 'N/A') . ' · ' . $booking->check_in->format('d M') . ' → ' . $booking->check_out->format('d M Y')">
    <x-slot:actions>
        <a href="{{ route('bookings.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Bookings</a>
        <a href="{{ route('invoices.show', $booking) }}" target="_blank" rel="noopener" class="btn btn-white"><x-icon name="receipt" class="size-5" />Invoice</a>
        <a href="{{ route('invoices.pdf', $booking) }}" class="btn btn-glass"><x-icon name="download" class="size-5" />PDF</a>
    </x-slot:actions>
</x-page-hero>

{{-- Money summary --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total" :value="'$' . number_format((float) $booking->total_price, 2)" :hint="$booking->nights . ' ' . \Illuminate\Support\Str::plural('night', $booking->nights) . ' × $' . number_format((float) $booking->price_per_night, 2)" icon="calendar" />
    <x-stat-card label="Paid" :value="'$' . number_format($paid, 2)" hint="deposits + payments − refunds" icon="check" tone="green" />
    <x-stat-card label="Balance due" :value="'$' . number_format($balance, 2)" :hint="$balance > 0 ? 'still to collect' : ($paid > (float) $booking->total_price ? 'overpaid' : 'settled')" icon="dollar" :tone="$balance > 0 ? 'red' : 'green'" />
    <div class="card flex flex-col justify-center p-5">
        <p class="text-sm text-slate-500">Payment status</p>
        <p class="mt-2"><span class="chip {{ $pChip }} !px-4 !py-1.5 !text-sm">{{ $pLabel }}</span></p>
    </div>
</div>

<div class="mt-6 grid gap-6 lg:grid-cols-3">

    {{-- Booking details --}}
    <aside class="card h-fit p-6 lg:col-span-1">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold">Details</h2>
            <span class="chip {{ $sChip }}">{{ $sLabel }}</span>
        </div>
        <dl class="mt-5 space-y-3 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Guest</dt>
                <dd class="text-right font-medium">@if ($booking->guest)<a href="{{ route('guests.show', $booking->guest) }}" class="text-primary hover:underline">{{ $booking->guest_name }}</a>@else{{ $booking->guest_name }}@endif</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $booking->phone }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">ID / passport</dt><dd class="font-medium">{{ $booking->id_number }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Room</dt><dd class="font-medium">{{ $booking->room->room_number }} · Floor {{ $booking->room->floor }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Guests</dt><dd class="font-medium">{{ $booking->guests }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Check-in</dt><dd class="font-medium">{{ $booking->check_in->format('d M Y') }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Check-out</dt><dd class="font-medium">{{ $booking->check_out->format('d M Y') }}</dd></div>
            @if ($booking->notes)<div><dt class="text-slate-500">Notes</dt><dd class="mt-1 whitespace-pre-line rounded-xl bg-tint p-3 text-slate-700">{{ $booking->notes }}</dd></div>@endif
        </dl>

        {{-- Check-in / out: Admin + Staff.  Edit: Admin only. --}}
        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
            @if ($booking->canCheckIn())
                <form action="{{ route('bookings.check-in', $booking) }}" method="POST" data-loading-form>@csrf
                    <button class="btn btn-soft" data-submit><x-icon name="login" class="size-4" /><span data-submit-text>Check in</span></button></form>
            @endif
            @if ($booking->canCheckOut())
                <form action="{{ route('bookings.check-out', $booking) }}" method="POST" data-loading-form>@csrf
                    <button class="btn btn-primary" data-submit><x-icon name="logout" class="size-4" /><span data-submit-text>Check out</span></button></form>
            @endif
            @can('update', $booking)
                @if ($booking->isEditable())
                    <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-soft"><x-icon name="pencil" class="size-4" />Edit</a>
                @endif
            @endcan
        </div>
    </aside>

    <div class="space-y-6 lg:col-span-2">

        {{-- Payments list --}}
        <section class="card overflow-hidden" aria-labelledby="payTitle">
            <div class="p-6 pb-4">
                <h2 id="payTitle" class="text-lg font-bold">Payments</h2>
                <p class="text-sm text-slate-500">Everything received or refunded for this booking.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[34rem] text-left text-sm">
                    <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th scope="col" class="px-6 py-3">Date</th>
                            <th scope="col" class="px-6 py-3">Type</th>
                            <th scope="col" class="px-6 py-3">Method</th>
                            <th scope="col" class="px-6 py-3">Reference</th>
                            <th scope="col" class="px-6 py-3 text-right">Amount</th>
                            @if ($isAdmin)<th scope="col" class="px-6 py-3 text-right">Actions</th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($booking->payments->sortBy('paid_at') as $pay)
                            <tr class="border-t border-slate-100">
                                <td class="px-6 py-3 text-slate-600">{{ $pay->paid_at->format('d M Y, H:i') }}<span class="block text-xs text-slate-500">by {{ $pay->receiver?->name ?? '—' }}</span></td>
                                <td class="px-6 py-3"><span class="chip {{ $typeChip[$pay->type] ?? 'bg-slate-100 text-slate-600' }}">{{ $pay->typeLabel() }}</span></td>
                                <td class="px-6 py-3 text-slate-600">{{ $pay->methodLabel() }}</td>
                                <td class="px-6 py-3 text-slate-600">{{ $pay->reference ?: '—' }}</td>
                                <td class="px-6 py-3 text-right font-bold {{ $pay->isRefund() ? 'text-red-600' : '' }}">{{ $pay->isRefund() ? '−' : '' }}${{ number_format($pay->amount, 2) }}</td>
                                @if ($isAdmin)
                                    <td class="px-6 py-3">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('payments.edit', $pay) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit payment" title="Edit"><x-icon name="pencil" class="size-4" /></a>
                                            <form action="{{ route('payments.destroy', $pay) }}" method="POST" class="inline"
                                                  data-confirm-delete data-heading="Delete this payment?"
                                                  data-title="${{ number_format($pay->amount, 2) }} · {{ $pay->typeLabel() }}"
                                                  data-detail="The balance due is recalculated.">
                                                @csrf @method('DELETE')
                                                <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete payment" title="Delete"><x-icon name="trash" class="size-4" /></button>
                                            </form>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isAdmin ? 6 : 5 }}" class="px-6 py-10 text-center text-slate-500">
                                <p class="font-medium text-ink">No payments yet.</p>
                                <p class="mt-1 text-sm">Record a deposit or payment below.</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Record a payment --}}
        <section id="record-payment" class="card scroll-mt-24 p-6 sm:p-8" aria-labelledby="recTitle">
            <h2 id="recTitle" class="text-lg font-bold">Record a payment</h2>

            @if ($cancelled && ! $isAdmin)
                <p class="mt-2 rounded-xl bg-tint p-4 text-sm text-slate-600">This booking is cancelled, so no new payments can be recorded. Ask an administrator if a refund is needed.</p>
            @else
                <p class="text-sm text-slate-500">
                    @if ($cancelled) Booking is cancelled: only a refund can be recorded.
                    @elseif ($balance > 0) The amount is filled in with the balance due. Change it for a deposit or part payment.
                    @else This booking is fully paid. @endif
                </p>

                <div class="mt-5"><x-error-summary /></div>

                <form method="POST" action="{{ route('bookings.payments.store', $booking) }}" class="mt-2" data-loading-form data-submit-label="Saving…">
                    @csrf
                    @include('payments.partials.fields', ['payment' => null, 'booking' => $booking])
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="btn btn-primary" data-submit><x-icon name="check" class="size-4" /><span data-submit-text>Record payment</span></button>
                    </div>
                </form>
            @endif
        </section>
    </div>
</div>

@endsection
