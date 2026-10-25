@extends('layouts.app')
@section('title', 'Edit Room Type')
@section('content')

<x-page-hero eyebrow="Update category" title="Edit Room Type" subtitle="Change the nightly rate, capacity or description of this category.">
    <x-slot:actions>
        <a href="{{ route('room-types.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to List</a>
    </x-slot:actions>
</x-page-hero>

{{-- CHANGED: card form + live preview. data-preview-root / data-bind are UI-only hooks used by resources/js/app.js.
     Form action, @csrf, @method('PUT'), field names and old() are unchanged. --}}
<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]" data-preview-root>
    <div class="card p-6 sm:p-8">
        <x-error-summary />
        <form method="POST" action="{{ route('room-types.update', $roomType) }}" class="space-y-6">
            @csrf
            @method('PUT')
            <div>
                <label for="type_name" class="label">Type name <span class="text-red-600" aria-hidden="true">*</span></label>
                <input type="text" id="type_name" name="type_name" class="field" value="{{ old('type_name', $roomType->type_name) }}" required @error('type_name') aria-invalid="true" @enderror>
                <x-field-error name="type_name" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="price_per_night" class="label">Price per night (USD) <span class="text-red-600" aria-hidden="true">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">$</span>
                        <input type="number" step="0.01" id="price_per_night" name="price_per_night" class="field !pl-8" value="{{ old('price_per_night', $roomType->price_per_night) }}" required @error('price_per_night') aria-invalid="true" @enderror>
                    </div>
                    <x-field-error name="price_per_night" />
                </div>
                <div>
                    <label for="capacity" class="label">Capacity (guests) <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="number" id="capacity" name="capacity" class="field" value="{{ old('capacity', $roomType->capacity) }}" required @error('capacity') aria-invalid="true" @enderror>
                    <x-field-error name="capacity" />
                </div>
            </div>

            <div>
                <label for="description" class="label">Description</label>
                <textarea id="description" name="description" rows="5" class="field" @error('description') aria-invalid="true" @enderror>{{ old('description', $roomType->description) }}</textarea>
                <x-field-error name="description" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('room-types.index') }}" class="btn btn-soft"><x-icon name="arrow-left" class="size-4" />Cancel</a>
                <button class="btn btn-primary"><x-icon name="save" class="size-4" />Update</button>
            </div>
        </form>
    </div>

    <aside class="card h-fit p-6" aria-label="Live preview">
        <p class="text-sm font-semibold text-slate-500">Live preview</p>
        <div class="hero mt-4 p-5">
            <p class="text-lg font-bold" data-bind="type_name" data-empty="Room type name"></p>
            <p class="mt-3 text-3xl font-bold"><span data-bind="price_per_night" data-format="money" data-empty="$0.00"></span><span class="text-sm font-normal text-white/70"> / night</span></p>
        </div>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Capacity</dt><dd class="font-semibold"><span data-bind="capacity" data-empty="—"></span> guests</dd></div>
        </dl>
        <p class="mt-4 text-sm text-slate-600" data-bind="description" data-empty="No description yet."></p>
    </aside>
</div>
@endsection
