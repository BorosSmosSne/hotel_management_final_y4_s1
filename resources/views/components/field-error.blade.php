{{-- NEW: inline validation message. Usage: <x-field-error name="room_number" /> --}}
@props(['name'])
@error($name)
    <p class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-600" role="alert">
        <x-icon name="alert" class="size-4 shrink-0" />{{ $message }}
    </p>
@enderror
