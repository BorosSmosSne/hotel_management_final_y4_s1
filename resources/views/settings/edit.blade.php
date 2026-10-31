@extends('layouts.app')
@section('title', 'Settings')
@section('content')

@php
    $v = fn (string $key) => old($key, $settings[$key] ?? '');
    $initial = mb_strtoupper(mb_substr($settings['name'] ?? 'H', 0, 1));
@endphp

<x-page-hero eyebrow="Admin" title="Settings" subtitle="Hotel details used in the sidebar, bookings and invoices." />

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6" novalidate>
    @csrf
    @method('PUT')
    <x-error-summary />

    {{-- Identity --}}
    <section class="card p-6" aria-labelledby="idTitle">
        <h2 id="idTitle" class="text-lg font-bold">Hotel details</h2>
        <p class="text-sm text-slate-500">Shown in the sidebar and on every invoice.</p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                <label for="name" class="mb-1.5 block text-sm font-medium">Hotel name <span class="text-red-600">*</span></label>
                <input type="text" id="name" name="name" class="field" value="{{ $v('name') }}" maxlength="100" required>
                <x-field-error name="name" />
            </div>
            <div class="md:col-span-2">
                <label for="address" class="mb-1.5 block text-sm font-medium">Address</label>
                <input type="text" id="address" name="address" class="field" value="{{ $v('address') }}" maxlength="255">
                <x-field-error name="address" />
            </div>
            <div>
                <label for="phone" class="mb-1.5 block text-sm font-medium">Phone</label>
                <input type="text" id="phone" name="phone" class="field" value="{{ $v('phone') }}" maxlength="40" inputmode="tel">
                <x-field-error name="phone" />
            </div>
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium">Email</label>
                <input type="email" id="email" name="email" class="field" value="{{ $v('email') }}" maxlength="150">
                <x-field-error name="email" />
            </div>
        </div>
    </section>

    {{-- Logo --}}
    <section class="card p-6" aria-labelledby="logoTitle">
        <h2 id="logoTitle" class="text-lg font-bold">Logo</h2>
        <p class="text-sm text-slate-500">PNG, JPG or WEBP, up to 2 MB. Without a logo the hotel's first letter is shown.</p>

        <div class="mt-5 flex flex-wrap items-center gap-6">
            <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl bg-tint ring-1 ring-slate-200">
                @if ($logo)
                    <img src="{{ $logo }}" alt="Current logo" class="max-h-full max-w-full object-contain" id="logoPreview">
                @else
                    <span class="text-3xl font-bold text-primary" id="logoInitial">{{ $initial }}</span>
                    <img src="" alt="Logo preview" class="hidden max-h-full max-w-full object-contain" id="logoPreview">
                @endif
            </div>
            <div class="min-w-0 flex-1 space-y-3">
                <div>
                    <label for="logo" class="mb-1.5 block text-sm font-medium">{{ $logo ? 'Replace logo' : 'Upload logo' }}</label>
                    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp" class="field !py-2 text-sm">
                    <x-field-error name="logo" />
                </div>
                @if ($logo)
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remove_logo" value="1" class="size-4 rounded border-slate-300" @checked(old('remove_logo'))>
                        Remove the current logo
                    </label>
                @endif
            </div>
        </div>
    </section>

    {{-- Money --}}
    <section class="card p-6" aria-labelledby="moneyTitle">
        <h2 id="moneyTitle" class="text-lg font-bold">Currency and tax</h2>
        <p class="text-sm text-slate-500">Tax is added on top of the room price when a booking is made.</p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label for="currency" class="mb-1.5 block text-sm font-medium">Currency symbol <span class="text-red-600">*</span></label>
                <input type="text" id="currency" name="currency" class="field" value="{{ $v('currency') }}" maxlength="5" required placeholder="$">
                <x-field-error name="currency" />
            </div>
            <div>
                <label for="tax_rate" class="mb-1.5 block text-sm font-medium">Tax (%) <span class="text-red-600">*</span></label>
                <input type="number" id="tax_rate" name="tax_rate" class="field" value="{{ $v('tax_rate') }}" min="0" max="100" step="0.01" required inputmode="decimal">
                <x-field-error name="tax_rate" />
            </div>
        </div>
        <p class="mt-4 flex items-start gap-2 rounded-xl bg-tint px-3 py-2.5 text-sm text-slate-600">
            <x-icon name="alert" class="mt-0.5 size-4 shrink-0 text-primary" />
            A new tax rate applies to bookings made from now on. Existing bookings and their invoices keep the rate they were booked at.
        </p>
    </section>

    {{-- Times --}}
    <section class="card p-6" aria-labelledby="timeTitle">
        <h2 id="timeTitle" class="text-lg font-bold">Default check-in and check-out times</h2>
        <p class="text-sm text-slate-500">Shown on the booking form and on invoices.</p>

        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <div>
                <label for="checkin_time" class="mb-1.5 block text-sm font-medium">Check-in from <span class="text-red-600">*</span></label>
                <input type="time" id="checkin_time" name="checkin_time" class="field" value="{{ $v('checkin_time') }}" required>
                <x-field-error name="checkin_time" />
            </div>
            <div>
                <label for="checkout_time" class="mb-1.5 block text-sm font-medium">Check-out by <span class="text-red-600">*</span></label>
                <input type="time" id="checkout_time" name="checkout_time" class="field" value="{{ $v('checkout_time') }}" required>
                <x-field-error name="checkout_time" />
            </div>
        </div>
    </section>

    <div class="flex justify-end">
        <button class="btn btn-primary" data-submit><x-icon name="save" class="size-4" />Save settings</button>
    </div>
</form>

<script>
(function () {
    // Preview a newly chosen logo before saving (nothing is uploaded until you press Save).
    var input = document.getElementById('logo'), img = document.getElementById('logoPreview'), initial = document.getElementById('logoInitial');
    if (!input || !img) return;
    input.addEventListener('change', function () {
        var f = input.files && input.files[0];
        if (!f || !f.type.match(/^image\/(png|jpeg|webp)$/)) return;
        img.src = URL.createObjectURL(f);
        img.classList.remove('hidden');
        if (initial) initial.classList.add('hidden');
    });
})();
</script>

@endsection
