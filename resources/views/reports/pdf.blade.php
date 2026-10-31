{{-- Report PDF. Plain tables + inline CSS so dompdf renders it. DejaVu Sans covers currency symbols such as € and ฿. --}}
@php
    $k = $report['kpi'];
    $money = fn ($n) => \App\Support\HotelSettings::money($n);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Report · {{ $hotel['name'] }}</title>
    <style>
        body { font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 11px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #eef2ff; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #475569; }
        td { border-bottom: 1px solid #e2e8f0; }
        .r { text-align: right; }
        .muted { color: #64748b; }
        h1 { margin: 0; font-size: 20px; }
        h2 { margin: 22px 0 6px; font-size: 13px; }
        .logo { height: 42px; max-width: 140px; }
        .kpi td { border: 1px solid #e2e8f0; padding: 10px; width: 25%; }
        .kpi b { display: block; font-size: 16px; margin-top: 3px; }
        @page { margin: 16mm; }
    </style>
</head>
<body>

<table>
    <tr>
        <td style="border:0; padding-left:0">
            @if ($logo)<img src="{{ $logo }}" class="logo" alt=""><br>@endif
            <b>{{ $hotel['name'] }}</b>
        </td>
        <td class="r" style="border:0">
            <h1>Report</h1>
            <div class="muted">{{ $report['from']->format('d M Y') }} – {{ $report['to']->format('d M Y') }} ({{ $report['days'] }} {{ \Illuminate\Support\Str::plural('day', $report['days']) }})</div>
            <div class="muted">Generated {{ now()->format('d M Y H:i') }}</div>
        </td>
    </tr>
</table>

<table class="kpi" style="margin-top:14px">
    <tr>
        <td><span class="muted">Revenue received</span><b>{{ $money($k['revenue']) }}</b><span class="muted">{{ $money($k['collected']) }} paid − {{ $money($k['refunded']) }} refunds</span></td>
        <td><span class="muted">Booked value</span><b>{{ $money($k['booked_value']) }}</b><span class="muted">{{ $k['tax'] > 0 ? 'incl. tax ' . $money($k['tax']) : 'arrivals in period' }}</span></td>
        <td><span class="muted">Occupancy</span><b>{{ rtrim(rtrim(number_format($k['occupancy'], 1), '0'), '.') }}%</b><span class="muted">{{ $k['occupied_nights'] }} of {{ $k['available_nights'] }} room-nights</span></td>
        <td><span class="muted">Bookings</span><b>{{ $k['bookings'] }}</b><span class="muted">{{ $k['cancelled'] }} cancelled</span></td>
    </tr>
</table>

<h2>Bookings by {{ $report['group'] }}</h2>
<table>
    <thead><tr><th>{{ ucfirst($report['group']) }}</th><th class="r">Bookings</th><th class="r">Booked value</th><th class="r">Revenue received</th></tr></thead>
    <tbody>
        @foreach ($report['series'] as $s)
            <tr><td>{{ $s['label'] }}</td><td class="r">{{ $s['bookings'] }}</td><td class="r">{{ $money($s['value']) }}</td><td class="r">{{ $money($s['revenue']) }}</td></tr>
        @endforeach
    </tbody>
</table>

<h2>By room type</h2>
<table>
    <thead><tr><th>Room type</th><th class="r">Bookings</th><th class="r">Nights</th><th class="r">Booked value</th></tr></thead>
    <tbody>
        @forelse ($report['by_type'] as $t)
            <tr><td>{{ $t['label'] }}</td><td class="r">{{ $t['bookings'] }}</td><td class="r">{{ $t['nights'] }}</td><td class="r">{{ $money($t['value']) }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted">No bookings arrive in this period.</td></tr>
        @endforelse
    </tbody>
</table>

<h2>Payments by method</h2>
<table>
    <thead><tr><th>Method</th><th class="r">Payments</th><th class="r">Received</th><th class="r">Refunded</th><th class="r">Net</th></tr></thead>
    <tbody>
        @foreach ($report['by_method'] as $m)
            <tr><td>{{ $m['label'] }}</td><td class="r">{{ $m['count'] }}</td><td class="r">{{ $money($m['in']) }}</td><td class="r">{{ $money($m['refunds']) }}</td><td class="r">{{ $money($m['net']) }}</td></tr>
        @endforeach
        <tr><td><b>Total</b></td><td class="r"><b>{{ $report['by_method']->sum('count') }}</b></td><td class="r"><b>{{ $money($k['collected']) }}</b></td><td class="r"><b>{{ $money($k['refunded']) }}</b></td><td class="r"><b>{{ $money($k['revenue']) }}</b></td></tr>
    </tbody>
</table>

<p class="muted" style="margin-top:16px">Revenue received = deposits + payments − refunds, by the day the money was received. Booked value = bookings arriving in the period (not cancelled). Occupancy = occupied room-nights ÷ (rooms × days).</p>

</body>
</html>
