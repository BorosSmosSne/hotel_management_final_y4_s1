<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Hotel Management System')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="group/sb flex min-h-full flex-col bg-canvas font-sans text-ink antialiased">
<script>try{document.body.dataset.collapsed=localStorage.getItem('sidebar-collapsed')==='1'?'true':'false'}catch(e){}</script>

@auth
@php
    // Sidebar menu: label, route, active pattern, icon
    $browse = [
        ['Dashboard',  'dashboard',        'dashboard',    'grid'],
        ['Room Booking', 'bookings.index', 'bookings.*',   'calendar'],
        ['Rooms',      'rooms.index',      'rooms.*',      'door'],
        ['Room Types', 'room-types.index', 'room-types.*', 'bed'],
    ];
    $quick = [
        ['Add Room',      'rooms.create',      'plus'],
    ];
    if (Auth::user()->isAdmin()) {
        $quick[] = ['Add Room Type', 'room-types.create', 'plus'];
    }
    $initials = \Illuminate\Support\Str::of(Auth::user()->name)->explode(' ')->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');

    // Class helpers (full literals so Tailwind can see them). "Collapsed" only applies on lg+.
    // Floors submenu (Room Booking only). $sidebarFloors comes from the view composer in AppServiceProvider:
    // [['floor' => 3, 'total' => 4, 'available' => 2], ...] built from the rooms table.
    $floorList  = $sidebarFloors ?? collect();
    $onBookings = request()->routeIs('bookings.index');
    $curFloor   = ($onBookings && request()->filled('floor')) ? (string) request('floor') : null;
    $keepQuery  = $onBookings ? request()->only(['search', 'status', 'room_type_id', 'date']) : []; // keep other filters
    $floorUrl   = fn ($f = null) => route('bookings.index', array_filter(
        array_merge($keepQuery, ['floor' => $f]),
        fn ($v) => $v !== null && $v !== ''
    ));
    $floorsOpen = $curFloor !== null;   // stay open on load when a floor is selected

    $hid = 'lg:group-data-[collapsed=true]/sb:hidden';
    $ctr = 'lg:group-data-[collapsed=true]/sb:justify-center lg:group-data-[collapsed=true]/sb:px-0';
@endphp

