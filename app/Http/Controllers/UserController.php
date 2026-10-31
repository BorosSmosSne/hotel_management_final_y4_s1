<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\SaveUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * User management. Admin only: every route sits behind the "role:admin" middleware (routes/web.php).
 *
 * Safety rules enforced here, on the server:
 *  - you cannot delete, demote or deactivate your own account
 *  - the system always keeps at least one ACTIVE admin
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(in_array($request->query('role'), [User::ROLE_ADMIN, User::ROLE_STAFF], true),
                fn ($q) => $q->where('role', $request->query('role')))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        // Used to protect the only remaining active admin in the UI (the server enforces it too).
        $activeAdminIds = User::where('role', User::ROLE_ADMIN)->where('is_active', true)->pluck('id');

        return view('users.index', [
            'users'       => $users,
            'total'       => User::count(),
            'admins'      => User::where('role', User::ROLE_ADMIN)->count(),
            'staff'       => User::where('role', User::ROLE_STAFF)->count(),
            'inactive'    => User::where('is_active', false)->count(),
            'soleAdminId' => $activeAdminIds->count() === 1 ? $activeAdminIds->first() : null,
            // re-open the reset-password dialog after a validation error
            'resetUser'   => $request->old('reset_user_id') ? User::find($request->old('reset_user_id')) : null,
        ]);
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'role'      => $data['role'],
            'is_active' => $data['status'] === 'active',
            'password'  => Hash::make($data['password']),
        ]);

        return redirect()->route('users.index')->with('success', "User {$user->name} was created.");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $newActive = $data['status'] === 'active';
        $this->guardAdminSafety($request, $user, $data['role'], $newActive);

        $user->fill([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? null,
            'role'      => $data['role'],
            'is_active' => $newActive,
        ]);

        // Blank password = keep the current one
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', "User {$user->name} was updated.");
    }

    /** Activate / deactivate (deactivated users cannot log in). */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $activate = ! $user->is_active;

        if (! $activate) {
            if ($user->id === $request->user()->id) {
                return back()->with('error', 'You cannot deactivate your own account.');
            }
            if ($user->isLastActiveAdmin()) {
                return back()->with('error', 'This is the last active administrator and cannot be deactivated.');
            }
        }

        $user->update(['is_active' => $activate]);

        return back()->with('success', $activate
            ? "{$user->name} is active again."
            : "{$user->name} was deactivated and can no longer log in.");
    }

    /** Admin sets a new password for a user (from the dialog on the list page). */
    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        $user->update(['password' => Hash::make($request->validated('password'))]);

        return back()->with('success', "Password updated for {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isLastActiveAdmin()) {
            return back()->with('error', 'This is the last active administrator and cannot be deleted.');
        }

        $user->delete();

        return back()->with('success', "User {$user->name} was deleted.");
    }

    /* ---------- helpers ---------- */

    /**
     * Blocks any edit that would remove an active admin: self-demotion / self-deactivation,
     * or demoting / deactivating the last active admin.
     */
    private function guardAdminSafety(Request $request, User $user, string $newRole, bool $newActive): void
    {
        $losesAdmin = $user->isAdmin() && $user->is_active
            && ($newRole !== User::ROLE_ADMIN || ! $newActive);

        if (! $losesAdmin) {
            return;
        }

        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages(['role' => 'You cannot demote or deactivate your own account.']);
        }

        if ($user->isLastActiveAdmin()) {
            throw ValidationException::withMessages(['role' => 'This is the last active administrator. Make another user an active admin first.']);
        }
    }
}
