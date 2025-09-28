<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\Player;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FilteringUtil;

class PlayerController extends CrudController
{
    use ApiResponseTrait;
    use GameValidationTrait;

    protected Model $model;

    protected array $validationRules = [
        'name' => 'required|string|max:255',
        'tribe' => 'required|string|in:roman,teuton,gaul',
        'alliance_id' => 'nullable|exists:alliances,id',
        'world_id' => 'required|exists:worlds,id',
    ];

    protected array $searchableFields = ['name', 'tribe'];

    protected array $relationships = ['user', 'world', 'alliance', 'villages'];

    protected int $perPage = 20;

    public function __construct()
    {
        $this->model = new Player();
        parent::__construct($this->model);
    }

    /**
     * Get players with advanced filtering and statistics
     */
    public function withStats(Request $request)
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

        // Apply filters using FilteringUtil
        $filters = [];
        if ($request->has('search')) {
            $filters[] = ['target' => 'name', 'type' => '$like', 'value' => '%'.$request->get('search').'%'];
        }
        if ($request->has('is_online')) {
            $filters[] = ['target' => 'is_online', 'type' => '$eq', 'value' => $request->boolean('is_online')];
        }
        if ($request->has('is_active')) {
            $filters[] = ['target' => 'is_active', 'type' => '$eq', 'value' => $request->boolean('is_active')];
        }
        if ($request->has('min_points')) {
            $filters[] = ['target' => 'points', 'type' => '$gte', 'value' => $request->get('min_points')];
        }
        if ($request->has('max_points')) {
            $filters[] = ['target' => 'points', 'type' => '$lte', 'value' => $request->get('max_points')];
        }

        if (! empty($filters)) {
            $query = $query->filter($filters);
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
    public function top(Request $request)
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
    public function stats($playerId)
    {
        $player = Player::withStats()
            ->with($this->relationships)
            ->findOrFail($playerId);

        $playerStats = $player->stats;

        $stats = [
            'player' => $player,
            'player_stats' => $playerStats,
            'village_count' => $player->villages->count(),
            'total_population' => $player->villages->sum('population'),
            'total_points' => $playerStats->points,
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

        $validated = $this->validateRequestData($request, [
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
        if (! $player->alliance_id) {
            return null;
        }

        return Player::where('alliance_id', $player->alliance_id)
            ->where('points', '>', $player->points)
            ->count() + 1;
    }
}
