{{-- NEW: gradient header banner used on every page. Slot "actions" is optional. --}}
@props(['title', 'subtitle' => null, 'eyebrow' => null])
<section class="hero relative overflow-hidden px-6 py-7 sm:px-9 sm:py-9">
    <div aria-hidden="true" class="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-white/10 blur-2xl"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-5">
        <div class="max-w-2xl">
            @if ($eyebrow)
                <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">
                    <span class="size-1.5 rounded-full bg-white"></span>{{ $eyebrow }}
                </span>
            @endif
            <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-2 text-white/80">{{ $subtitle }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="flex flex-wrap gap-3">{{ $actions }}</div>
        @endisset
    </div>
</section>
