@extends('layouts.app')
@section('title', 'Access denied')
@section('content')

<div class="card mx-auto mt-10 max-w-lg p-10 text-center">
    <span class="mx-auto grid size-14 place-items-center rounded-2xl bg-red-50 text-red-600"><x-icon name="lock" class="size-7" /></span>
    <p class="mt-5 text-sm font-semibold text-red-600">Error 403</p>
    <h1 class="mt-1 text-2xl font-bold">Access denied</h1>
    <p class="mt-2 text-slate-600">{{ $exception->getMessage() ?: 'You do not have permission to open this page.' }}</p>
    <a href="{{ route('dashboard') }}" class="btn btn-primary mt-6"><x-icon name="arrow-left" class="size-4" />Back to dashboard</a>
</div>

@endsection
