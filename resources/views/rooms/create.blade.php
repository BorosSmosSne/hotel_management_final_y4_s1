@extends('layouts.app')
@section('title', 'Add Room')
@section('content')

<x-page-hero eyebrow="New room" title="Add Room" subtitle="Register a new room in the hotel inventory.">
    <x-slot:actions>
        <a href="{{ route('rooms.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to List</a>
    </x-slot:actions>
</x-page-hero>

{{-- CHANGED: card form + live preview. Action, @csrf, no method spoofing, field names, old() and the $roomTypes loop are unchanged.
     ADDED: old('status') repopulation on the create form (it was missing before). Option data-price feeds the preview only. --}}
<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]" data-preview-root>
    <div class="card p-6 sm:p-8">
        <x-error-summary />
        <form method="POST" action="{{ route('rooms.store') }}" class="space-y-6">
            @csrf
            
            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="room_number" class="label">Room number <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="text" id="room_number" name="room_number" class="field" value="{{ old('room_number') }}" required @error('room_number') aria-invalid="true" @enderror>
                    <x-field-error name="room_number" />
                </div>
                <div>
                    <label for="floor" class="label">Floor <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input type="number" id="floor" name="floor" class="field" value="{{ old('floor') }}" required min="0" step="1" inputmode="numeric" data-floor-input aria-describedby="floor-hint" @error('floor') aria-invalid="true" @enderror>
                    <p id="floor-hint" class="hint">Whole number from 0 up. Use 0 for the ground floor.</p>
                    <x-field-error name="floor" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="room_type_id" class="label">Room type <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="room_type_id" name="room_type_id" class="field" required @error('room_type_id') aria-invalid="true" @enderror>
                        <option value="">-- Select Type --</option>
                        @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}" data-price="{{ $type->price_per_night }}" {{ old('room_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->type_name }}
                            </option>
                        @endforeach
                    </select>
                    <x-field-error name="room_type_id" />
                </div>
                <div>
                    <label for="status" class="label">Status <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="status" name="status" class="field" required @error('status') aria-invalid="true" @enderror>
                        @foreach(['available', 'occupied', 'maintenance'] as $status)
                            <option value="{{ $status }}" {{ old('status') == $status ? 'selected' : '' }}>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                    <x-field-error name="status" />
                </div>
            </div>

            <div>
                <label for="description" class="label">Description</label>
                <textarea id="description" name="description" rows="5" class="field" @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                <x-field-error name="description" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6">
                <a href="{{ route('rooms.index') }}" class="btn btn-soft"><x-icon name="arrow-left" class="size-4" />Cancel</a>
                <button class="btn btn-primary"><x-icon name="save" class="size-4" />Save</button>
            </div>
        </form>
    </div>

    <aside class="card h-fit p-6" aria-label="Live preview">
        <p class="text-sm font-semibold text-slate-500">Live preview</p>
        <div class="hero mt-4 p-5">
            <p class="text-sm text-white/75" data-bind="room_type_id" data-empty="Choose a room type"></p>
            <p class="mt-1 text-3xl font-bold">Room <span data-bind="room_number" data-empty="—"></span></p>
            <p class="mt-3 text-lg font-semibold"><span data-bind="room_type_id" data-bind-data="price" data-format="money" data-empty="$0.00"></span><span class="text-sm font-normal text-white/70"> / night</span></p>
        </div>
        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between"><dt class="text-slate-500">Floor</dt><dd class="font-semibold" data-bind="floor" data-empty="—"></dd></div>
            <div class="flex justify-between"><dt class="text-slate-500">Status</dt><dd class="font-semibold" data-bind="status" data-empty="—"></dd></div>
        </dl>
    </aside>
</div>
@endsection
