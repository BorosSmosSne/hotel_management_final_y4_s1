<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Hotel Management System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-canvas font-sans text-ink antialiased">

@auth
<header class="sticky top-0 z-30 bg-white/90 shadow-sm shadow-primary/5 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-none items-center gap-6 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 text-lg font-bold text-primary">
            <span class="grid size-9 place-items-center rounded-xl bg-primary text-white"><x-icon name="building" class="size-5" /></span>
            Grand Hotel
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
            <a href="{{ route('dashboard') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-tint text-primary' : 'text-slate-600 hover:bg-tint/60' }}"
               @if(request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
            <a href="{{ route('rooms.index') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('rooms.*') ? 'bg-tint text-primary' : 'text-slate-600 hover:bg-tint/60' }}"
               @if(request()->routeIs('rooms.*')) aria-current="page" @endif>Rooms</a>
            <a href="{{ route('room-types.index') }}"
               class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('room-types.*') ? 'bg-tint text-primary' : 'text-slate-600 hover:bg-tint/60' }}"
               @if(request()->routeIs('room-types.*')) aria-current="page" @endif>Room Types</a>
        </nav>

        <div class="ml-auto hidden items-center gap-3 md:flex">
            <span class="grid size-9 place-items-center rounded-full bg-tint-2 text-sm font-bold text-primary" aria-hidden="true">
                {{ \Illuminate\Support\Str::of(Auth::user()->name)->explode(' ')->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
            </span>
            <span class="text-sm font-semibold">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-soft !px-3 !py-2"><x-icon name="logout" class="size-4" />Logout</button>
            </form>
        </div>

        <button type="button" data-nav-toggle aria-expanded="false" aria-controls="mobileNav" aria-label="Toggle menu"
                class="icon-btn ml-auto bg-tint text-primary md:hidden"><x-icon name="menu" /></button>
    </div>

    <div id="mobileNav" class="hidden border-t border-slate-100 px-4 pb-4 pt-2 md:hidden">
        <a href="{{ route('dashboard') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-tint text-primary' : 'text-slate-600' }}">Dashboard</a>
        <a href="{{ route('rooms.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('rooms.*') ? 'bg-tint text-primary' : 'text-slate-600' }}">Rooms</a>
        <a href="{{ route('room-types.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-medium {{ request()->routeIs('room-types.*') ? 'bg-tint text-primary' : 'text-slate-600' }}">Room Types</a>
        <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3">
            <span class="text-sm font-semibold">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="btn btn-soft !px-3 !py-2">Logout</button>
            </form>
        </div>
    </div>
</header>
@endauth

@hasSection('bare')
    @yield('content')
@else
    <main class="mx-auto w-full max-w-none flex-1 px-4 py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div data-flash role="status" class="mb-5 flex items-center justify-between gap-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200">
                <span class="flex items-center gap-2"><x-icon name="check" class="size-4" />{{ session('success') }}</span>
                <button type="button" data-dismiss aria-label="Dismiss" class="icon-btn !size-7 text-emerald-700 hover:bg-emerald-100"><x-icon name="x" class="size-4" /></button>
            </div>
        @endif

        @yield('content')
    </main>
@endif

@auth
<footer class="mt-8 border-t border-slate-200/70 bg-white">
    <div class="mx-auto flex max-w-none flex-wrap items-center justify-between gap-2 px-4 py-5 text-xs text-slate-500 sm:px-6 lg:px-8">
        <span><b class="text-primary">Grand Hotel</b> &middot; Hotel Management System</span>
        <span>&copy; {{ date('Y') }} Grand Hotel. All rights reserved.</span>
    </div>
</footer>

<dialog id="confirmDialog" aria-labelledby="confirmHeading"
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl bg-white p-0 text-ink shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm">
    <div class="p-6 sm:p-8">
        <div class="flex items-start gap-4">
            <span class="grid size-12 shrink-0 place-items-center rounded-full bg-red-100 text-red-700"><x-icon name="trash" /></span>
            <div>
                <p class="text-xs font-semibold tracking-wide text-red-700">Destructive action</p>
                <h2 id="confirmHeading" data-dialog-heading class="text-xl font-bold">Delete this item?</h2>
            </div>
        </div>
        <div class="mt-5 rounded-2xl bg-tint p-4">
            <p data-dialog-title class="font-semibold"></p>
            <p data-dialog-detail class="mt-1 text-sm text-slate-600"></p>
        </div>
        <p class="mt-4 flex items-start gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-800">
            <x-icon name="alert" class="mt-0.5 size-4 shrink-0" />This action cannot be undone.
        </p>
        <form method="dialog" class="mt-6 flex justify-end gap-3">
            <button class="btn btn-soft">Cancel</button>
            <button type="button" data-dialog-confirm class="btn btn-danger"><x-icon name="trash" class="size-4" />Yes, delete</button>
        </form>
    </div>
</dialog>
@endauth

</body>
</html>