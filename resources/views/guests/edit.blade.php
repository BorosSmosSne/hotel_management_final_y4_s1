@extends('layouts.app')
@section('title', 'Edit Guest')
@section('content')

<x-page-hero eyebrow="Edit guest" title="Edit Guest" :subtitle="$guest->name . ' · ' . $guest->id_number">
    <x-slot:actions>
        <a href="{{ route('guests.show', $guest) }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to guest</a>
    </x-slot:actions>
</x-page-hero>

<div class="card mt-6 p-6 sm:p-8">
    <x-error-summary />
    <form method="POST" action="{{ route('guests.update', $guest) }}" data-loading-form data-submit-label="Saving…">
        @csrf
        @method('PUT')
        @include('guests.partials.form', ['guest' => $guest])
        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('guests.show', $guest) }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save changes</span></button>
        </div>
    </form>
</div>

@endsection
