{{-- Shared by create + edit. $user is null when creating. Values fall back to old() so errors keep the input. --}}
@php
    $u = $user ?? null;
    // Your own account and the last active admin cannot change role/status (the server enforces this too).
    $locked = $u && ($u->id === auth()->id() || $u->isLastActiveAdmin());
    $roleVal   = old('role', $u?->role ?? 'staff');
    $statusVal = old('status', ($u ? ($u->is_active ? 'active' : 'inactive') : 'active'));
@endphp

<div class="grid gap-6 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label for="name" class="label">Full name <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="text" id="name" name="name" class="field" required maxlength="255" value="{{ old('name', $u?->name) }}" @error('name') aria-invalid="true" @enderror>
        <x-field-error name="name" />
    </div>

    <div>
        <label for="email" class="label">Email <span class="text-red-600" aria-hidden="true">*</span></label>
        <input type="email" id="email" name="email" class="field" required maxlength="255" value="{{ old('email', $u?->email) }}" autocomplete="off" @error('email') aria-invalid="true" @enderror>
        <x-field-error name="email" />
    </div>

    <div>
        <label for="phone" class="label">Phone</label>
        <input type="tel" id="phone" name="phone" class="field" maxlength="30" placeholder="+855 12 345 678" value="{{ old('phone', $u?->phone) }}" @error('phone') aria-invalid="true" @enderror>
        <x-field-error name="phone" />
    </div>

    <div>
        <label for="role" class="label">Role <span class="text-red-600" aria-hidden="true">*</span></label>
        <select id="role" name="role" class="field" @disabled($locked) @error('role') aria-invalid="true" @enderror>
            <option value="admin" @selected($roleVal === 'admin')>Admin: full access</option>
            <option value="staff" @selected($roleVal === 'staff')>Staff: bookings, check-in / check-out</option>
        </select>
        @if ($locked)<input type="hidden" name="role" value="{{ $u->role }}">@endif
        <x-field-error name="role" />
    </div>

    <div>
        <label for="status" class="label">Status <span class="text-red-600" aria-hidden="true">*</span></label>
        <select id="status" name="status" class="field" @disabled($locked) @error('status') aria-invalid="true" @enderror>
            <option value="active" @selected($statusVal === 'active')>Active</option>
            <option value="inactive" @selected($statusVal === 'inactive')>Inactive: cannot log in</option>
        </select>
        @if ($locked)<input type="hidden" name="status" value="{{ $u->is_active ? 'active' : 'inactive' }}">@endif
        <x-field-error name="status" />
    </div>

    @if ($locked)
        <p class="flex items-start gap-2 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800 ring-1 ring-amber-200 sm:col-span-2">
            <x-icon name="lock" class="mt-0.5 size-4 shrink-0" />
            {{ $u->id === auth()->id() ? 'This is your own account, so its role and status cannot be changed.' : 'This is the last active administrator, so its role and status are locked.' }}
        </p>
    @endif

    <div>
        <label for="password" class="label">Password @unless($u)<span class="text-red-600" aria-hidden="true">*</span>@endunless</label>
        <x-password-input name="password" minlength="8" autocomplete="new-password" :required="! $u" />
        <p class="hint">{{ $u ? 'Leave blank to keep the current password.' : 'At least 8 characters.' }}</p>
        <x-field-error name="password" />
    </div>

    <div>
        <label for="password_confirmation" class="label">Confirm password @unless($u)<span class="text-red-600" aria-hidden="true">*</span>@endunless</label>
        <x-password-input name="password_confirmation" minlength="8" autocomplete="new-password" :required="! $u" />
    </div>
</div>
