<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ValidationHelperTrait;

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

        LoggingUtil::info('LoginController: Login attempt started', [
            'controller' => 'LoginController',
            'method' => 'login',
            'email' => $request->input('email'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_time' => now(),
        ]);

        $validated = $this->validateRequestDataOrFail($request, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $validated;
        $remember = $request->boolean('remember');

        $authStart = microtime(true);
        if (Auth::attempt($credentials, $remember)) {
            $authTime = round((microtime(true) - $authStart) * 1000, 2);
            $request->session()->regenerate();

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            LoggingUtil::info('Login successful', [
                'user_id' => Auth::id(),
                'email' => $validated['email'],
                'remember' => $remember,
                'auth_time_ms' => $authTime,
                'total_time_ms' => $totalTime,
                'redirect_to' => route('game.dashboard'),
            ], 'auth');

            return redirect()
                ->intended(route('game.dashboard'))
                ->with('success', 'Welcome to Travian Online! Your empire awaits.');
        }

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        LoggingUtil::warning('Login failed', [
            'email' => $validated['email'],
            'reason' => 'Invalid credentials',
            'total_time_ms' => $totalTime,
        ], 'auth');

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Handle a logout request.
     */
    public function logout(Request $request)
    {
        $startTime = microtime(true);
        $userId = Auth::id();

        LoggingUtil::info('LoginController: Logout started', [
            'controller' => 'LoginController',
            'method' => 'logout',
            'user_id' => $userId,
            'logout_time' => now(),
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        LoggingUtil::info('Logout completed', [
            'user_id' => $userId,
            'total_time_ms' => $totalTime,
            'redirect_to' => route('login'),
        ], 'auth');

        return redirect()
            ->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}
