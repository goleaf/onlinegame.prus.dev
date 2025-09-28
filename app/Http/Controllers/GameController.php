<?php

namespace App\Http\Controllers;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use App\Models\User;
use App\Models\User;
use App\Models\User;
use App\Models\User;
use App\Services\GameSeoService;
use App\Utilities\LoggingUtil;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class GameController extends CrudController
{
    use ApiResponseTrait;

    protected GameSeoService $seoService;

    public function __construct(GameSeoService $seoService)
    {
        $this->seoService = $seoService;
        parent::__construct(new User());
    }

    public function dashboard()
    {
        try {
            $player = auth()->user();
            $villages = $player->villages ?? collect();

            // Set SEO metadata for dashboard
            $this->seoService->setDashboardSeo($player);

            LoggingUtil::info('Game dashboard accessed', [
                'user_id' => $player->id,
                'villages_count' => $villages->count(),
            ], 'game_controller');

            return view('game.dashboard', compact('player', 'villages'));
        } catch (\Exception $e) {
            LoggingUtil::error('Error accessing game dashboard', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'game_controller');

            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    public function village($id)
    {
        try {
            $village = Village::with(['buildings', 'resources'])->findOrFail($id);
            $player = auth()->user();

            // Check if player owns this village
            if ($village->player_id !== $player->id) {
                abort(403, 'You do not own this village');
            }

            // Set SEO metadata for village
            $this->seoService->setVillageSeo($village, $player);

            LoggingUtil::info('Village page accessed', [
                'user_id' => $player->id,
                'village_id' => $village->id,
                'village_name' => $village->name,
            ], 'game_controller');

            return view('game.village', compact('village', 'player'));
        } catch (\Exception $e) {
            LoggingUtil::error('Error accessing village page', [
                'error' => $e->getMessage(),
                'village_id' => $id,
                'user_id' => auth()->id(),
            ], 'game_controller');

            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    public function map()
    {
        try {
            $worlds = World::all();
            $currentWorld = $worlds->first();

            // Set SEO metadata for world map
            $this->seoService->setWorldMapSeo($currentWorld);

            LoggingUtil::info('World map accessed', [
                'user_id' => auth()->id(),
                'worlds_count' => $worlds->count(),
                'current_world_id' => $currentWorld->id ?? null,
            ], 'game_controller');

            return view('game.map', compact('worlds', 'currentWorld'));
        } catch (\Exception $e) {
            LoggingUtil::error('Error accessing world map', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'game_controller');

            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    public function index(Request $request)
    {
        try {
            // Set SEO metadata for main game page
            $this->seoService->setGameIndexSeo();
            $this->seoService->setGameStructuredData();

            LoggingUtil::info('Game index page accessed', [
                'user_id' => auth()->id(),
            ], 'game_controller');

            // Handle SPA routing for Travian game
            return view('game.index');
        } catch (\Exception $e) {
            LoggingUtil::error('Error accessing game index', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'game_controller');

            return view('game.error', ['error' => $e->getMessage()]);
        }
    }
}
