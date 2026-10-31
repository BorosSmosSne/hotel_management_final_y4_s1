<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

/**
 * Builds every number on the Reports page (screen, CSV and PDF all use this one class,
 * so the three always agree).
 *
 * Definitions:
 *  - Revenue (cash)  = deposits + payments - refunds, by the date the money was received (paid_at).
 *  - Booked value    = total of bookings (not cancelled) whose ARRIVAL date is in the range.
 *  - Occupancy       = occupied room-nights / (rooms x days). A night counts when a booking that is
 *                      not cancelled covers it. Rooms in maintenance still count as rooms.
 */
class ReportBuilder
{
    public const RANGES = [
        'today'  => 'Today',
        'week'   => 'This week',
        'month'  => 'This month',
        'custom' => 'Custom',
    ];

    public const MAX_DAYS = 366;

    /** @return array{range:string, from:Carbon, to:Carbon, group:string, error:?string} */
    public static function resolve(Request $request): array
    {
        $range = array_key_exists((string) $request->query('range'), self::RANGES) ? $request->query('range') : 'month';
        $error = null;

        switch ($range) {
            case 'today':
                $from = today();
                $to   = today();
                break;
            case 'week':
                $from = today()->startOfWeek();
                $to   = today()->endOfWeek()->startOfDay();
                break;
            case 'custom':
                $from = self::date($request->query('from'));
                $to   = self::date($request->query('to'));
                if (! $from || ! $to) {
                    $error = 'Pick a start and an end date.';
                    $from  = today()->startOfMonth();
                    $to    = today();
                } elseif ($to->lt($from)) {
                    $error = 'The end date is before the start date, so the dates were swapped.';
                    [$from, $to] = [$to, $from];
                }
                if ($from->diffInDays($to) > self::MAX_DAYS) {
                    $error = 'The range is limited to ' . self::MAX_DAYS . ' days; it was shortened.';
                    $to = $from->copy()->addDays(self::MAX_DAYS);
                }
                break;
            default:
                $from = today()->startOfMonth();
                $to   = today()->endOfMonth()->startOfDay();
        }

        // Group by day unless the range is long (or the person asked for months).
        $span  = (int) $from->diffInDays($to) + 1;
        $group = in_array($request->query('group'), ['day', 'month'], true)
            ? $request->query('group')
            : ($span > 62 ? 'month' : 'day');

        return ['range' => $range, 'from' => $from->startOfDay(), 'to' => $to->startOfDay(), 'group' => $group, 'error' => $error];
    }

