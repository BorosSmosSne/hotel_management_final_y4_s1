<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\HotelSettings;
use Illuminate\Support\Facades\Gate;

/**
 * Printable invoice / receipt for a booking, plus a PDF download.
 * PDF needs the dompdf package: composer require barryvdh/laravel-dompdf
 */
class InvoiceController extends Controller
{
    public function show(Booking $booking)
    {
        Gate::authorize('view', $booking);

        return view('invoices.document', $this->data($booking, forPdf: false));
    }

    public function pdf(Booking $booking)
    {
        Gate::authorize('view', $booking);

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return back()->with('error', 'PDF export needs the dompdf package. Run: composer require barryvdh/laravel-dompdf  (or use Print and choose "Save as PDF").');
        }

        $data = $this->data($booking, forPdf: true);

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('invoices.document', $data)
            ->setPaper('a4')
            ->download($data['number'] . '.pdf');
    }

    private function data(Booking $booking, bool $forPdf): array
    {
        $booking->load(['room.roomType', 'guest', 'payments' => fn ($q) => $q->orderBy('paid_at')->orderBy('id'), 'payments.receiver']);

        return [
            'booking' => $booking,
            'hotel'   => HotelSettings::all(),
            'logo'    => HotelSettings::logoSrc($forPdf),
            'number'  => 'INV-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'issued'  => now(),
            'paid'    => $booking->netPaid(),
            'balance' => $booking->balanceDue(),
            'status'  => $booking->paymentStatus(),
            'pdf'     => $forPdf,
        ];
    }
}
