@extends('layouts.app')
@section('title', $guest->name)
@section('content')

@php
    $initials = \Illuminate\Support\Str::of($guest->name)->explode(' ')->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $bLook = [
        'confirmed'   => ['Reserved',    'bg-orange-50 text-orange-700'],
        'checked_in'  => ['Checked in',  'bg-red-50 text-red-700'],
        'checked_out' => ['Checked out', 'bg-slate-100 text-slate-600'],
        'cancelled'   => ['Cancelled',   'bg-slate-100 text-slate-500'],
    ];
    $pLook = [
        'unpaid'  => ['Unpaid',  'bg-red-50 text-red-700'],
        'partial' => ['Partial', 'bg-amber-50 text-amber-700'],
        'paid'    => ['Paid',    'bg-emerald-50 text-emerald-700'],
    ];
@endphp

<x-page-hero eyebrow="Guest profile" :title="$guest->name" :subtitle="'ID ' . $guest->id_number">
    <x-slot:actions>
        <a href="{{ route('guests.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />All guests</a>
        <a href="{{ route('guests.edit', $guest) }}" class="btn btn-white"><x-icon name="pencil" class="size-5" />Edit</a>
    </x-slot:actions>
</x-page-hero>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <aside class="card h-fit p-6 lg:col-span-1">
        <div class="flex items-center gap-4">
            <span class="grid size-14 shrink-0 place-items-center rounded-full bg-tint-2 text-lg font-bold text-primary" aria-hidden="true">{{ $initials }}</span>
            <div class="min-w-0">
                <h2 class="truncate text-lg font-bold">{{ $guest->name }}</h2>
                <p class="text-sm text-slate-500">Guest since {{ $guest->created_at?->format('d M Y') }}</p>
            </div>
        </div>

        <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $guest->phone }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Email</dt><dd class="break-all text-right font-medium">{{ $guest->email ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">ID / passport</dt><dd class="font-medium">{{ $guest->id_number }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Nationality</dt><dd class="font-medium">{{ $guest->nationality ?: '—' }}</dd></div>
            <div><dt class="text-slate-500">Address</dt><dd class="mt-1 font-medium">{{ $guest->address ?: '—' }}</dd></div>
            @if ($guest->notes)
                <div><dt class="text-slate-500">Notes</dt><dd class="mt-1 whitespace-pre-line rounded-xl bg-tint p-3 text-slate-700">{{ $guest->notes }}</dd></div>
            @endif
        </dl>
    </aside>

    <div class="space-y-6 lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Stays" :value="$stayCount" hint="excluding cancelled" icon="calendar" />
            <x-stat-card label="Nights" :value="$nights" icon="bed" />
            <x-stat-card label="Total billed" :value="'$' . number_format($billed, 2)" icon="dollar" tone="green" />
        </div>

        <section class="card overflow-hidden" aria-labelledby="historyTitle">
            <div class="p-6 pb-4">
                <h2 id="historyTitle" class="text-lg font-bold">Booking history</h2>
                <p class="text-sm text-slate-500">Newest stays first.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-left text-sm">
                    <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                        <tr>
                            <th scope="col" class="px-6 py-3">Room</th>
                            <th scope="col" class="px-6 py-3">Stay</th>
                            <th scope="col" class="px-6 py-3">Total</th>
                            <th scope="col" class="px-6 py-3">Payment</th>
                            <th scope="col" class="px-6 py-3">Status</th>
                            <th scope="col" class="px-6 py-3 text-right"><span class="sr-only">Open</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bookings as $bk)
                            @php
                                [$sLabel, $sChip] = $bLook[$bk->status] ?? [ucfirst($bk->status), 'bg-slate-100 text-slate-600'];
                                [$pLabel, $pChip] = $pLook[$bk->paymentStatus()];
                            @endphp
                            <tr class="border-t border-slate-100 transition hover:bg-tint/40">
                                <td class="px-6 py-4"><span class="chip bg-tint text-primary">{{ $bk->room->room_number }}</span> <span class="ml-1 text-xs text-slate-500">{{ $bk->room->roomType->type_name ?? '' }}</span></td>
                                <td class="px-6 py-4 text-slate-600">{{ $bk->check_in->format('d M') }} → {{ $bk->check_out->format('d M Y') }}<span class="block text-xs text-slate-500">{{ $bk->nights }} {{ \Illuminate\Support\Str::plural('night', $bk->nights) }}</span></td>
                                <td class="px-6 py-4 font-bold">${{ number_format($bk->total_price, 2) }}</td>
                                <td class="px-6 py-4"><span class="chip {{ $pChip }}">{{ $pLabel }}</span></td>
                                <td class="px-6 py-4"><span class="chip {{ $sChip }}">{{ $sLabel }}</span></td>
                                <td class="px-6 py-4 text-right"><a href="{{ route('bookings.show', $bk) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Open booking #{{ $bk->id }}" title="Open"><x-icon name="eye" class="size-4" /></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <p class="font-medium text-ink">No bookings yet.</p>
                                <p class="mt-1 text-sm">Pick an available room on <a href="{{ route('bookings.index') }}" class="font-semibold text-primary hover:underline">Room Booking</a> and search for this guest.</p>
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-6 py-4">{{ $bookings->links() }}</div>
        </section>
    </div>
</div>

@endsection
