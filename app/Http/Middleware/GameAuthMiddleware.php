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
        $startTime = microtime(true);
        
        ds('GameAuthMiddleware: Authentication check started', [
            'middleware' => 'GameAuthMiddleware',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'auth_check_time' => now()
        ]);
        
        // Check if user is authenticated
        if (!Auth::check()) {
            ds('GameAuthMiddleware: User not authenticated', [
                'redirect_to' => 'login',
                'reason' => 'User not logged in'
            ]);
            return redirect()->route('login')->with('error', 'Please log in to access the game.');
        }

        // Check if user has a player account
        $playerQueryStart = microtime(true);
        $player = Player::where('user_id', Auth::id())->first();
        $playerQueryTime = round((microtime(true) - $playerQueryStart) * 1000, 2);
        
        if (!$player) {
            ds('GameAuthMiddleware: No player account found', [
                'user_id' => Auth::id(),
                'query_time_ms' => $playerQueryTime,
                'redirect_to' => 'game.no-player'
            ]);
            return redirect()->route('game.no-player')->with('error', 'No player account found. Please create a player account.');
        }

        // Check if player is active
        if (!$player->is_active) {
            ds('GameAuthMiddleware: Player account suspended', [
                'player_id' => $player->id,
                'user_id' => Auth::id(),
                'redirect_to' => 'game.suspended'
            ]);
            return redirect()->route('game.suspended')->with('error', 'Your player account has been suspended.');
        }

        // Add player to request for easy access
        $request->merge(['player' => $player]);

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        
        ds('GameAuthMiddleware: Authentication check completed successfully', [
            'player_id' => $player->id,
            'user_id' => Auth::id(),
            'player_name' => $player->name,
            'is_active' => $player->is_active,
            'total_time_ms' => $totalTime,
            'player_query_time_ms' => $playerQueryTime
        ]);

        return $next($request);
    }
}
