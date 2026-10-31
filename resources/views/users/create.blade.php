@extends('layouts.app')
@section('title', 'Add User')
@section('content')

<x-page-hero eyebrow="New account" title="Add User" subtitle="Create a login for a new admin or staff member.">
    <x-slot:actions>
        <a href="{{ route('users.index') }}" class="btn btn-glass"><x-icon name="arrow-left" class="size-5" />Back to Users</a>
    </x-slot:actions>
</x-page-hero>

<div class="card mt-6 p-6 sm:p-8">
    <x-error-summary />
    <form method="POST" action="{{ route('users.store') }}" data-loading-form data-submit-label="Saving…">
        @csrf
        @include('users.partials.form', ['user' => null])

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('users.index') }}" class="btn btn-soft">Cancel</a>
            <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Create user</span></button>
        </div>
    </form>
</div>

@endsection
