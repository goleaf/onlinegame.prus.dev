<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use SmartCache\Facades\SmartCache;

class Technology extends Model
{
    use HasTaxonomy;

    protected $fillable = [
        'name',
        'description',
        'category',
        'max_level',
        'base_costs',
        'cost_multiplier',
        'research_time_base',
        'research_time_multiplier',
        'requirements',
        'effects',
        'is_active',
    ];

    protected $casts = [
        'base_costs' => 'array',
        'cost_multiplier' => 'array',
        'requirements' => 'array',
        'effects' => 'array',
        'is_active' => 'boolean',
    ];

    public function players(): BelongsToMany
    {
        return $this
            ->belongsToMany(Player::class, 'player_technologies')
            ->withPivot(['level', 'researched_at'])
            ->withTimestamps();
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            technologies.*,
            (SELECT COUNT(*) FROM player_technologies pt WHERE pt.technology_id = technologies.id) as total_researchers,
            (SELECT COUNT(*) FROM player_technologies pt2 WHERE pt2.technology_id = technologies.id AND pt2.status = "completed") as completed_count,
            (SELECT AVG(level) FROM player_technologies pt3 WHERE pt3.technology_id = technologies.id AND pt3.status = "completed") as avg_level,
            (SELECT COUNT(*) FROM player_technologies pt4 WHERE pt4.technology_id = technologies.id AND pt4.status = "researching") as researching_count
        ');
    }

    public function scopeByCategory($query, $category = null)
    {
        return $query->when($category, function ($q) use ($category) {
            return $q->where('category', $category);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByMaxLevel($query, $minLevel = null, $maxLevel = null)
    {
        return $query->when($minLevel, function ($q) use ($minLevel) {
            return $q->where('max_level', '>=', $minLevel);
        })->when($maxLevel, function ($q) use ($maxLevel) {
            return $q->where('max_level', '<=', $maxLevel);
        });
    }

    public function scopeByResearchTime($query, $minTime = null, $maxTime = null)
    {
        return $query->when($minTime, function ($q) use ($minTime) {
            return $q->where('research_time_base', '>=', $minTime);
        })->when($maxTime, function ($q) use ($maxTime) {
            return $q->where('research_time_base', '<=', $maxTime);
        });
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query
            ->orderByRaw('(SELECT COUNT(*) FROM player_technologies pt WHERE pt.technology_id = technologies.id) DESC')
            ->limit($limit);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('category', 'like', '%' . $searchTerm . '%');
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'players:id,name,level,points'
        ]);
    }

    /**
     * Get technologies with SmartCache optimization
     */
    public static function getCachedTechnologies($playerId = null, $filters = [])
    {
        $cacheKey = "technologies_{$playerId}_" . md5(serialize($filters));
        
        return SmartCache::remember($cacheKey, now()->addMinutes(25), function () use ($playerId, $filters) {
            $query = static::active()->withStats();
            
            if (isset($filters['category'])) {
                $query->byCategory($filters['category']);
            }
            
            if (isset($filters['max_level'])) {
                $query->byMaxLevel($filters['max_level']);
            }
            
            if (isset($filters['search'])) {
                $query->search($filters['search']);
            }
            
            if ($playerId) {
                $query->withPlayerInfo();
            }
            
            return $query->get();
        });
    }
}
