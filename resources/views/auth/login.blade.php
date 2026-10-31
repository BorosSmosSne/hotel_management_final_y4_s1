@extends('layouts.app')
@section('title', 'Login')
{{-- NEW: 'bare' tells the layout not to wrap this page in the padded <main> --}}
@section('bare', '1')
@section('content')
{{-- CHANGED: Bootstrap card -> split-screen layout. Form action, @csrf, field names and old('email') unchanged. --}}
<div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">

    <aside class="hero relative hidden flex-col justify-between overflow-hidden !rounded-none p-12 lg:flex">
        <div aria-hidden="true" class="pointer-events-none absolute -bottom-32 -left-24 size-[28rem] rounded-full bg-white/10 blur-3xl"></div>
        <div class="relative flex items-center gap-3">
            <span class="grid size-11 place-items-center rounded-2xl bg-white text-primary"><x-icon name="building" class="size-6" /></span>
            <span class="text-xl font-bold">Grand Hotel</span>
        </div>

        <div class="relative max-w-lg">
            <h2 class="text-4xl font-bold leading-tight tracking-tight">Run every room from one place.</h2>
            <p class="mt-4 text-lg text-white/80">Manage your room inventory, nightly rates and availability for the whole property.</p>

            <ul class="mt-10 space-y-3">
                <li class="flex gap-4 rounded-2xl bg-white/10 p-4">
                    <x-icon name="door" class="mt-0.5 size-6 shrink-0" />
                    <div><p class="font-semibold">Room inventory</p><p class="text-sm text-white/75">Add, filter and update every room, with floor and live status.</p></div>
                </li>
                <li class="flex gap-4 rounded-2xl bg-white/10 p-4">
                    <x-icon name="dollar" class="mt-0.5 size-6 shrink-0" />
                    <div><p class="font-semibold">Room types and rates</p><p class="text-sm text-white/75">Set nightly prices and guest capacity once, reuse across rooms.</p></div>
                </li>
                <li class="flex gap-4 rounded-2xl bg-white/10 p-4">
                    <x-icon name="shield" class="mt-0.5 size-6 shrink-0" />
                    <div><p class="font-semibold">Staff-only access</p><p class="text-sm text-white/75">Every page requires a signed-in staff account.</p></div>
                </li>
            </ul>
        </div>
        <p class="relative text-sm text-white/60">&copy; {{ date('Y') }} Grand Hotel</p>
    </aside>

    <section class="flex items-center justify-center px-6 py-12 sm:px-12">
        <div class="w-full max-w-md">
            <span class="mb-6 grid size-12 place-items-center rounded-2xl bg-primary text-white lg:hidden"><x-icon name="building" class="size-6" /></span>
            <h1 class="text-3xl font-bold tracking-tight">Sign in to Grand Hotel</h1>
            <p class="mt-2 text-slate-600">Enter your staff credentials to continue.</p>

            @if (session('success'))
                <div role="status" class="mt-6 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
                    <x-icon name="check" class="size-4" />{{ session('success') }}
                </div>
            @endif

            <div class="mt-6">
                <x-error-summary />
            </div>

            <form method="POST" action="{{ route('login.submit') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="label">Email address</label>
                    <div class="relative">
                        <x-icon name="mail" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                               class="field !pl-12" @error('email') aria-invalid="true" @enderror>
                    </div>
                    <x-field-error name="email" />
                </div>

                <div>
                    <label for="password" class="label">Password</label>
                    <x-password-input name="password" leading required autocomplete="current-password" />
                    <x-field-error name="password" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember" class="size-4 rounded border-slate-300 accent-primary">
                    <label for="remember" class="text-sm text-slate-700">Remember me</label>
                </div>

                <button class="btn btn-primary w-full !py-3.5 text-base">Login <x-icon name="arrow-left" class="size-5 rotate-180" /></button>
            </form>

            <p class="mt-8 text-center text-sm text-slate-500">Need an account? Ask an administrator to create one for you.</p>
        </div>
    </section>
</div>
@endsection
