{{-- NEW: same $errors->any() list as before, restyled. --}}
@if ($errors->any())
    <div class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200" role="alert">
        <p class="mb-1 flex items-center gap-2 font-semibold"><x-icon name="alert" class="size-4" />Please fix the following:</p>
        <ul class="list-inside list-disc space-y-0.5">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
