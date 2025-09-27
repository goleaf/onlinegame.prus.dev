<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function __construct()
    {
        // Middleware is applied in routes/game.php
    }

    public function dashboard()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            $player = \App\Models\Game\Player::where('user_id', $user->id)->first();
            if (!$player) {
                return view('game.no-player', compact('user'));
            }

            return view('game.dashboard');
        } catch (\Exception $e) {
            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    public function village($village)
    {
        return view('game.village', compact('village'));
    }

    public function troops()
    {
        return view('game.troops');
    }

    public function movements()
    {
        return view('game.movements');
    }

    public function alliance()
    {
        return view('game.alliance');
    }

    public function quests()
    {
        return view('game.quests');
    }

    public function technology()
    {
        return view('game.technology');
    }

    public function reports()
    {
        return view('game.reports');
    }

    public function map()
    {
        return view('game.map');
    }

    public function statistics()
    {
        return view('game.statistics');
    }

    public function realTime()
    {
        return view('game.real-time');
    }

    public function battles()
    {
        return view('game.battles');
    }

    public function market()
    {
        return view('game.market');
    }
}
