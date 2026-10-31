@extends('layouts.app')
@section('title', 'Reports')
@section('content')

@php
    $k = $report['kpi'];
    // keep the current range when switching day / month grouping or exporting
    $qs      = request()->only(['range', 'from', 'to', 'group']);
    $qs['range'] = $p['range'];
    if ($p['range'] === 'custom') { $qs['from'] = $p['from']->toDateString(); $qs['to'] = $p['to']->toDateString(); }
    $tabUrl  = fn (string $r) => route('reports.index', $r === 'custom'
        ? ['range' => 'custom', 'from' => $p['from']->toDateString(), 'to' => $p['to']->toDateString()]
        : ['range' => $r]);
    $maxBook = max(1, collect($report['series'])->max('bookings'));
    $maxRev  = max(1, collect($report['series'])->max(fn ($s) => abs($s['revenue'])));
    $maxType = max(1, $report['by_type']->max('value'));
    $maxMeth = max(1, $report['by_method']->max(fn ($m) => abs($m['net'])));
@endphp

<x-page-hero eyebrow="Admin" title="Reports"
    :subtitle="$report['from']->format('d M Y') . ($report['from']->eq($report['to']) ? '' : ' to ' . $report['to']->format('d M Y')) . ' · ' . $report['days'] . ' ' . \Illuminate\Support\Str::plural('day', $report['days'])">
    <x-slot:actions>
        <a href="{{ route('reports.csv', $qs) }}" class="btn btn-soft"><x-icon name="download" class="size-4" />CSV</a>
        <a href="{{ route('reports.pdf', $qs) }}" class="btn btn-soft"><x-icon name="download" class="size-4" />PDF</a>
        <button type="button" class="btn btn-soft" onclick="window.print()"><x-icon name="printer" class="size-4" />Print</button>
    </x-slot:actions>
</x-page-hero>

{{-- Date range --}}
<nav class="mt-6 flex flex-wrap items-center gap-2" aria-label="Date range">
    @foreach ($ranges as $key => $label)
        <a href="{{ $tabUrl($key) }}" @if($p['range'] === $key) aria-current="page" @endif
           class="inline-flex items-center rounded-full px-4 py-2 text-sm font-semibold transition {{ $p['range'] === $key ? 'bg-primary text-white shadow-md shadow-primary/25' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-tint hover:text-primary' }}">{{ $label }}</a>
    @endforeach
</nav>

@if ($p['range'] === 'custom')
    <form method="GET" action="{{ route('reports.index') }}" class="card mt-4 grid gap-3 p-4 sm:grid-cols-[1fr_1fr_auto]">
        <input type="hidden" name="range" value="custom">
        <div>
            <label for="r_from" class="mb-1 block text-sm font-medium text-slate-600">From</label>
            <input type="date" id="r_from" name="from" class="field" value="{{ $p['from']->toDateString() }}" required>
        </div>
        <div>
            <label for="r_to" class="mb-1 block text-sm font-medium text-slate-600">To</label>
            <input type="date" id="r_to" name="to" class="field" value="{{ $p['to']->toDateString() }}" required>
        </div>
        <div class="flex items-end"><button class="btn btn-primary w-full sm:w-auto">Show report</button></div>
    </form>
@endif

@if ($p['error'])
    <div class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 ring-1 ring-amber-200" role="status">{{ $p['error'] }}</div>
@endif

{{-- Headline numbers --}}
<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Revenue received" :value="\App\Support\HotelSettings::money($k['revenue'])" :hint="'Payments ' . \App\Support\HotelSettings::money($k['collected']) . ' − refunds ' . \App\Support\HotelSettings::money($k['refunded'])" icon="dollar" tone="green" />
    <x-stat-card label="Booked value" :value="\App\Support\HotelSettings::money($k['booked_value'])" :hint="'Arrivals in period' . ($k['tax'] > 0 ? ' · incl. tax ' . \App\Support\HotelSettings::money($k['tax']) : '')" icon="receipt" tone="primary" />
    <x-stat-card label="Occupancy" :value="rtrim(rtrim(number_format($k['occupancy'], 1), '0'), '.') . '%'" :hint="$k['occupied_nights'] . ' of ' . $k['available_nights'] . ' room-nights (' . $k['rooms'] . ' rooms)'" icon="door" tone="amber" />
    <x-stat-card label="Bookings" :value="$k['bookings']" :hint="$k['cancelled'] . ' cancelled · avg nightly rate ' . \App\Support\HotelSettings::money($k['adr'])" icon="calendar" tone="orange" />
</div>

