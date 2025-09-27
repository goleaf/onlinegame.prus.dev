<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use SmartCache\Facades\SmartCache;

class UnitType extends Model
{
    use HasFactory, HasTaxonomy;

    protected $fillable = [
        'name',
        'key',
        'tribe',
        'description',
        'attack',
        'defense_infantry',
        'defense_cavalry',
        'speed',
        'carry_capacity',
        'costs',
        'requirements',
        'is_special',
        'is_active',
    ];

    protected $casts = [
        'costs' => 'array',
        'requirements' => 'array',
        'is_special' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function troops(): HasMany
    {
        return $this->hasMany(Troop::class);
    }

    public function trainingQueues(): HasMany
    {
        return $this->hasMany(TrainingQueue::class);
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            unit_types.*,
            (SELECT COUNT(*) FROM troops t WHERE t.unit_type_id = unit_types.id AND t.quantity > 0) as total_troops,
            (SELECT SUM(quantity) FROM troops t2 WHERE t2.unit_type_id = unit_types.id) as total_quantity,
            (SELECT AVG(quantity) FROM troops t3 WHERE t3.unit_type_id = unit_types.id AND t3.quantity > 0) as avg_quantity,
            (SELECT COUNT(*) FROM training_queues tq WHERE tq.unit_type_id = unit_types.id AND tq.is_completed = 0) as active_training_queues
        ');
    }

    public function scopeByTribe($query, $tribe = null)
    {
        return $query->when($tribe, function ($q) use ($tribe) {
            return $q->where('tribe', $tribe);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSpecial($query)
    {
        return $query->where('is_special', true);
    }

    public function scopeByAttackPower($query, $minAttack = null, $maxAttack = null)
    {
        return $query->when($minAttack, function ($q) use ($minAttack) {
            return $q->where('attack_power', '>=', $minAttack);
        })->when($maxAttack, function ($q) use ($maxAttack) {
            return $q->where('attack_power', '<=', $maxAttack);
        });
    }

    public function scopeByDefensePower($query, $minDefense = null, $maxDefense = null)
    {
        return $query->when($minDefense, function ($q) use ($minDefense) {
            return $q->where('defense_power', '>=', $minDefense);
        })->when($maxDefense, function ($q) use ($maxDefense) {
            return $q->where('defense_power', '<=', $maxDefense);
        });
    }

    public function scopeBySpeed($query, $minSpeed = null, $maxSpeed = null)
    {
        return $query->when($minSpeed, function ($q) use ($minSpeed) {
            return $q->where('speed', '>=', $minSpeed);
        })->when($maxSpeed, function ($q) use ($maxSpeed) {
            return $q->where('speed', '<=', $maxSpeed);
        });
    }

    /**
     * Get unit types with SmartCache optimization
     */
    public static function getCachedUnitTypes($tribe = null, $filters = [])
    {
        $cacheKey = "unit_types_{$tribe}_" . md5(serialize($filters));
        
        return SmartCache::remember($cacheKey, now()->addMinutes(15), function () use ($tribe, $filters) {
            $query = static::active()->withStats();
            
            if ($tribe) {
                $query->byTribe($tribe);
            }
            
            if (isset($filters['special'])) {
                $query->special();
            }
            
            if (isset($filters['min_attack'])) {
                $query->byAttackPower($filters['min_attack']);
            }
            
            if (isset($filters['min_defense'])) {
                $query->byDefensePower($filters['min_defense']);
            }
            
            return $query->get();
        });
    }

    public function scopeTopAttack($query, $limit = 10)
    {
        return $query->orderBy('attack_power', 'desc')->limit($limit);
    }

    public function scopeTopDefense($query, $limit = 10)
    {
        return $query->orderBy('defense_power', 'desc')->limit($limit);
    }

    public function scopeFastest($query, $limit = 10)
    {
        return $query->orderBy('speed', 'desc')->limit($limit);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('tribe', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithTroopInfo($query)
    {
        return $query->with([
            'troops:village_id,unit_type_id,quantity',
            'trainingQueues:village_id,unit_type_id,quantity,is_completed'
        ]);
    }
}
