<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Village;
use App\Traits\GameValidationTrait;
use App\ValueObjects\Coordinates;
use App\ValueObjects\VillageResources;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\ValidationHelperTrait;
use LaraUtilX\Utilities\FilteringUtil;
use sbamtr\LaravelQueryEnrich\QE;

use function sbamtr\LaravelQueryEnrich\c;

class VillageController extends CrudController
{
    use ApiResponseTrait, GameValidationTrait;

    protected Model $model;

    protected array $validationRules = [
        'name' => 'required|string|max:255',
        'player_id' => 'required|exists:players,id',
        'world_id' => 'required|exists:worlds,id',
        'x_coordinate' => 'required|integer|min:0|max:999',
        'y_coordinate' => 'required|integer|min:0|max:999',
        'population' => 'integer|min:0',
        'culture_points' => 'integer|min:0',
    ];

    protected array $searchableFields = ['name'];
    protected array $relationships = ['player', 'world', 'buildings', 'troops', 'resources'];
    protected int $perPage = 15;

    public function __construct()
    {
        $this->model = new Village();
        parent::__construct($this->model);
    }

    /**
     * Get villages with advanced filtering and statistics
     */
    public function withStats(Request $request)
    {
        $query = Village::with($this->relationships)
            ->selectRaw('
                villages.*,
                (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                (SELECT COUNT(*) FROM troops WHERE village_id = villages.id) as troop_count,
                (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources
            ');

        // Apply filters using FilteringUtil
        $filters = [];

        if ($request->has('player_id')) {
            $filters[] = ['target' => 'player_id', 'type' => '$eq', 'value' => $request->get('player_id')];
        }

        if ($request->has('world_id')) {
            $filters[] = ['target' => 'world_id', 'type' => '$eq', 'value' => $request->get('world_id')];
        }

        if ($request->has('min_population')) {
            $filters[] = ['target' => 'population', 'type' => '$gte', 'value' => $request->get('min_population')];
        }

        if ($request->has('max_population')) {
            $filters[] = ['target' => 'population', 'type' => '$lte', 'value' => $request->get('max_population')];
        }

        if (!empty($filters)) {
            $query = $query->filter($filters);
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
            $query->orderBy('population', 'desc');
        }

        $villages = $query->paginate($request->get('per_page', $this->perPage));

        return $this->paginatedResponse($villages, 'Villages fetched successfully.');
    }

    /**
     * Get villages by coordinates range
     */
    public function byCoordinates(Request $request)
    {
        $validated = $request->validate([
            'x_min' => 'required|integer|min:0|max:999',
            'x_max' => 'required|integer|min:0|max:999',
            'y_min' => 'required|integer|min:0|max:999',
            'y_max' => 'required|integer|min:0|max:999',
            'world_id' => 'required|exists:worlds,id',
        ]);

        $villages = Village::with($this->relationships)
            ->where('world_id', $validated['world_id'])
            ->whereBetween('x_coordinate', [$validated['x_min'], $validated['x_max']])
            ->whereBetween('y_coordinate', [$validated['y_min'], $validated['y_max']])
            ->orderBy('x_coordinate')
            ->orderBy('y_coordinate')
            ->get();

        return $this->successResponse($villages, 'Villages by coordinates fetched successfully.');
    }

    /**
     * Get village details with all related data
     */
    public function details($villageId)
    {
        $village = Village::with([
            'player',
            'world',
            'buildings.buildingType',
            'troops.unitType',
            'resources',
            'buildingQueues.buildingType',
            'trainingQueues.unitType',
        ])->findOrFail($villageId);

        $villageCoordinates = $village->coordinates;
        $villageResources = $village->villageResources;

        $details = [
            'village' => $village,
            'coordinates' => $villageCoordinates,
            'village_resources' => $villageResources,
            'statistics' => [
                'total_buildings' => $village->buildings->count(),
                'total_troops' => $village->troops->sum('quantity'),
                'total_resources' => $villageResources ? $villageResources->getTotalResources() : 0,
                'active_queues' => $village->buildingQueues->where('is_active', true)->count()
                    + $village->trainingQueues->where('is_active', true)->count(),
            ],
        ];

        return $this->successResponse($details, 'Village details fetched successfully.');
    }

    /**
     * Update village resources
     */
    public function updateResources(Request $request, $villageId)
    {
        $village = Village::findOrFail($villageId);

        $validated = $request->validate([
            'wood' => 'integer|min:0',
            'clay' => 'integer|min:0',
            'iron' => 'integer|min:0',
            'crop' => 'integer|min:0',
        ]);

        $village->resources()->updateOrCreate(
            ['village_id' => $villageId],
            $validated
        );

        return $this->successResponse($village->fresh(['resources']), 'Village resources updated successfully.');
    }

    /**
     * Get nearby villages
     */
    public function nearby(Request $request, $villageId)
    {
        $village = Village::findOrFail($villageId);
        $radius = $request->get('radius', 10);

        $nearbyVillages = Village::with(['player'])
            ->where('world_id', $village->world_id)
            ->where('id', '!=', $villageId)
            ->whereRaw('
                SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2)) <= ?
            ', [$village->x_coordinate, $village->y_coordinate, $radius])
            ->orderByRaw('
                SQRT(POW(x_coordinate - ?, 2) + POW(y_coordinate - ?, 2))
            ', [$village->x_coordinate, $village->y_coordinate])
            ->limit(20)
            ->get();

        return $this->successResponse($nearbyVillages, 'Nearby villages fetched successfully.');
    }
}
