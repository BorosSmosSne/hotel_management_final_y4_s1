{{-- Shared payment fields. $payment = null when recording. $booking is always set. --}}
@php
    $p        = $payment ?? null;
    $isAdmin  = auth()->user()->isAdmin();
    $defaultAmount = $p ? $p->amount : ($booking->balanceDue() > 0 ? number_format($booking->balanceDue(), 2, '.', '') : '');
    $paidAtVal = old('paid_at', ($p?->paid_at ?? now())->format('Y-m-d\TH:i'));
@endphp

<div class="grid gap-5 sm:grid-cols-2">
    <div>
        <label for="pay_type" class="label">Type <span class="text-red-600" aria-hidden="true">*</span></label>
        <select id="pay_type" name="type" class="field" required @error('type') aria-invalid="true" @enderror>
            @foreach (\App\Models\Payment::TYPES as $key => $label)
                {{-- Staff cannot refund (the server rejects it too) --}}
                @continue($key === 'refund' && ! $isAdmin)
                <option value="{{ $key }}" @selected(old('type', $p?->type ?? 'payment') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-field-error name="type" />
    </div>

    <div>
        <label for="pay_amount" class="label">Amount ($) <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="number" id="pay_amount" name="amount" class="field" required min="0.01" step="0.01" inputmode="decimal"
               value="{{ old('amount', $defaultAmount) }}" @error('amount') aria-invalid="true" @enderror>
        <x-field-error name="amount" />
    </div>

    <div>
        <label for="pay_method" class="label">Method <span class="text-red-600" aria-hidden="true">*</span></label>
        <select id="pay_method" name="method" class="field" required @error('method') aria-invalid="true" @enderror>
            @foreach (\App\Models\Payment::METHODS as $key => $label)
                <option value="{{ $key }}" @selected(old('method', $p?->method ?? 'cash') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <x-field-error name="method" />
    </div>

    <div>
        <label for="pay_paid_at" class="label">Paid at</label>
        <input type="datetime-local" id="pay_paid_at" name="paid_at" class="field" value="{{ $paidAtVal }}" @error('paid_at') aria-invalid="true" @enderror>
        <x-field-error name="paid_at" />
    </div>

    <div class="sm:col-span-2">
        <label for="pay_reference" class="label">Reference</label>
        <input type="text" id="pay_reference" name="reference" class="field" maxlength="100" placeholder="Receipt or transaction number"
               value="{{ old('reference', $p?->reference) }}" @error('reference') aria-invalid="true" @enderror>
        <x-field-error name="reference" />
    </div>

    <div class="sm:col-span-2">
        <label for="pay_notes" class="label">Notes</label>
        <input type="text" id="pay_notes" name="notes" class="field" maxlength="500" value="{{ old('notes', $p?->notes) }}" @error('notes') aria-invalid="true" @enderror>
        <x-field-error name="notes" />
    </div>
</div>
