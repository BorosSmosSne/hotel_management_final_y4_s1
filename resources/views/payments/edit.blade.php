@extends('layouts.app')
@section('title', 'Edit Payment')
@section('content')

<x-page-hero eyebrow="Admin only" title="Edit Payment"
             :subtitle="'Booking #' . $booking->id . ' · ' . $booking->guest_name . ' · Room ' . $booking->room->room_number">
    <x-slot:actions>
        <a href="{{ route('bookings.show', $booking) }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to booking</a>
    </x-slot:actions>
</x-page-hero>

<div class="card mt-6 p-6 sm:p-8">
    <x-error-summary />
    <form method="POST" action="{{ route('payments.update', $payment) }}" data-loading-form data-submit-label="Saving…">
        @csrf
        @method('PUT')
        @include('payments.partials.fields', ['payment' => $payment, 'booking' => $booking])
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save changes</span></button>
        </div>
    </form>
</div>

@endsection
