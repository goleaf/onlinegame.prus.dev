<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Player;
use App\Traits\GameValidationTrait;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class PlayerController extends CrudController
{
    use ApiResponseTrait, GameValidationTrait;

    protected $model;

    protected $validationRules = [
        'name' => 'required|string|max:255',
        'tribe' => 'required|string|in:roman,teuton,gaul',
        'alliance_id' => 'nullable|exists:alliances,id',
        'world_id' => 'required|exists:worlds,id',
    ];

    protected $searchableFields = ['name', 'tribe'];
    protected $relationships = ['user', 'world', 'alliance', 'villages'];
    protected $perPage = 20;

    public function __construct()
    {
        $this->model = new Player();
        parent::__construct($this->model);
    }

    /**
     * Get players with advanced filtering and statistics
     */
    public function getPlayersWithStats(Request $request)
    {
        $query = Player::withStats()
            ->with($this->relationships);

        // Apply filters
        if ($request->has('world_id')) {
            $query->byWorld($request->get('world_id'));
        }

        if ($request->has('tribe')) {
            $query->byTribe($request->get('tribe'));
        }

        if ($request->has('alliance_id')) {
            $query->inAlliance($request->get('alliance_id'));
        }

        if ($request->has('is_active')) {
            $query->active();
        }

        if ($request->has('is_online')) {
            $query->online();
        }

        // Apply search
        if ($request->has('search')) {
            $query->search($request->get('search'));
        }

        // Apply sorting
        if ($request->has('sort_by')) {
            $direction = $request->get('sort_direction', 'desc');
            $query->orderBy($request->get('sort_by'), $direction);
        } else {
            $query->orderBy('points', 'desc');
        }

        $players = $query->paginate($request->get('per_page', $this->perPage));

        return $this->paginatedResponse($players, 'Players fetched successfully.');
    }

    /**
     * Get top players by various criteria
     */
    public function getTopPlayers(Request $request)
    {
        $limit = $request->get('limit', 10);
        $worldId = $request->get('world_id');

        $query = Player::withStats()
            ->with($this->relationships)
            ->topPlayers($limit);

        if ($worldId) {
            $query->byWorld($worldId);
        }

        $players = $query->get();

        return $this->successResponse($players, 'Top players fetched successfully.');
    }

    /**
     * Get player statistics
     */
    public function getPlayerStats($playerId)
    {
        $player = Player::withStats()
            ->with($this->relationships)
            ->findOrFail($playerId);

        $stats = [
            'player' => $player,
            'village_count' => $player->villages->count(),
            'total_population' => $player->villages->sum('population'),
            'total_points' => $player->points,
            'world_rank' => $this->getWorldRank($player),
            'alliance_rank' => $this->getAllianceRank($player),
        ];

        return $this->successResponse($stats, 'Player statistics fetched successfully.');
    }

    /**
     * Update player status
     */
    public function updateStatus(Request $request, $playerId)
    {
        $player = Player::findOrFail($playerId);

        $validated = $request->validate([
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'last_active_at' => 'nullable|date',
        ]);

        $player->update($validated);

        return $this->successResponse($player, 'Player status updated successfully.');
    }

    /**
     * Get world rank for a player
     */
    private function getWorldRank(Player $player)
    {
        return Player::where('world_id', $player->world_id)
            ->where('points', '>', $player->points)
            ->count() + 1;
    }

    /**
     * Get alliance rank for a player
     */
    private function getAllianceRank(Player $player)
    {
        if (!$player->alliance_id) {
            return null;
        }

        return Player::where('alliance_id', $player->alliance_id)
            ->where('points', '>', $player->points)
            ->count() + 1;
    }
}
