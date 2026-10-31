@extends('layouts.app')
@section('title', 'My Profile')
@section('content')

@php
    $initials = \Illuminate\Support\Str::of($user->name)->explode(' ')->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
    $pw = $errors->getBag('updatePassword');   // the password form has its own error bag
@endphp

<x-page-hero eyebrow="Account" title="My Profile" subtitle="Update your name, phone number and password." />

<div class="mt-6 grid gap-6 lg:grid-cols-3">

    {{-- Summary --}}
    <aside class="card h-fit p-6 text-center lg:col-span-1">
        <span class="mx-auto grid size-20 place-items-center rounded-full bg-tint-2 text-2xl font-bold text-primary" aria-hidden="true">{{ $initials }}</span>
        <h2 class="mt-4 text-lg font-bold">{{ $user->name }}</h2>
        <p class="break-all text-sm text-slate-500">{{ $user->email }}</p>

        <div class="mt-4 flex justify-center">
            @if ($user->isAdmin())
                <span class="chip bg-violet-50 text-violet-700"><x-icon name="shield" class="size-3.5" />Admin</span>
            @else
                <span class="chip bg-sky-50 text-sky-700"><x-icon name="user" class="size-3.5" />Staff</span>
            @endif
        </div>

        <dl class="mt-6 space-y-3 border-t border-slate-100 pt-5 text-left text-sm">
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Phone</dt><dd class="font-medium">{{ $user->phone ?: '—' }}</dd></div>
            <div class="flex justify-between gap-3"><dt class="text-slate-500">Member since</dt><dd class="font-medium">{{ $user->created_at?->format('d M Y') }}</dd></div>
        </dl>
    </aside>

    <div class="space-y-6 lg:col-span-2">

        {{-- Profile details --}}
        <section class="card p-6 sm:p-8" aria-labelledby="detailsTitle">
            <h2 id="detailsTitle" class="text-lg font-bold">Profile details</h2>
            <p class="text-sm text-slate-500">Your email and role are managed by an administrator.</p>

            <form method="POST" action="{{ route('profile.update') }}" class="mt-6" data-loading-form data-submit-label="Saving…">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="name" class="label">Full name <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" class="field" required maxlength="255" value="{{ old('name', $user->name) }}" @error('name') aria-invalid="true" @enderror>
                        <x-field-error name="name" />
                    </div>

                    <div>
                        <label for="email" class="label">Email</label>
                        <input type="email" id="email" class="field cursor-not-allowed opacity-70" value="{{ $user->email }}" disabled>
                    </div>

                    <div>
                        <label for="phone" class="label">Phone</label>
                        <input type="tel" id="phone" name="phone" class="field" maxlength="30" placeholder="+855 12 345 678" value="{{ old('phone', $user->phone) }}" @error('phone') aria-invalid="true" @enderror>
                        <x-field-error name="phone" />
                    </div>

                    <div>
                        <label for="role" class="label">Role</label>
                        <input type="text" id="role" class="field cursor-not-allowed capitalize opacity-70" value="{{ $user->role }}" disabled>
                        <p class="hint">You cannot change your own role.</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save profile</span></button>
                </div>
            </form>
        </section>

        {{-- Change password --}}
        <section class="card p-6 sm:p-8" aria-labelledby="passwordTitle">
            <h2 id="passwordTitle" class="text-lg font-bold">Change password</h2>
            <p class="text-sm text-slate-500">Enter your current password first. Use at least 8 characters.</p>

            <form method="POST" action="{{ route('profile.password') }}" class="mt-6" data-loading-form data-submit-label="Updating…">
                @csrf
                @method('PUT')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="current_password" class="label">Current password <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="password" id="current_password" name="current_password" class="field" required autocomplete="current-password" @if($pw->has('current_password')) aria-invalid="true" @endif>
                        @if ($pw->has('current_password'))
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-600" role="alert"><x-icon name="alert" class="size-4 shrink-0" />{{ $pw->first('current_password') }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="new_password" class="label">New password <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="password" id="new_password" name="password" class="field" required minlength="8" autocomplete="new-password" @if($pw->has('password')) aria-invalid="true" @endif>
                        @if ($pw->has('password'))
                            <p class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-600" role="alert"><x-icon name="alert" class="size-4 shrink-0" />{{ $pw->first('password') }}</p>
                        @endif
                    </div>

                    <div>
                        <label for="new_password_confirmation" class="label">Confirm new password <span class="text-red-600" aria-hidden="true">*</span></label>
                        <input type="password" id="new_password_confirmation" name="password_confirmation" class="field" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="btn btn-primary" data-submit><x-icon name="lock" class="size-4" /><span data-submit-text>Update password</span></button>
                </div>
            </form>
        </section>
    </div>
</div>

@endsection
