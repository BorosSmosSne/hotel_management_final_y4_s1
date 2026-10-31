<?php

namespace App\Http\Controllers;

use App\Http\Requests\SavePaymentRequest;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\HotelSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Payments. Staff: list + record deposits/payments.  Admin: also edit, delete and refund.
 * The rules are enforced by SavePaymentRequest + PaymentPolicy, not only hidden in the UI.
 */
class PaymentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Payment::class);

        $base = $this->filtered($request);

        $collected = (float) (clone $base)->whereIn('type', [Payment::DEPOSIT, Payment::PAYMENT])->sum('amount');
        $refunded  = (float) (clone $base)->where('type', Payment::REFUND)->sum('amount');

        $payments = (clone $base)
            ->with(['booking.room', 'booking.guest', 'receiver'])
            ->orderByDesc('paid_at')->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('payments.index', compact('payments', 'collected', 'refunded'));
    }

    public function store(SavePaymentRequest $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('create', Payment::class);
        $data = $request->validated();

        $payment = DB::transaction(function () use ($data, $booking, $request) {
            // Lock the booking so two people cannot overpay it at the same moment
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $this->assertAmountAllowed($locked, $data['type'], (float) $data['amount']);

            return Payment::create([
                'booking_id'  => $locked->id,
                'amount'      => $data['amount'],
                'method'      => $data['method'],
                'type'        => $data['type'],
                'reference'   => $data['reference'] ?? null,
                'notes'       => $data['notes'] ?? null,
                'paid_at'     => ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now(),
                'received_by' => $request->user()->id,
            ]);
        });

        return redirect()->route('bookings.show', $booking)->with('success', sprintf(
            '%s of %s recorded (%s).',
            $payment->typeLabel(), HotelSettings::money($payment->amount), $payment->methodLabel()
        ));
    }

    public function edit(Payment $payment)
    {
        Gate::authorize('update', $payment);

        $payment->load('booking.room', 'booking.guest');

        return view('payments.edit', ['payment' => $payment, 'booking' => $payment->booking]);
    }

    public function update(SavePaymentRequest $request, Payment $payment): RedirectResponse
    {
        Gate::authorize('update', $payment);
        $data = $request->validated();

        DB::transaction(function () use ($data, $payment) {
            $locked = Booking::whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();

            $this->assertAmountAllowed($locked, $data['type'], (float) $data['amount'], ignorePaymentId: $payment->id);

            $payment->update([
                'amount'    => $data['amount'],
                'method'    => $data['method'],
                'type'      => $data['type'],
                'reference' => $data['reference'] ?? null,
                'notes'     => $data['notes'] ?? null,
                'paid_at'   => ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : $payment->paid_at,
            ]);
        });

        return redirect()->route('bookings.show', $payment->booking_id)->with('success', 'Payment updated.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        Gate::authorize('delete', $payment);

        $bookingId = $payment->booking_id;
        $payment->delete();

        return redirect()->route('bookings.show', $bookingId)->with('success', 'Payment deleted.');
    }

    /* ---------- helpers ---------- */

    /** Search + filters for the payments list. */
    private function filtered(Request $request): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $from   = $this->parseDate($request->query('from'));
        $to     = $this->parseDate($request->query('to'));

        return Payment::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('booking', fn ($b) => $b
                    ->where('guest_name', 'like', "%{$search}%")
                    ->orWhereHas('room', fn ($r) => $r->where('room_number', 'like', "%{$search}%")))))
            ->when(array_key_exists((string) $request->query('method'), Payment::METHODS),
                fn ($q) => $q->where('method', $request->query('method')))
            ->when(array_key_exists((string) $request->query('type'), Payment::TYPES),
                fn ($q) => $q->where('type', $request->query('type')))
            ->when($from, fn ($q) => $q->where('paid_at', '>=', $from->copy()->startOfDay()))
            ->when($to, fn ($q) => $q->where('paid_at', '<=', $to->copy()->endOfDay()));
    }

    /**
     * Business rules for the amount:
     *  - a refund can never exceed what has been paid
     *  - deposits / payments can never exceed the balance due, and not on a cancelled booking
     */
    private function assertAmountAllowed(Booking $booking, string $type, float $amount, ?int $ignorePaymentId = null): void
    {
        $paid = $booking->netPaid($ignorePaymentId);

        if ($type === Payment::REFUND) {
            if ($amount > $paid + 0.001) {
                throw ValidationException::withMessages([
                    'amount' => 'A refund cannot be more than the amount paid (' . HotelSettings::money($paid) . ').',
                ]);
            }
            return;
        }

        if ($booking->status === Booking::CANCELLED) {
            throw ValidationException::withMessages([
                'type' => 'This booking is cancelled. Only a refund can be recorded.',
            ]);
        }

        $due = $booking->balanceDue($ignorePaymentId);
        if ($amount > $due + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'The amount is more than the balance due (' . HotelSettings::money($due) . ').',
            ]);
        }
    }

    private function parseDate(?string $value): ?Carbon
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
