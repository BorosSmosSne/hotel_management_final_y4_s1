{{-- Reusable password field with a show/hide eye button.
     Usage:  <x-password-input name="password" autocomplete="new-password" required />
             <x-password-input name="password_confirmation" />
             <x-password-input name="password" leading />      (adds the lock icon on the left, like the login page)
     The label and <x-field-error> stay in the page. Extra attributes (required, minlength, autocomplete…)
     are passed straight to the <input>. The click is handled once for all fields in resources/js/app.js
     ([data-toggle-password]); each field toggles on its own. --}}
@props([
    'name',
    'id' => null,
    'leading' => false,
])
@php $id = $id ?: $name; @endphp

<div class="relative">
    @if ($leading)
        <x-icon name="lock" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
    @endif

    <input type="password" id="{{ $id }}" name="{{ $name }}"
           {{ $attributes->merge(['class' => 'field !pr-12' . ($leading ? ' !pl-12' : '')]) }}
           @error($name) aria-invalid="true" @enderror>

    <button type="button" data-toggle-password="{{ $id }}" aria-controls="{{ $id }}"
            aria-label="Show password" aria-pressed="false"
            class="icon-btn absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:bg-tint-2">
        <span data-eye><x-icon name="eye" /></span>
        <span data-eye-off class="hidden"><x-icon name="eye-off" /></span>
    </button>
</div>
