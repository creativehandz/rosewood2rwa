<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class WebAuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard.index');
        }
        
        return view('auth.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            // Redirect to intended URL or dashboard
            return redirect()->intended(route('dashboard.index'))
                ->with('success', 'Welcome back to RWA Management System!');
        }

        return back()
            ->withErrors(['email' => 'Invalid credentials provided.'])
            ->withInput($request->except('password'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show registration form (for admin setup)
     */
    public function showRegister()
    {
        // Only allow registration if no admin user exists
        $adminExists = User::where('email', 'admin@rwa.com')->exists();
        
        if ($adminExists && !Auth::check()) {
            return redirect()->route('login')
                ->with('info', 'Admin account already exists. Please login.');
        }

        return view('auth.register');
    }

    /**
     * Handle registration (for initial admin setup)
     */
    public function register(Request $request)
    {
        // Only allow registration if no admin user exists
        $adminExists = User::where('email', 'admin@rwa.com')->exists();
        
        if ($adminExists) {
            return redirect()->route('login')
                ->with('info', 'Admin account already exists. Please login.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('dashboard.index')
            ->with('success', 'Admin account created successfully! Welcome to RWA Management System.');
    }
}