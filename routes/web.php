<?php

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |
 */

use App\Http\Controllers\Game\GameController;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Include auth routes
require __DIR__ . '/auth.php';

// Include game routes
require __DIR__ . '/game.php';

// Temporary debug route to test login
Route::get('/debug-login', function () {
    $email = 'goleaf@gmail.com';
    $password = 'goleaf';

    if (\Auth::attempt(['email' => $email, 'password' => $password])) {
        $user = \Auth::user();
        $capital = $user->capital;

        if ($capital) {
            return redirect("/game/city/{$capital->city_id}");
        } else {
            return 'No capital city found for user';
        }
    } else {
        return 'Login failed - invalid credentials';
    }
});

// Debug route to test GameController directly
Route::get('/debug-game', [GameController::class, 'index']);

// Debug route to test game dashboard
Route::get('/debug-game-dashboard', function () {
    try {
        $user = \Auth::user();
        if (! $user) {
            return 'No authenticated user';
        }

        $player = \App\Models\Game\Player::where('user_id', $user->id)->first();
        if (! $player) {
            return 'No player found for user: ' . $user->email;
        }

        return 'Player found: ' . $player->name . ' (ID: ' . $player->id . ')';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// Simple game dashboard for testing
Route::get('/game-simple', function () {
    return view('game.simple-dashboard');
});

// Auth::routes();
