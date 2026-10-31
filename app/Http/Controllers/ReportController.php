<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\HotelSettings;
use App\Support\ReportBuilder;
use Illuminate\Http\Request;

/**
 * Reports (Admin only: the routes sit behind role:admin).
 * One ReportBuilder feeds the screen, the CSV and the PDF, so the three always agree.
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        $params = ReportBuilder::resolve($request);

        return view('reports.index', [
            'p'      => $params,
            'report' => ReportBuilder::build($params),
            'ranges' => ReportBuilder::RANGES,
            'hotel'  => HotelSettings::all(),
        ]);
    }

    public function csv(Request $request)
    {
        $params = ReportBuilder::resolve($request);
        $r      = ReportBuilder::build($params);
        $k      = $r['kpi'];
        $name   = 'report-' . $r['from']->format('Ymd') . '-' . $r['to']->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($r, $k) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // UTF-8 BOM so Excel shows accents correctly

            $row = fn (array $cells) => fputcsv($out, array_map([$this, 'safe'], $cells));

            $row([HotelSettings::get('name') . ' - report']);
            $row(['Period', $r['from']->format('Y-m-d') . ' to ' . $r['to']->format('Y-m-d')]);
            $row(['Currency', HotelSettings::currency()]);
            $row([]);

            $row(['SUMMARY']);
            $row(['Revenue received (net of refunds)', $k['revenue']]);
            $row(['Payments received', $k['collected']]);
            $row(['Refunds', $k['refunded']]);
            $row(['Booked value (arrivals in period)', $k['booked_value']]);
            $row(['Tax included in booked value', $k['tax']]);
            $row(['Bookings (arrivals, not cancelled)', $k['bookings']]);
            $row(['Cancelled bookings (arrivals)', $k['cancelled']]);
            $row(['Occupancy %', $k['occupancy']]);
            $row(['Occupied room-nights', $k['occupied_nights']]);
            $row(['Available room-nights', $k['available_nights']]);
            $row(['Average nightly rate (before tax)', $k['adr']]);
            $row([]);

            $row(['BOOKINGS BY ' . strtoupper($r['group'])]);
            $row([ucfirst($r['group']), 'Bookings', 'Booked value', 'Revenue received']);
            foreach ($r['series'] as $s) {
                $row([$s['label'], $s['bookings'], round($s['value'], 2), round($s['revenue'], 2)]);
            }
            $row([]);

            $row(['BY ROOM TYPE']);
            $row(['Room type', 'Bookings', 'Nights', 'Booked value']);
            foreach ($r['by_type'] as $t) {
                $row([$t['label'], $t['bookings'], $t['nights'], $t['value']]);
            }
            $row([]);

            $row(['PAYMENTS BY METHOD']);
            $row(['Method', 'Payments', 'Received', 'Refunded', 'Net']);
            foreach ($r['by_method'] as $m) {
                $row([$m['label'], $m['count'], $m['in'], $m['refunds'], $m['net']]);
            }

            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request)
    {
        // Same approach as the invoice PDF: needs composer require barryvdh/laravel-dompdf
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('error', 'PDF export needs the dompdf package. Run: composer require barryvdh/laravel-dompdf  (or use Print and choose "Save as PDF").');
        }

        $params = ReportBuilder::resolve($request);
        $r      = ReportBuilder::build($params);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.pdf', [
            'report' => $r,
            'hotel'  => HotelSettings::all(),
            'logo'   => HotelSettings::logoSrc(forPdf: true),
        ])->setPaper('a4')->download('report-' . $r['from']->format('Ymd') . '-' . $r['to']->format('Ymd') . '.pdf');
    }

    /** Stops spreadsheet formula injection: a cell that starts with = + - @ is read as a formula by Excel. */
    private function safe(mixed $v): mixed
    {
        return is_string($v) && $v !== '' && str_contains('=+-@', $v[0]) ? "'" . $v : $v;
    }
}
