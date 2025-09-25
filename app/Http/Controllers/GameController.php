<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\Game\Building;
use App\Models\Game\Resource;

class GameController extends Controller
{
    public function dashboard()
    {
        $player = auth()->user();
        $villages = $player->villages ?? collect();
        
        return view('game.dashboard', compact('player', 'villages'));
    }
    
    public function village($id)
    {
        $village = Village::with(['buildings', 'resources'])->findOrFail($id);
        $player = auth()->user();
        
        // Check if player owns this village
        if ($village->player_id !== $player->id) {
            abort(403, 'You do not own this village');
        }
        
        return view('game.village', compact('village', 'player'));
    }
    
    public function map()
    {
        $worlds = World::all();
        $currentWorld = $worlds->first();
        
        return view('game.map', compact('worlds', 'currentWorld'));
    }
    
    public function index(Request $request)
    {
        // Handle SPA routing for Travian game
        return view('game.index');
    }
}
