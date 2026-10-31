<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Deactivated accounts cannot log in. Tell them why, but only if the password was right
        // (so the message does not reveal which emails exist).
        $existing = User::where('email', $credentials['email'])->first();
        if ($existing && ! $existing->is_active && Hash::check($credentials['password'], $existing->password)) {
            ActivityLogger::log('login_failed', "Deactivated account {$existing->email} tried to sign in", null, $existing->id);

            return back()->withErrors(['email' => 'Your account has been deactivated. Please contact an administrator.'])->onlyInput('email');
        }

        if (Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('room-types.index'))
                ->with('success', 'Welcome back, ' . Auth::user()->name . '!');
        }

        return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Logged out successfully.');
    }
}