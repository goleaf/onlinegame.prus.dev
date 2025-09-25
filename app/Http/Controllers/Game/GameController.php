<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Livewire\Game\AllianceManager;
use App\Livewire\Game\MapViewer;
use App\Livewire\Game\MovementManager;
use App\Livewire\Game\QuestManager;
use App\Livewire\Game\RealTimeUpdater;
use App\Livewire\Game\ReportManager;
use App\Livewire\Game\StatisticsViewer;
use App\Livewire\Game\TechnologyManager;
use App\Livewire\Game\TravianDashboard;
use App\Livewire\Game\TroopManager;
use App\Livewire\Game\VillageManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
