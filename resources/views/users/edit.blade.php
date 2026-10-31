@extends('layouts.app')
@section('title', 'Edit User')
@section('content')

<x-page-hero eyebrow="Edit account" title="Edit User" :subtitle="$user->name . ' · ' . $user->email">
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to Users</a>
    </x-slot:actions>
</x-page-hero>

<div class="card mt-6 p-6 sm:p-8">
    <x-error-summary />
    <form method="POST" action="{{ route('users.update', $user) }}" data-loading-form data-submit-label="Saving…">
        @csrf
        @method('PUT')
        @include('users.partials.form', ['user' => $user])

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save changes</span></button>
        </div>
    </form>
</div>

@endsection
