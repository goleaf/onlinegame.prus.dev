<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\RealTimeGameService;

class WebSocketAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'error' => 'Unauthorized - User not authenticated',
            ], 401);
        }

        $user = Auth::user();

        // Mark user as online for real-time features
        RealTimeGameService::markUserOnline($user->id);

        // Add user info to request for use in controllers
        $request->merge(['authenticated_user' => $user]);

        return $next($request);
    }
}
