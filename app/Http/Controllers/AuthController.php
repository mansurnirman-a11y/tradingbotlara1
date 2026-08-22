<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        $role = $request->route('role', 'user');
        return view('auth.login', compact('role'));
    }

    public function login(Request $request)
    {
        $role = $request->route('role', 'user');

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            // Security check: Only allow login if user's actual role matches the portal they are using
            // (Except superadmins can log into admin portal, or we can enforce strict matching)
            if ($user->role !== $role) {
                Auth::logout();
                return back()->withErrors([
                    'email' => "Access Denied. You do not have {$role} privileges.",
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            if (in_array($user->role, ['admin', 'superadmin'])) {
                return redirect()->intended('/admin/dashboard');
            }
            return redirect()->intended('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister(Request $request)
    {
        $role = $request->route('role', 'user');
        return view('auth.register', compact('role'));
    }

    public function register(Request $request)
    {
        $role = $request->route('role', 'user');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile_number' => $validated['mobile_number'],
            'password' => Hash::make($validated['password']),
            'role' => $role, // Automatically assign the role based on the URL they registered at
            'is_active' => true,
        ]);

        Auth::login($user);

        if (in_array($role, ['admin', 'superadmin'])) {
            return redirect('/admin/dashboard');
        }
        return redirect('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
