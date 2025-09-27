<?php

namespace App\Traits;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JonPurvis\Squeaky\Rules\Clean;

trait GameValidationTrait
{
    /**
     * Validate game-specific data
     */
    protected function validateGameData(array $data, array $rules = [])
    {
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate village coordinates
     */
    protected function validateVillageCoordinates($x, $y, $worldId = null)
    {
        $rules = [
            'x' => 'required|integer|min:0|max:999',
            'y' => 'required|integer|min:0|max:999',
        ];

        if ($worldId) {
            $rules['world_id'] = 'required|exists:worlds,id';
        }

        return $this->validateGameData([
            'x' => $x,
            'y' => $y,
            'world_id' => $worldId,
        ], $rules);
    }

    /**
     * Validate troop quantities
     */
    protected function validateTroopQuantities(array $troops)
    {
        $rules = [];
        foreach ($troops as $troopId => $quantity) {
            $rules["troops.{$troopId}"] = 'required|integer|min:0|max:10000';
        }

        return $this->validateGameData(['troops' => $troops], $rules);
    }

    /**
     * Validate resource amounts
     */
    protected function validateResourceAmounts(array $resources)
    {
        $rules = [
            'wood' => 'integer|min:0|max:999999999',
            'clay' => 'integer|min:0|max:999999999',
            'iron' => 'integer|min:0|max:999999999',
            'crop' => 'integer|min:0|max:999999999',
        ];

        return $this->validateGameData($resources, $rules);
    }

    /**
     * Validate movement data
     */
    protected function validateMovementData(array $data)
    {
        $rules = [
            'from_village_id' => 'required|exists:villages,id',
            'to_village_id' => 'required|exists:villages,id|different:from_village_id',
            'type' => 'required|in:attack,reinforce,support,return',
            'troops' => 'required|array|min:1',
            'troops.*' => 'integer|min:1|max:10000',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate battle data
     */
    protected function validateBattleData(array $data)
    {
        $rules = [
            'attacker_id' => 'required|exists:players,id',
            'defender_id' => 'required|exists:players,id|different:attacker_id',
            'attacker_village_id' => 'required|exists:villages,id',
            'defender_village_id' => 'required|exists:villages,id',
            'attacker_troops' => 'required|array|min:1',
            'defender_troops' => 'required|array|min:1',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate task data
     */
    protected function validateTaskData(array $data)
    {
        $rules = [
            'title' => ['required', 'string', 'max:255', new Clean],
            'description' => ['nullable', 'string', 'max:1000', new Clean],
            'type' => 'required|in:building,combat,resource,exploration,alliance',
            'status' => 'required|in:available,active,completed,expired',
            'progress' => 'integer|min:0|max:100',
            'target' => 'required|integer|min:1|max:999999',
            'rewards' => 'nullable|json',
            'deadline' => 'nullable|date|after:now',
            'world_id' => 'required|exists:worlds,id',
            'player_id' => 'required|exists:players,id',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate player data
     */
    protected function validatePlayerData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:players,name', new Clean],
            'tribe' => 'required|in:roman,teuton,gaul',
            'alliance_id' => 'nullable|exists:alliances,id',
            'world_id' => 'required|exists:worlds,id',
            'user_id' => 'required|exists:users,id|unique:players,user_id',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate alliance data
     */
    protected function validateAllianceData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'unique:alliances,name', new Clean],
            'tag' => ['required', 'string', 'max:10', 'unique:alliances,tag', new Clean],
            'description' => ['nullable', 'string', 'max:1000', new Clean],
            'world_id' => 'required|exists:worlds,id',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate building data
     */
    protected function validateBuildingData(array $data)
    {
        $rules = [
            'village_id' => 'required|exists:villages,id',
            'building_type_id' => 'required|exists:building_types,id',
            'level' => 'required|integer|min:0|max:20',
            'is_under_construction' => 'boolean',
            'construction_started_at' => 'nullable|date',
            'construction_completed_at' => 'nullable|date|after:construction_started_at',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate quest data
     */
    protected function validateQuestData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new Clean],
            'description' => ['required', 'string', 'max:1000', new Clean],
            'category' => 'required|in:tutorial,main,side,daily,weekly,special',
            'difficulty' => 'required|in:easy,medium,hard,expert',
            'requirements' => 'nullable|json',
            'rewards' => 'nullable|json',
            'is_repeatable' => 'boolean',
            'cooldown_hours' => 'nullable|integer|min:0|max:168',  // 1 week max
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate achievement data
     */
    protected function validateAchievementData(array $data)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', new Clean],
            'description' => ['required', 'string', 'max:1000', new Clean],
            'category' => 'required|in:combat,building,resource,exploration,alliance,special',
            'points' => 'required|integer|min:1|max:1000',
            'requirements' => 'required|json',
            'is_hidden' => 'boolean',
            'is_repeatable' => 'boolean',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate file upload data
     */
    protected function validateFileUploadData(array $data)
    {
        $rules = [
            'file' => 'required|file|max:5120',  // 5MB max
            'type' => 'required|in:avatar,screenshot,document',
            'description' => 'nullable|string|max:255',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate search parameters
     */
    protected function validateSearchParameters(array $data)
    {
        $rules = [
            'query' => 'nullable|string|max:255',
            'filters' => 'nullable|array',
            'sort_by' => 'nullable|string|max:50',
            'sort_direction' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate pagination parameters
     */
    protected function validatePaginationParameters(array $data)
    {
        $rules = [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate date range
     */
    protected function validateDateRange(array $data)
    {
        $rules = [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ];

        return $this->validateGameData($data, $rules);
    }

    /**
     * Validate numeric range
     */
    protected function validateNumericRange(array $data, string $field, int $min = 0, int $max = 999999)
    {
        $rules = [
            "{$field}_min" => "nullable|integer|min:{$min}|max:{$max}",
            "{$field}_max" => "nullable|integer|min:{$min}|max:{$max}|gte:{$field}_min",
        ];

        return $this->validateGameData($data, $rules);
    }
}
