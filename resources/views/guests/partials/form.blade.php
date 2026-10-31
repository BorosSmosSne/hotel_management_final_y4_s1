{{-- Shared by create + edit. $guest is null when creating. --}}
@php $g = $guest ?? null; @endphp

<div class="grid gap-6 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="label">Full name <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" class="field" required maxlength="255" value="{{ old('name', $g?->name) }}" @error('name') aria-invalid="true" @enderror>
        <x-field-error name="name" />
    </div>

    <div>
        <label for="phone" class="label">Phone <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="tel" id="phone" name="phone" class="field" required maxlength="30" placeholder="+855 12 345 678" value="{{ old('phone', $g?->phone) }}" @error('phone') aria-invalid="true" @enderror>
        <x-field-error name="phone" />
    </div>

    <div>
        <label for="email" class="label">Email</label>
        <input type="email" id="email" name="email" class="field" maxlength="255" value="{{ old('email', $g?->email) }}" @error('email') aria-invalid="true" @enderror>
        <x-field-error name="email" />
    </div>

    <div>
        <label for="id_number" class="label">ID / passport number <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="text" id="id_number" name="id_number" class="field" required maxlength="50" value="{{ old('id_number', $g?->id_number) }}" autocomplete="off" @error('id_number') aria-invalid="true" @enderror>
        <x-field-error name="id_number" />
    </div>

    <div>
        <label for="nationality" class="label">Nationality</label>
        <input type="text" id="nationality" name="nationality" class="field" maxlength="100" value="{{ old('nationality', $g?->nationality) }}" @error('nationality') aria-invalid="true" @enderror>
        <x-field-error name="nationality" />
    </div>

    <div class="sm:col-span-2">
        <label for="address" class="label">Address</label>
        <input type="text" id="address" name="address" class="field" maxlength="500" value="{{ old('address', $g?->address) }}" @error('address') aria-invalid="true" @enderror>
        <x-field-error name="address" />
    </div>

    <div class="sm:col-span-2">
        <label for="notes" class="label">Notes</label>
        <textarea id="notes" name="notes" rows="3" class="field" maxlength="2000" placeholder="Preferences, allergies, VIP…" @error('notes') aria-invalid="true" @enderror>{{ old('notes', $g?->notes) }}</textarea>
        <x-field-error name="notes" />
    </div>
</div>
