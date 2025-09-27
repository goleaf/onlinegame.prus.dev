<?php

namespace App\Http\Controllers;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\GameSeoService;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;

class GameController extends CrudController
{
    use ApiResponseTrait;

    protected GameSeoService $seoService;

    public function __construct(GameSeoService $seoService)
    {
        $this->seoService = $seoService;
        parent::__construct();
    }

    public function dashboard()
    {
        $player = auth()->user();
        $villages = $player->villages ?? collect();

        // Set SEO metadata for dashboard
        $this->seoService->setDashboardSeo($player);

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

        // Set SEO metadata for village
        $this->seoService->setVillageSeo($village, $player);

        return view('game.village', compact('village', 'player'));
    }

    public function map()
    {
        $worlds = World::all();
        $currentWorld = $worlds->first();

        // Set SEO metadata for world map
        $this->seoService->setWorldMapSeo($currentWorld);

        return view('game.map', compact('worlds', 'currentWorld'));
    }

    public function index(Request $request)
    {
        // Set SEO metadata for main game page
        $this->seoService->setGameIndexSeo();
        $this->seoService->setGameStructuredData();

        // Handle SPA routing for Travian game
        return view('game.index');
    }
}
