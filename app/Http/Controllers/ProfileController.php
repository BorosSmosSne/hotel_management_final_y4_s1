<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * My Profile: available to every signed-in user (Admin and Staff).
 * Only ever touches the CURRENT user, so there is no id in the URL to tamper with.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        // validated() only contains name + phone, so role / status can never be changed here
        $request->user()->update($request->validated());

        return redirect()->route('profile.edit')->with('success', 'Your profile was updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => Hash::make($request->validated('password'))]);

        return redirect()->route('profile.edit')->with('success', 'Your password was changed.');
    }
}