{{-- Left side menu --}}
<aside id="sidebar" aria-label="Sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 overflow-hidden whitespace-nowrap -translate-x-full flex-col bg-white shadow-xl shadow-primary/10 transition-[transform,width] duration-200 lg:translate-x-0 lg:shadow-none lg:ring-1 lg:ring-slate-900/5 lg:group-data-[collapsed=true]/sb:w-20">

    <div class="flex h-16 shrink-0 items-center justify-between px-5 lg:group-data-[collapsed=true]/sb:justify-center lg:group-data-[collapsed=true]/sb:gap-1 lg:group-data-[collapsed=true]/sb:px-0">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5 whitespace-nowrap text-lg font-bold text-primary" title="Grand Hotel">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary text-white"><x-icon name="building" class="size-5" /></span>
            <span class="{{ $hid }}">Grand Hotel</span>
        </a>

        {{-- mobile: close --}}
        <button type="button" data-sidebar-toggle aria-label="Close menu" class="icon-btn text-slate-500 hover:bg-tint lg:hidden"><x-icon name="x" /></button>

        {{-- desktop: collapse (shown when expanded) --}}
        <button type="button" data-sidebar-collapse aria-expanded="true" aria-label="Collapse sidebar" title="Collapse sidebar"
                class="icon-btn !size-7 shrink-0 text-slate-400 hover:bg-tint hover:text-primary max-lg:hidden {{ $hid }}"><x-icon name="chevrons-left" class="!size-4" /></button>

        {{-- desktop: expand (shown next to the logo when collapsed) --}}
        <button type="button" data-sidebar-collapse aria-expanded="false" aria-label="Expand sidebar" title="Expand sidebar"
                class="icon-btn hidden !size-6 shrink-0 text-slate-400 hover:bg-tint hover:text-primary lg:group-data-[collapsed=true]/sb:grid"><x-icon name="chevrons-right" class="!size-4" /></button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4" aria-label="Main">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400 {{ $hid }}">Browse</p>
        <ul class="space-y-1">
            @foreach ($browse as [$label, $route, $pattern, $icon])
                @php $active = request()->routeIs($pattern); @endphp
                @if ($route === 'bookings.index')
                {{-- Room Booking: link + chevron that expands the floors submenu --}}
                <li>
                    <div class="group flex items-center rounded-xl transition {{ $active ? 'bg-primary text-white shadow-md shadow-primary/25' : 'text-slate-600 hover:bg-tint hover:text-primary' }}">
                        <a href="{{ route($route) }}" title="{{ $label }}" @if($active) aria-current="page" @endif
                           class="flex min-w-0 flex-1 items-center gap-3 px-3 py-2.5 text-sm font-medium {{ $ctr }}">
                            <x-icon :name="$icon" class="size-5 shrink-0 {{ $active ? '' : 'text-slate-400 group-hover:text-primary' }}" />
                            <span class="truncate {{ $hid }}">{{ $label }}</span>
                        </a>
                        <button type="button" data-submenu-toggle aria-controls="floorMenu" aria-expanded="{{ $floorsOpen ? 'true' : 'false' }}"
                                aria-label="Show floors"
                                class="icon-btn mr-1 !size-8 shrink-0 {{ $active ? 'text-white/80 hover:bg-white/15' : 'text-slate-400 hover:bg-tint-2 hover:text-primary' }} {{ $hid }}">
                            <x-icon name="chevron-down" data-chevron class="size-4 transition-transform duration-200 {{ $floorsOpen ? 'rotate-180' : '' }}" />
                        </button>
                    </div>

                    {{-- grid-rows 0fr -> 1fr gives a smooth height animation without measuring in JS --}}
                    <div id="floorMenu" @unless($floorsOpen) inert @endunless
                         class="grid transition-[grid-template-rows] duration-200 ease-out {{ $floorsOpen ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]' }} {{ $hid }}">
                        <ul class="ml-6 min-h-0 space-y-0.5 overflow-hidden border-l border-slate-200 pl-3" aria-label="Filter by floor">
                            <li class="pt-1">
                                <a href="{{ $floorUrl() }}" @if($onBookings && $curFloor === null) aria-current="true" @endif
                                   class="flex items-center justify-between rounded-lg px-3 py-2 text-[13px] font-medium transition {{ ($onBookings && $curFloor === null) ? 'bg-tint font-semibold text-primary' : 'text-slate-600 hover:bg-tint/60 hover:text-primary' }}">
                                    All Floors
                                </a>
                            </li>
                            @foreach ($floorList as $fl)
                                @php $isFloor = $curFloor !== null && $curFloor === (string) $fl['floor']; @endphp
                                <li>
                                    <a href="{{ $floorUrl($fl['floor']) }}" @if($isFloor) aria-current="true" @endif
                                       class="flex items-center justify-between gap-2 rounded-lg px-3 py-2 text-[13px] font-medium transition {{ $isFloor ? 'bg-tint font-semibold text-primary' : 'text-slate-600 hover:bg-tint/60 hover:text-primary' }}">
                                        <span>Floor {{ $fl['floor'] }}</span>
                                        <span title="{{ $fl['available'] }} of {{ $fl['total'] }} rooms available today"
                                              class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $fl['available'] > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $fl['available'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                            <li class="pb-1" aria-hidden="true"></li>
                        </ul>
                    </div>
                </li>
                @else
                @php $active = request()->routeIs($pattern); @endphp
                <li>
                    <a href="{{ route($route) }}" title="{{ $label }}" @if($active) aria-current="page" @endif
                       class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $ctr }} {{ $active ? 'bg-primary text-white shadow-md shadow-primary/25' : 'text-slate-600 hover:bg-tint hover:text-primary' }}">
                        <x-icon :name="$icon" class="size-5 shrink-0 {{ $active ? '' : 'text-slate-400 group-hover:text-primary' }}" />
                        <span class="truncate {{ $hid }}">{{ $label }}</span>
                    </a>
                </li>
                @endif
            @endforeach
        </ul>

        <p class="mt-8 px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-400 {{ $hid }}">Quick actions</p>
        <hr class="mx-2 my-4 hidden border-slate-100 lg:group-data-[collapsed=true]/sb:block">
        <ul class="space-y-1">
            @foreach ($quick as [$label, $route, $icon])
                <li>
                    <a href="{{ route($route) }}" title="{{ $label }}"
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-tint hover:text-primary {{ $ctr }}">
                        <span class="grid size-5 shrink-0 place-items-center rounded-md bg-tint-2 text-primary"><x-icon :name="$icon" class="size-3.5" /></span>
                        <span class="truncate {{ $hid }}">{{ $label }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="shrink-0 border-t border-slate-100 p-4">
        <div class="flex items-center gap-3 {{ $ctr }}">
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-tint-2 text-sm font-bold text-primary" aria-hidden="true" title="{{ Auth::user()->name }}">{{ $initials }}</span>
            <span class="min-w-0 flex-1 truncate text-sm font-semibold {{ $hid }}">{{ Auth::user()->name }}
                <span class="block text-xs font-normal capitalize text-slate-500">{{ Auth::user()->role }}</span>
            </span>
        </div>
        <form action="{{ route('logout') }}" method="POST" class="mt-3">
            @csrf
            <button class="btn btn-soft w-full !py-2 lg:group-data-[collapsed=true]/sb:!px-0" title="Logout" aria-label="Logout"><x-icon name="logout" class="size-4" /><span class="{{ $hid }}">Logout</span></button>
        </form>
    </div>
</aside>

{{-- Mobile backdrop --}}
<div id="sidebarBackdrop" data-sidebar-toggle class="fixed inset-0 z-30 hidden bg-slate-900/40 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

<div class="flex min-h-screen flex-1 flex-col transition-[padding] duration-200 lg:pl-64 lg:group-data-[collapsed=true]/sb:pl-20">
    {{-- Mobile top bar --}}
    <header class="sticky top-0 z-20 flex h-16 items-center gap-3 bg-white/90 px-4 shadow-sm shadow-primary/5 backdrop-blur lg:hidden">
        <button type="button" data-sidebar-toggle aria-expanded="false" aria-controls="sidebar" aria-label="Open menu"
                class="icon-btn bg-tint text-primary"><x-icon name="menu" /></button>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-bold text-primary">
            <span class="grid size-8 place-items-center rounded-lg bg-primary text-white"><x-icon name="building" class="size-4" /></span>
            Grand Hotel
        </a>
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

        @if (session('error'))
            <div data-flash role="alert" class="mb-5 flex items-center justify-between gap-3 rounded-xl bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-200">
                <span class="flex items-center gap-2"><x-icon name="alert" class="size-4" />{{ session('error') }}</span>
                <button type="button" data-dismiss aria-label="Dismiss" class="icon-btn !size-7 text-red-700 hover:bg-red-100"><x-icon name="x" class="size-4" /></button>
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
            <button type="button" data-dialog-confirm class="btn btn-danger"><x-icon name="trash" class="size-4" /><span data-dialog-confirm-label>Yes, delete</span></button>
        </form>
    </div>
</dialog>
</div>
@endauth

</body>
</html>