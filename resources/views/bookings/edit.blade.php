@extends('layouts.app')
@section('title', 'Edit Booking')
@section('content')

<x-page-hero eyebrow="Admin only" title="Edit Booking"
             :subtitle="'Room ' . $booking->room->room_number . ' · ' . ($booking->room->roomType->type_name ?? 'N/A')">
    <x-slot:actions>
        <a href="{{ route('bookings.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to Bookings</a>
    </x-slot:actions>
</x-page-hero>

<div class="card mt-6 p-6 sm:p-8">
    <x-error-summary />
    <form method="POST" action="{{ route('bookings.update', $booking) }}" data-booking-form data-submit-label="Saving…"
          data-price="{{ $booking->price_per_night }}" data-capacity="{{ $booking->room->roomType->capacity ?? '' }}">
        @csrf
        @method('PUT')
        @include('bookings.partials.fields', ['booking' => $booking, 'idp' => 'e_'])

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('bookings.index') }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save changes</span></button>
        </div>
    </form>
</div>

@endsection
