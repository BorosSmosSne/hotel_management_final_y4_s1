@extends('layouts.app')
@section('title', 'Users')
@section('content')

@php
    $me = auth()->id();
    // Re-open the reset-password dialog when the server rejected the new password
    $reopen = $errors->hasBag('resetPassword') ? $resetUser : null;
@endphp

<x-page-hero eyebrow="Administration" title="Users" subtitle="Manage who can sign in, their role and their access.">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn btn-white"><x-icon name="plus" class="size-5" />Add User</a>
    </x-slot:actions>
</x-page-hero>

<div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Total users" :value="$total" icon="users" />
    <x-stat-card label="Admins" :value="$admins" icon="shield" />
    <x-stat-card label="Staff" :value="$staff" icon="user" />
    <x-stat-card label="Inactive" :value="$inactive" hint="cannot log in" icon="lock" tone="slate" />
</div>

{{-- Filters (GET form; same auto-submit helper as the Room Booking page) --}}
<form method="GET" action="{{ route('users.index') }}" class="card mt-6 p-4" data-filter-form>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_12rem_12rem_auto]">
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" />
            <label for="u_search" class="sr-only">Search users</label>
            <input type="text" id="u_search" name="search" class="field !pl-12" placeholder="Search name or email…"
                   value="{{ request('search') }}" autocomplete="off" data-filter-text>
        </div>
        <div>
            <label for="u_role" class="sr-only">Role</label>
            <select id="u_role" name="role" class="field" data-filter-auto>
                <option value="">All Roles</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                <option value="staff" @selected(request('role') === 'staff')>Staff</option>
            </select>
        </div>
        <div>
            <label for="u_status" class="sr-only">Status</label>
            <select id="u_status" name="status" class="field" data-filter-auto>
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-soft"><x-icon name="refresh" class="size-4" />Reset</a>
    </div>
</form>

<div class="card mt-6 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[56rem] text-left text-sm">
            <thead class="bg-tint text-xs font-semibold uppercase tracking-wider text-slate-600">
                <tr>
                    <th scope="col" class="px-6 py-4">Name</th>
                    <th scope="col" class="px-6 py-4">Email</th>
                    <th scope="col" class="px-6 py-4">Role</th>
                    <th scope="col" class="px-6 py-4">Status</th>
                    <th scope="col" class="px-6 py-4">Created</th>
                    <th scope="col" class="px-6 py-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $u)
                    @php
                        $isMe      = $u->id === $me;
                        $isSole    = $u->id === $soleAdminId;
                        $protected = $isMe || $isSole;       // cannot be deleted / deactivated
                        $initials  = \Illuminate\Support\Str::of($u->name)->explode(' ')->filter()->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
                    @endphp
                    <tr class="border-t border-slate-100 transition hover:bg-tint/40 {{ $u->is_active ? '' : 'opacity-70' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-tint-2 text-sm font-bold text-primary" aria-hidden="true">{{ $initials }}</span>
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $u->name }} @if ($isMe)<span class="chip ml-1 bg-tint !py-0.5 text-primary">You</span>@endif</p>
                                    @if ($u->phone)<p class="text-xs text-slate-500">{{ $u->phone }}</p>@endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-slate-600">{{ $u->email }}</td>
                        <td class="px-6 py-4">
                            @if ($u->isAdmin())
                                <span class="chip bg-violet-50 text-violet-700"><x-icon name="shield" class="size-3.5" />Admin</span>
                            @else
                                <span class="chip bg-sky-50 text-sky-700"><x-icon name="user" class="size-3.5" />Staff</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($u->is_active)
                                <span class="chip bg-emerald-50 text-emerald-700"><span class="size-1.5 rounded-full bg-emerald-500"></span>Active</span>
                            @else
                                <span class="chip bg-slate-100 text-slate-600"><span class="size-1.5 rounded-full bg-slate-400"></span>Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-500">{{ $u->created_at?->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('users.edit', $u) }}" class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Edit {{ $u->name }}" title="Edit"><x-icon name="pencil" class="size-4" /></a>

                                <button type="button" data-reset-password
                                        data-action="{{ route('users.password', $u) }}" data-user-id="{{ $u->id }}" data-name="{{ $u->name }}"
                                        class="icon-btn bg-tint text-primary hover:bg-tint-2" aria-label="Reset password for {{ $u->name }}" title="Reset password"><x-icon name="lock" class="size-4" /></button>

                                @if ($protected)
                                    <span class="text-xs text-slate-400" title="{{ $isMe ? 'You cannot delete, demote or deactivate your own account.' : 'Last active administrator: it cannot be deleted, demoted or deactivated.' }}">
                                        {{ $isMe ? 'Your account' : 'Last admin' }}
                                    </span>
                                @else
                                    {{-- Activate / deactivate (reversible, so no confirmation dialog) --}}
                                    <form action="{{ route('users.toggle-status', $u) }}" method="POST" class="inline" data-loading-form data-submit-label="…">
                                        @csrf
                                        @if ($u->is_active)
                                            <button class="icon-btn bg-amber-50 text-amber-700 hover:bg-amber-100" data-submit aria-label="Deactivate {{ $u->name }}" title="Deactivate"><x-icon name="ban" class="size-4" /></button>
                                        @else
                                            <button class="icon-btn bg-emerald-50 text-emerald-700 hover:bg-emerald-100" data-submit aria-label="Activate {{ $u->name }}" title="Activate"><x-icon name="check" class="size-4" /></button>
                                        @endif
                                    </form>

                                    <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline"
                                          data-confirm-delete data-heading="Delete this user?"
                                          data-title="{{ $u->name }} ({{ $u->email }})"
                                          data-detail="The account is removed permanently. Bookings they created are kept.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="icon-btn bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete {{ $u->name }}" title="Delete"><x-icon name="trash" class="size-4" /></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-14 text-center text-slate-500">
                        <p class="font-medium text-ink">No users found.</p>
                        <p class="mt-1 text-sm">Try a different search or filter, or <a href="{{ route('users.create') }}" class="font-semibold text-primary hover:underline">add a user</a>.</p>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-6 py-4">
        <p class="text-sm text-slate-500">
            @if ($users->total())Showing <b class="text-ink">{{ $users->firstItem() }}</b> to <b class="text-ink">{{ $users->lastItem() }}</b> of <b class="text-ink">{{ $users->total() }}</b> users @endif
        </p>
        {{ $users->links() }}
    </div>
