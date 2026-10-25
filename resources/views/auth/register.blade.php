@extends('layouts.app')
@section('title', 'Register')
@section('bare', '1')
@section('content')
{{-- CHANGED: Bootstrap card -> centered card. Action, @csrf, field names and old() unchanged. --}}
<div class="flex min-h-screen items-center justify-center bg-linear-to-br from-tint via-canvas to-tint-2 px-4 py-12">
    <div class="card w-full max-w-md p-8 sm:p-10">
        <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-linear-to-br from-primary to-secondary text-white"><x-icon name="bed" class="size-7" /></span>
        <h1 class="mt-5 text-center text-2xl font-bold tracking-tight">Create staff account</h1>
        <p class="mt-1 text-center text-sm text-slate-600">Register for access to the hotel management system.</p>

        <div class="mt-6"><x-error-summary /></div>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf
            <div>
                <label for="name" class="label">Full name</label>
                <div class="relative">
                    <x-icon name="user" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                           class="field !pl-12" @error('name') aria-invalid="true" @enderror>
                </div>
                <x-field-error name="name" />
            </div>

            <div>
                <label for="email" class="label">Email address</label>
                <div class="relative">
                    <x-icon name="mail" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                           class="field !pl-12" @error('email') aria-invalid="true" @enderror>
                </div>
                <x-field-error name="email" />
            </div>

            <div>
                <label for="password" class="label">Password</label>
                <div class="relative">
                    <x-icon name="lock" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           class="field !px-12" @error('password') aria-invalid="true" @enderror>
                    <button type="button" data-toggle-password="password" aria-label="Show password" aria-pressed="false"
                            class="icon-btn absolute right-2 top-1/2 -translate-y-1/2 text-slate-500 hover:bg-tint-2">
                        <span data-eye><x-icon name="eye" /></span>
                        <span data-eye-off class="hidden"><x-icon name="eye-off" /></span>
                    </button>
                </div>
                {{-- Hint matches the real rule in AuthController (min:6) --}}
                <p class="hint">At least 6 characters.</p>
                <x-field-error name="password" />
            </div>

            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <div class="relative">
                    <x-icon name="shield" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="field !pl-12">
                </div>
            </div>

            <button class="btn btn-primary w-full !py-3.5 text-base">Register <x-icon name="arrow-left" class="size-5 rotate-180" /></button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600">Already registered? <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">Login</a></p>
    </div>
</div>
@endsection