    /** @param array{from:Carbon,to:Carbon,group:string} $p */
    public static function build(array $p): array
    {
        $from = $p['from']->copy()->startOfDay();
        $to   = $p['to']->copy()->startOfDay();
        $days = (int) $from->diffInDays($to) + 1;

        /* ---------- Payments (cash revenue) ---------- */
        $payments = Payment::query()
            ->whereBetween('paid_at', [$from, $to->copy()->endOfDay()])
            ->get(['id', 'amount', 'method', 'type', 'paid_at']);

        $collected = (float) $payments->where('type', '!=', Payment::REFUND)->sum('amount');
        $refunded  = (float) $payments->where('type', Payment::REFUND)->sum('amount');

        $byMethod = collect(Payment::METHODS)->map(function ($label, $key) use ($payments) {
            $rows = $payments->where('method', $key);

            return [
                'label'    => $label,
                'count'    => $rows->count(),
                'in'       => round((float) $rows->where('type', '!=', Payment::REFUND)->sum('amount'), 2),
                'refunds'  => round((float) $rows->where('type', Payment::REFUND)->sum('amount'), 2),
                'net'      => round((float) $rows->sum(fn (Payment $p) => $p->signedAmount()), 2),
            ];
        })->values();

        /* ---------- Bookings arriving in the range (booked value) ---------- */
        $arrivals = Booking::query()
            ->with('room.roomType')
            ->where('status', '!=', Booking::CANCELLED)
            ->whereBetween('check_in', [$from->toDateString(), $to->toDateString()])
            ->get();

        $cancelled = Booking::where('status', Booking::CANCELLED)
            ->whereBetween('check_in', [$from->toDateString(), $to->toDateString()])
            ->count();

        $bookedValue = round((float) $arrivals->sum('total_price'), 2);
        $taxTotal    = round((float) $arrivals->sum('tax_amount'), 2);

        /* ---------- Occupancy: room-nights inside the range ---------- */
        $roomCount = Room::count();
        $stays = Booking::query()
            ->where('status', '!=', Booking::CANCELLED)
            ->where('check_in', '<=', $to->toDateString())
            ->where('check_out', '>', $from->toDateString())
            ->get(['id', 'room_id', 'check_in', 'check_out']);

        $nightsByDay = [];   // 'Y-m-d' => occupied rooms that night
        foreach ($stays as $b) {
            $start = $b->check_in->copy()->max($from);
            $end   = $b->check_out->copy()->subDay()->min($to);   // last night of the stay
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $nightsByDay[$d->toDateString()] = ($nightsByDay[$d->toDateString()] ?? 0) + 1;
            }
        }
        $occupiedNights = array_sum($nightsByDay);
        $availableNights = $roomCount * $days;
        $occupancy = $availableNights > 0 ? round($occupiedNights / $availableNights * 100, 1) : 0.0;
        // Average nightly rate (before tax) of the bookings that arrive in the range
        $arrivalNights = (int) $arrivals->sum('nights');
        $adr = $arrivalNights > 0 ? round((float) $arrivals->sum(fn (Booking $b) => $b->subtotal()) / $arrivalNights, 2) : 0.0;

        /* ---------- Bookings by day / month ---------- */
        $fmt   = $p['group'] === 'month' ? 'Y-m' : 'Y-m-d';
        $label = $p['group'] === 'month' ? 'M Y' : 'D d M';
        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $key = $cursor->format($fmt);
            $buckets[$key] ??= ['label' => $cursor->format($label), 'bookings' => 0, 'value' => 0.0, 'revenue' => 0.0];
            $cursor->addDay();
        }
        foreach ($arrivals as $b) {
            $key = $b->check_in->format($fmt);
            if (isset($buckets[$key])) {
                $buckets[$key]['bookings']++;
                $buckets[$key]['value'] += (float) $b->total_price;
            }
        }
        foreach ($payments as $pay) {
            $key = $pay->paid_at->format($fmt);
            if (isset($buckets[$key])) {
                $buckets[$key]['revenue'] += $pay->signedAmount();
            }
        }
        $series = array_values($buckets);

        /* ---------- By room type ---------- */
        $byType = $arrivals->groupBy(fn (Booking $b) => $b->room?->roomType?->type_name ?? 'Unknown')
            ->map(fn ($rows, $name) => [
                'label'    => $name,
                'bookings' => $rows->count(),
                'nights'   => (int) $rows->sum('nights'),
                'value'    => round((float) $rows->sum('total_price'), 2),
            ])
            ->sortByDesc('value')->values();

        return [
            'from' => $from, 'to' => $to, 'days' => $days, 'group' => $p['group'],
            'kpi' => [
                'revenue'        => round($collected - $refunded, 2),
                'collected'      => round($collected, 2),
                'refunded'       => round($refunded, 2),
                'booked_value'   => $bookedValue,
                'tax'            => $taxTotal,
                'bookings'       => $arrivals->count(),
                'cancelled'      => $cancelled,
                'occupancy'      => $occupancy,
                'occupied_nights'=> $occupiedNights,
                'available_nights' => $availableNights,
                'rooms'          => $roomCount,
                'adr'            => $adr,
            ],
            'series'    => $series,
            'by_type'   => $byType,
            'by_method' => $byMethod,
        ];
    }

    private static function date(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
