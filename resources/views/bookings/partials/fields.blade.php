{{-- Shared booking fields (used by the booking modal and the admin edit page).
     $booking is null when creating. Values fall back to old() so errors keep what was typed. --}}
@php $b = $booking ?? null; @endphp

<div class="grid gap-5 sm:grid-cols-2">
    @if (! empty($guestSearch))
        {{-- Find an existing guest, or just fill the fields below to create a new one --}}
        <div class="sm:col-span-2" data-guest-picker data-search-url="{{ route('guests.search') }}">
            <label for="{{ $idp }}guest_search" class="label">Find existing guest</label>
            <div class="relative">
                <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                <input type="text" id="{{ $idp }}guest_search" class="field !pl-12" placeholder="Search name, phone or ID number…"
                       autocomplete="off" role="combobox" aria-expanded="false" aria-controls="{{ $idp }}guest_results" data-guest-search>
                <ul id="{{ $idp }}guest_results" role="listbox" class="absolute z-20 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-xl bg-white p-1 shadow-lg ring-1 ring-slate-900/10" data-guest-results></ul>
            </div>
            <input type="hidden" name="guest_id" value="{{ old('guest_id') }}" data-guest-id>
            <div class="mt-2 hidden items-center justify-between gap-3 rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-800" data-guest-selected>
                <span class="min-w-0 truncate" data-guest-selected-text></span>
                <button type="button" class="shrink-0 font-semibold underline" data-guest-clear>Use a different guest</button>
            </div>
            <p class="hint" data-guest-hint>Not found? Fill in the details below and a new guest is saved automatically.</p>
        </div>
    @endif

    <div class="sm:col-span-2">
        <label for="{{ $idp }}guest_name" class="label">Customer name <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="text" id="{{ $idp }}guest_name" name="guest_name" class="field" required maxlength="255"
               value="{{ old('guest_name', $b?->guest_name) }}" autocomplete="off" @error('guest_name') aria-invalid="true" @enderror>
        <x-field-error name="guest_name" />
    </div>

    <div>
        <label for="{{ $idp }}phone" class="label">Phone <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="tel" id="{{ $idp }}phone" name="phone" class="field" required maxlength="30" placeholder="+855 12 345 678"
               value="{{ old('phone', $b?->phone) }}" @error('phone') aria-invalid="true" @enderror>
        <x-field-error name="phone" />
    </div>

    <div>
        <label for="{{ $idp }}id_number" class="label">ID / passport number <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="text" id="{{ $idp }}id_number" name="id_number" class="field" required maxlength="50"
               value="{{ old('id_number', $b?->id_number) }}" autocomplete="off" @error('id_number') aria-invalid="true" @enderror>
        <x-field-error name="id_number" />
    </div>

    <div>
        <label for="{{ $idp }}check_in" class="label">Check-in <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="date" id="{{ $idp }}check_in" name="check_in" class="field" required data-bk-in
               value="{{ old('check_in', $b?->check_in?->toDateString()) }}" @error('check_in') aria-invalid="true" @enderror>
        <x-field-error name="check_in" />
    </div>

    <div>
        <label for="{{ $idp }}check_out" class="label">Check-out <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="date" id="{{ $idp }}check_out" name="check_out" class="field" required data-bk-out
               value="{{ old('check_out', $b?->check_out?->toDateString()) }}" @error('check_out') aria-invalid="true" @enderror>
        <x-field-error name="check_out" />
    </div>

    <div>
        <label for="{{ $idp }}guests" class="label">Number of guests <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="number" id="{{ $idp }}guests" name="guests" class="field" required min="1" max="20" data-bk-guests
               value="{{ old('guests', $b?->guests ?? 1) }}" @error('guests') aria-invalid="true" @enderror>
        <p class="hint" data-bk-capacity-hint></p>
        <x-field-error name="guests" />
    </div>

    <div class="sm:col-span-2">
        <label for="{{ $idp }}notes" class="label">Notes</label>
        <textarea id="{{ $idp }}notes" name="notes" rows="3" class="field" maxlength="1000"
                  placeholder="Late arrival, special requests…" @error('notes') aria-invalid="true" @enderror>{{ old('notes', $b?->notes) }}</textarea>
        <x-field-error name="notes" />
    </div>
</div>

{{-- Live total: nights x nightly rate (display only; the server recalculates from the database) --}}
<div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-tint p-4" aria-live="polite">
    <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Estimated total</p>
        <p class="text-sm text-slate-600" data-bk-summary>Choose your dates</p>
    </div>
    <p class="text-2xl font-bold text-primary" data-bk-total>$0.00</p>
</div>
