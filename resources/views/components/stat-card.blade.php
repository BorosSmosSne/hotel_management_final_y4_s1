{{-- NEW: summary tile. Callers wrap it in @isset so pages still render if the controller doesn't supply the number. --}}
@props(['label', 'value', 'hint' => null, 'icon' => 'grid', 'tone' => 'primary'])
@php
    $tones = [
        'primary' => 'bg-tint text-primary',
        'green' => 'bg-emerald-50 text-emerald-600',
        'red' => 'bg-red-50 text-red-600',
        'amber' => 'bg-amber-50 text-amber-600',
    ];
@endphp
<div class="card flex items-start justify-between gap-3 p-5">
    <div>
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <p class="mt-2 text-3xl font-bold tracking-tight">{{ $value }}</p>
        @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    </div>
    <span class="grid size-11 shrink-0 place-items-center rounded-xl {{ $tones[$tone] ?? $tones['primary'] }}">
        <x-icon :name="$icon" class="size-5" />
    </span>
</div>
