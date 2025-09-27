<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Village extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'world_id',
        'name',
        'x_coordinate',
        'y_coordinate',
        'population',
        'is_capital',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_capital' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function resourceProductionLogs(): HasMany
    {
        return $this->hasMany(ResourceProductionLog::class);
    }

    public function trainingQueues(): HasMany
    {
        return $this->hasMany(TrainingQueue::class);
    }

    public function buildingQueues(): HasMany
    {
        return $this->hasMany(BuildingQueue::class);
    }

    public function troops(): HasMany
    {
        return $this->hasMany(Troop::class);
    }

    public function movementsFrom(): HasMany
    {
        return $this->hasMany(Movement::class, 'from_village_id');
    }

    public function movementsTo(): HasMany
    {
        return $this->hasMany(Movement::class, 'to_village_id');
    }

    public function battles(): HasMany
    {
        return $this->hasMany(Battle::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function getCoordinatesAttribute()
    {
        return "({$this->x_coordinate}|{$this->y_coordinate})";
    }
}