</div>

{{-- Reset password dialog --}}
<dialog id="passwordDialog" aria-labelledby="pwHeading" @if ($reopen) data-open @endif
        class="m-auto w-[calc(100%-2rem)] max-w-md rounded-3xl bg-white p-0 text-ink shadow-2xl backdrop:bg-slate-900/50 backdrop:backdrop-blur-sm">
    <form method="POST" action="{{ $reopen ? route('users.password', $reopen) : '#' }}" data-loading-form data-submit-label="Saving…">
        @csrf
        @method('PUT')
        <input type="hidden" name="reset_user_id" value="{{ $reopen?->id }}" data-pw-user-id>

        <div class="p-6 sm:p-8">
            <div class="flex items-start gap-4">
                <span class="grid size-12 shrink-0 place-items-center rounded-full bg-tint text-primary"><x-icon name="lock" /></span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Reset password</p>
                    <h2 id="pwHeading" class="text-xl font-bold" data-pw-name>{{ $reopen?->name }}</h2>
                </div>
            </div>

            @php $pwErr = $errors->getBag('resetPassword'); @endphp
            @if ($pwErr->any())
                <div class="mt-5 flex items-start gap-2 rounded-xl bg-red-50 px-3 py-2.5 text-sm font-medium text-red-800" role="alert">
                    <x-icon name="alert" class="mt-0.5 size-4 shrink-0" />{{ $pwErr->first() }}
                </div>
            @endif

            <div class="mt-5 space-y-4">
                <div>
                    <label for="pw_new" class="label">New password</label>
                    <input type="password" id="pw_new" name="password" class="field" required minlength="8" autocomplete="new-password">
                    <p class="hint">At least 8 characters. Share it with the user securely.</p>
                </div>
                <div>
                    <label for="pw_confirm" class="label">Confirm new password</label>
                    <input type="password" id="pw_confirm" name="password_confirmation" class="field" required minlength="8" autocomplete="new-password">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" data-pw-close class="btn btn-soft">Cancel</button>
                <button type="submit" class="btn btn-primary" data-submit><x-icon name="save" class="size-4" /><span data-submit-text>Save password</span></button>
            </div>
        </div>
    </form>
</dialog>

@endsection