{{-- Charts: bookings + revenue received --}}
<section class="card mt-6 p-6" aria-labelledby="seriesTitle">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="seriesTitle" class="text-lg font-bold">Bookings by {{ $report['group'] }}</h2>
            <p class="text-sm text-slate-500">Arrivals per {{ $report['group'] }}, and money received per {{ $report['group'] }}.</p>
        </div>
        <div class="inline-flex rounded-full bg-tint p-1 text-sm font-semibold" role="group" aria-label="Group by">
            @foreach (['day' => 'Day', 'month' => 'Month'] as $g => $gl)
                <a href="{{ request()->fullUrlWithQuery(['group' => $g]) }}" @if($report['group'] === $g) aria-current="true" @endif
                   class="rounded-full px-4 py-1.5 {{ $report['group'] === $g ? 'bg-white text-primary shadow-sm' : 'text-slate-500 hover:text-primary' }}">{{ $gl }}</a>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid gap-8 lg:grid-cols-2">
        @foreach ([['Bookings', 'bookings', $maxBook, 'bg-primary', false], ['Revenue received', 'revenue', $maxRev, 'bg-emerald-500', true]] as [$ct, $field, $max, $bar, $isMoney])
            <figure>
                <figcaption class="mb-2 text-sm font-semibold text-slate-600">{{ $ct }}</figcaption>
                <div class="flex h-44 items-end gap-px overflow-x-auto rounded-xl bg-tint/60 px-2 pt-3" role="img"
                     aria-label="{{ $ct }} by {{ $report['group'] }}">
                    @foreach ($report['series'] as $i => $s)
                        @php $val = $s[$field]; $h = $val > 0 ? max(3, round(abs($val) / $max * 100)) : 0; @endphp
                        <div class="group relative flex h-full min-w-[6px] flex-1 flex-col justify-end"
                             title="{{ $s['label'] }}: {{ $isMoney ? \App\Support\HotelSettings::money($val) : $val }}">
                            <div class="w-full rounded-t {{ $val < 0 ? 'bg-red-400' : $bar }}" style="height: {{ $h }}%"></div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-1 flex justify-between px-2 text-[11px] text-slate-500">
                    <span>{{ $report['series'][0]['label'] ?? '' }}</span>
                    <span>{{ end($report['series'])['label'] ?? '' }}</span>
                </div>
            </figure>
        @endforeach
    </div>

    <details class="mt-6">
        <summary class="cursor-pointer text-sm font-semibold text-primary">Show the table</summary>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[28rem] text-left text-sm">
                <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr><th class="px-4 py-2.5">{{ ucfirst($report['group']) }}</th><th class="px-4 py-2.5 text-right">Bookings</th><th class="px-4 py-2.5 text-right">Booked value</th><th class="px-4 py-2.5 text-right">Revenue received</th></tr>
                </thead>
                <tbody>
                    @foreach ($report['series'] as $s)
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-2.5">{{ $s['label'] }}</td>
                            <td class="px-4 py-2.5 text-right">{{ $s['bookings'] }}</td>
                            <td class="px-4 py-2.5 text-right">@money($s['value'])</td>
                            <td class="px-4 py-2.5 text-right">@money($s['revenue'])</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-2">
    {{-- By room type --}}
    <section class="card overflow-hidden" aria-labelledby="typeTitle">
        <div class="p-6 pb-3">
            <h2 id="typeTitle" class="text-lg font-bold">By room type</h2>
            <p class="text-sm text-slate-500">Bookings arriving in the period, not cancelled.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[26rem] text-left text-sm">
                <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr><th class="px-6 py-3">Room type</th><th class="px-3 py-3 text-right">Bookings</th><th class="px-3 py-3 text-right">Nights</th><th class="px-6 py-3 text-right">Value</th></tr>
                </thead>
                <tbody>
                    @forelse ($report['by_type'] as $t)
                        <tr class="border-t border-slate-100">
                            <td class="px-6 py-3">
                                <span class="font-semibold">{{ $t['label'] }}</span>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-tint"><div class="h-full rounded-full bg-primary" style="width: {{ round($t['value'] / $maxType * 100) }}%"></div></div>
                            </td>
                            <td class="px-3 py-3 text-right">{{ $t['bookings'] }}</td>
                            <td class="px-3 py-3 text-right">{{ $t['nights'] }}</td>
                            <td class="px-6 py-3 text-right font-semibold">@money($t['value'])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-slate-500">No bookings arrive in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Payments by method --}}
    <section class="card overflow-hidden" aria-labelledby="methodTitle">
        <div class="p-6 pb-3">
            <h2 id="methodTitle" class="text-lg font-bold">Payments by method</h2>
            <p class="text-sm text-slate-500">Money received in the period, net of refunds.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[26rem] text-left text-sm">
                <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                    <tr><th class="px-6 py-3">Method</th><th class="px-3 py-3 text-right">Payments</th><th class="px-3 py-3 text-right">Refunds</th><th class="px-6 py-3 text-right">Net</th></tr>
                </thead>
                <tbody>
                    @foreach ($report['by_method'] as $m)
                        <tr class="border-t border-slate-100">
                            <td class="px-6 py-3">
                                <span class="font-semibold">{{ $m['label'] }}</span>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-tint"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $m['net'] > 0 ? round($m['net'] / $maxMeth * 100) : 0 }}%"></div></div>
                            </td>
                            <td class="px-3 py-3 text-right">{{ $m['count'] }}</td>
                            <td class="px-3 py-3 text-right text-slate-500">@money($m['refunds'])</td>
                            <td class="px-6 py-3 text-right font-semibold">@money($m['net'])</td>
                        </tr>
                    @endforeach
                    <tr class="border-t-2 border-slate-200 bg-tint/50 font-bold">
                        <td class="px-6 py-3">Total</td>
                        <td class="px-3 py-3 text-right">{{ $report['by_method']->sum('count') }}</td>
                        <td class="px-3 py-3 text-right">@money($k['refunded'])</td>
                        <td class="px-6 py-3 text-right">@money($k['revenue'])</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>

<p class="mt-4 text-xs text-slate-500">
    <b>Revenue received</b> counts deposits and payments minus refunds, on the day the money was received.
    <b>Booked value</b> is the total of bookings that arrive in the period, whether or not they are paid yet.
    <b>Occupancy</b> is occupied room-nights divided by rooms × days.
</p>

@endsection
