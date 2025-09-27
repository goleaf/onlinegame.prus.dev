<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $startTime = microtime(true);
        
        ds('LoginController: Login attempt started', [
            'controller' => 'LoginController',
            'method' => 'login',
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_time' => now()
        ]);
        
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        $authStart = microtime(true);
        if (Auth::attempt($credentials, $remember)) {
            $authTime = round((microtime(true) - $authStart) * 1000, 2);
            $request->session()->regenerate();

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            
            ds('LoginController: Login successful', [
                'user_id' => Auth::id(),
                'email' => $request->input('email'),
                'remember' => $remember,
                'auth_time_ms' => $authTime,
                'total_time_ms' => $totalTime,
                'redirect_to' => route('game.dashboard')
            ]);

            return redirect()
                ->intended(route('game.dashboard'))
                ->with('success', 'Welcome to Travian Online! Your empire awaits.');
        }

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        
        ds('LoginController: Login failed', [
            'email' => $request->input('email'),
            'reason' => 'Invalid credentials',
            'total_time_ms' => $totalTime
        ]);

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Handle a logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
