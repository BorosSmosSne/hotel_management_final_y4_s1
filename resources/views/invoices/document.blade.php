{{-- Printable invoice / receipt. Plain tables + inline CSS so it prints well AND renders in dompdf.
     $pdf = true when rendered for the PDF download (no toolbar). --}}
@php
    $cur = $hotel['currency'];
    $money = fn ($n) => $cur . number_format((float) $n, 2);
    $statusLabel = ['unpaid' => 'UNPAID', 'partial' => 'PARTIALLY PAID', 'paid' => 'PAID'][$status];
    $statusColor = ['unpaid' => '#b91c1c', 'partial' => '#b45309', 'paid' => '#047857'][$status];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $number }} · {{ $hotel['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Helvetica, Arial, sans-serif; font-size: 13px; color: #0f172a; background: {{ $pdf ? '#fff' : '#f1f5f9' }}; }
        .sheet { max-width: 800px; margin: {{ $pdf ? '0' : '24px auto' }}; background: #fff; padding: 36px 40px; {{ $pdf ? '' : 'box-shadow: 0 10px 30px rgba(15,23,42,.08); border-radius: 12px;' }} }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 8px 10px; vertical-align: top; text-align: left; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        h1 { margin: 0; font-size: 26px; letter-spacing: 1px; }
        .brand { font-size: 20px; font-weight: bold; }
        .logo { height: 52px; max-width: 160px; }
        .initial { display: inline-block; width: 44px; height: 44px; line-height: 44px; text-align: center; background: #4338ca; color: #fff; border-radius: 10px; font-weight: bold; font-size: 20px; }
        .box { border: 1px solid #e2e8f0; border-radius: 8px; }
        .items th { background: #eef2ff; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #475569; }
        .items td { border-bottom: 1px solid #e2e8f0; }
        .totals td { padding: 5px 10px; }
        .grand td { font-size: 15px; font-weight: bold; border-top: 2px solid #0f172a; }
        .stamp { display: inline-block; padding: 4px 12px; border: 2px solid {{ $statusColor }}; color: {{ $statusColor }}; font-weight: bold; letter-spacing: 1px; border-radius: 6px; }
        .toolbar { max-width: 800px; margin: 20px auto 0; display: flex; gap: 8px; justify-content: flex-end; }
        .toolbar a, .toolbar button { font: inherit; padding: 9px 16px; border-radius: 8px; border: 1px solid #c7d2fe; background: #fff; color: #3730a3; text-decoration: none; cursor: pointer; }
        .toolbar .primary { background: #4338ca; color: #fff; border-color: #4338ca; }
        @media print { body { background: #fff; } .sheet { margin: 0; box-shadow: none; border-radius: 0; padding: 0; max-width: none; } .toolbar { display: none !important; } }
        @page { margin: 18mm; }
    </style>
</head>
<body>

@unless ($pdf)
    <div class="toolbar">
        <a href="{{ route('bookings.show', $booking) }}">← Back to booking</a>
        <a href="{{ route('invoices.pdf', $booking) }}">Download PDF</a>
        <button type="button" class="primary" onclick="window.print()">Print</button>
    </div>
@endunless

<div class="sheet">

    <table>
        <tr>
            <td style="width:60%">
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $hotel['name'] }}" class="logo">
                @else
                    <span class="initial">{{ mb_strtoupper(mb_substr($hotel['name'], 0, 1)) }}</span>
                @endif
                <div class="brand" style="margin-top:8px">{{ $hotel['name'] }}</div>
                <div class="muted">
                    @if ($hotel['address']){{ $hotel['address'] }}<br>@endif
                    @if ($hotel['phone']){{ $hotel['phone'] }}@endif
                    @if ($hotel['phone'] && $hotel['email']) · @endif
                    @if ($hotel['email']){{ $hotel['email'] }}@endif
                </div>
            </td>
            <td class="right">
                <h1>INVOICE</h1>
                <div style="margin-top:6px"><b>{{ $number }}</b></div>
                <div class="muted">Issued {{ $issued->format('d M Y') }}</div>
                <div style="margin-top:10px"><span class="stamp">{{ $statusLabel }}</span></div>
            </td>
        </tr>
    </table>

    <table style="margin-top:22px">
        <tr>
            <td style="width:50%; padding-left:0">
                <div class="muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px">Billed to</div>
                <div style="font-weight:bold; font-size:15px; margin-top:3px">{{ $booking->guest_name }}</div>
                <div class="muted">
                    {{ $booking->phone }}<br>
                    ID / passport: {{ $booking->id_number }}
                    @if ($booking->guest?->email)<br>{{ $booking->guest->email }}@endif
                    @if ($booking->guest?->address)<br>{{ $booking->guest->address }}@endif
                </div>
            </td>
            <td style="width:50%">
                <div class="muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px">Stay</div>
                <div style="margin-top:3px">
                    Room <b>{{ $booking->room->room_number }}</b> · {{ $booking->room->roomType->type_name ?? 'N/A' }} · Floor {{ $booking->room->floor }}<br>
                    Check-in: {{ $booking->check_in->format('d M Y') }} <span class="muted">from {{ \App\Support\HotelSettings::formatTime($hotel['checkin_time']) }}</span><br>
                    Check-out: {{ $booking->check_out->format('d M Y') }} <span class="muted">by {{ \App\Support\HotelSettings::formatTime($hotel['checkout_time']) }}</span><br>
                    Guests: {{ $booking->guests }}
                </div>
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top:18px">
        <thead><tr><th>Description</th><th class="right">Nights</th><th class="right">Rate</th><th class="right">Amount</th></tr></thead>
        <tbody>
            <tr>
                <td>Room {{ $booking->room->room_number }} ({{ $booking->room->roomType->type_name ?? 'Room' }})<br>
                    <span class="muted">{{ $booking->check_in->format('d M') }} – {{ $booking->check_out->format('d M Y') }}</span></td>
                <td class="right">{{ $booking->nights }}</td>
                <td class="right">{{ $money($booking->price_per_night) }}</td>
                <td class="right">{{ $money($booking->subtotal()) }}</td>
            </tr>
        </tbody>
    </table>

    <table style="margin-top:14px">
        <tr>
            <td style="width:52%; padding-left:0">
                <div class="muted" style="font-size:11px; text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px">Payments received</div>
                @if ($booking->payments->isEmpty())
                    <div class="muted">No payments recorded yet.</div>
                @else
                    <table class="box" style="font-size:12px">
                        @foreach ($booking->payments as $pay)
                            <tr>
                                <td>{{ $pay->paid_at->format('d M Y') }}<br><span class="muted">{{ $pay->typeLabel() }} · {{ $pay->methodLabel() }}@if ($pay->reference) · {{ $pay->reference }}@endif</span></td>
                                <td class="right" style="{{ $pay->isRefund() ? 'color:#b91c1c' : '' }}">{{ $pay->isRefund() ? '−' : '' }}{{ $money($pay->amount) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
            <td style="width:48%; padding-right:0">
                <table class="totals">
                    @if ((float) $booking->tax_amount > 0)
                        <tr><td class="muted">Subtotal</td><td class="right">{{ $money($booking->subtotal()) }}</td></tr>
                        <tr><td class="muted">Tax ({{ rtrim(rtrim(number_format((float) $booking->tax_rate, 2), '0'), '.') }}%)</td><td class="right">{{ $money($booking->tax_amount) }}</td></tr>
                    @endif
                    <tr><td class="muted">Total</td><td class="right">{{ $money($booking->total_price) }}</td></tr>
                    <tr><td class="muted">Paid</td><td class="right">{{ $money($paid) }}</td></tr>
                    <tr class="grand"><td>Balance due</td><td class="right">{{ $money($balance) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="muted" style="margin-top:34px; text-align:center; font-size:12px">
        Thank you for staying with {{ $hotel['name'] }}.
    </p>
</div>

</body>
</html>
