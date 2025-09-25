<?php

namespace App\Http\Middleware;

use App\Models\Game\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class GameAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to access the game.');
        }

        // Check if user has a player account
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player) {
            return redirect()->route('game.no-player')->with('error', 'No player account found. Please create a player account.');
        }

        // Check if player is active
        if (!$player->is_active) {
            return redirect()->route('game.suspended')->with('error', 'Your player account has been suspended.');
        }

        // Add player to request for easy access
        $request->merge(['player' => $player]);

        return $next($request);
    }
}

