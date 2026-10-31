@extends('layouts.app')
@section('title', 'Add Guest')
@section('content')

<x-page-hero eyebrow="New guest" title="Add Guest" subtitle="Save a guest once and reuse them for every booking.">
    <x-slot:actions>
        <a href="{{ route('guests.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to Guests</a>
    </x-slot:actions>
</x-page-hero>

<div class="card mt-6 p-6 sm:p-8">
    <x-error-summary />
    <form method="POST" action="{{ route('guests.store') }}" data-loading-form data-submit-label="Saving…">
        @csrf
        @include('guests.partials.form', ['guest' => null])
        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('guests.index') }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save guest</span></button>
        </div>
    </form>
</div>

@endsection
