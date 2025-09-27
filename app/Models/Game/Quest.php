<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Traits\Commentable;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use sbamtr\LaravelQueryEnrich\QE;
use SmartCache\Facades\SmartCache;
use WendellAdriel\Lift\Lift;

use function sbamtr\LaravelQueryEnrich\c;

class Quest extends Model implements Auditable
{
    use HasFactory, HasTaxonomy, HasNotables, HasReference, Commentable, Lift, AuditableTrait, GameValidationTrait;

    // Laravel Lift typed properties
    public int $id;
    public string $name;
    public string $key;
    public ?string $description;
    public ?string $instructions;
    public ?string $category;
    public ?string $difficulty;
    public ?array $requirements;
    public ?array $rewards;
    public ?int $experience_reward;
    public ?int $gold_reward;
    public ?array $resource_rewards;
    public bool $is_repeatable;
    public bool $is_active;
    public ?string $reference_number;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

    protected $fillable = [
        'name',
        'key',
        'description',
        'instructions',
        'category',
        'difficulty',
        'requirements',
        'rewards',
        'experience_reward',
        'gold_reward',
        'resource_rewards',
        'is_repeatable',
        'is_active',
        'reference_number',
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'resource_rewards' => 'array',
        'is_repeatable' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'QST-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'QST';

    public function players(): BelongsToMany
    {
        return $this
            ->belongsToMany(Player::class, 'player_quests')
            ->withPivot(['status', 'progress', 'progress_data', 'started_at', 'completed_at', 'expires_at'])
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByDifficulty($query, $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeRepeatable($query)
    {
        return $query->where('is_repeatable', true);
    }

    public function scopeTutorial($query)
    {
        return $query->where('category', 'tutorial');
    }

    public function scopeBuilding($query)
    {
        return $query->where('category', 'building');
    }

    public function scopeCombat($query)
    {
        return $query->where('category', 'combat');
    }

    // Enhanced query scopes using Query Enrich
    public function scopeWithPlayerStats($query, $playerId = null)
    {
        return $query->withCount([
            'players as completed_count' => function ($q) {
                $q->where('status', 'completed');
            },
            'players as active_count' => function ($q) {
                $q->where('status', 'active');
            },
        ])->when($playerId, function ($q) use ($playerId) {
            $q->with(['players' => function ($q) use ($playerId) {
                $q->where('player_id', $playerId);
            }]);
        });
    }

    public function scopeWithStats($query)
    {
        return $query->withCount([
            'players as completed_count' => function ($q) {
                $q->where('status', 'completed');
            },
            'players as active_count' => function ($q) {
                $q->where('status', 'active');
            },
            'players as available_count' => function ($q) {
                $q->where('status', 'available');
            },
        ]);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->withCount('players')
            ->orderByDesc('players_count')
            ->limit($limit);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function getCompletionRateAttribute(): float
    {
        $total = $this->players_count ?? 0;
        $completed = $this->completed_count ?? 0;
        
        return $total > 0 ? ($completed / $total) * 100 : 0;
    }

    public function getDifficultyColorAttribute(): string
    {
        return match($this->difficulty) {
            'easy' => 'green',
            'medium' => 'yellow',
            'hard' => 'orange',
            'expert' => 'red',
            default => 'gray'
        };
    }

    public function getCategoryIconAttribute(): string
    {
        return match($this->category) {
            'tutorial' => 'book',
            'building' => 'home',
            'combat' => 'sword',
            'resource' => 'treasure',
            'alliance' => 'users',
            'exploration' => 'map',
            default => 'star'
        };
    }

    public function isCompletedByPlayer(int $playerId): bool
    {
        return $this->players()
            ->where('player_id', $playerId)
            ->where('status', 'completed')
            ->exists();
    }

    public function isActiveForPlayer(int $playerId): bool
    {
        return $this->players()
            ->where('player_id', $playerId)
            ->where('status', 'active')
            ->exists();
    }

    public function canBeStartedByPlayer(int $playerId): bool
    {
        return !$this->isCompletedByPlayer($playerId) && 
               !$this->isActiveForPlayer($playerId) &&
               $this->is_active;
    }

    // Caching methods
    public static function getCachedQuests(string $cacheKey, callable $callback)
    {
        return SmartCache::remember(
            "quests_{$cacheKey}",
            now()->addMinutes(30),
            $callback
        );
    }

    public function getCachedPlayerProgress(int $playerId)
    {
        return SmartCache::remember(
            "quest_progress_{$this->id}_{$playerId}",
            now()->addMinutes(15),
            function () use ($playerId) {
                return $this->players()
                    ->where('player_id', $playerId)
                    ->first();
            }
        );
    }

    // Static methods for quest generation
    public static function generateTutorialQuest(string $key, string $name, string $description): self
    {
        return self::create([
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_repeatable' => false,
            'is_active' => true,
            'experience_reward' => 100,
            'gold_reward' => 50,
        ]);
    }

    public static function generateDailyQuest(string $key, string $name, string $description): self
    {
        return self::create([
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'category' => 'daily',
            'difficulty' => 'medium',
            'is_repeatable' => true,
            'is_active' => true,
            'experience_reward' => 200,
            'gold_reward' => 100,
        ]);
    }

    public static function generateWeeklyQuest(string $key, string $name, string $description): self
    {
        return self::create([
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'category' => 'weekly',
            'difficulty' => 'hard',
            'is_repeatable' => true,
            'is_active' => true,
            'experience_reward' => 500,
            'gold_reward' => 250,
        ]);
    }
}
            return $q->where('difficulty', $difficulty);
        });
    }

    public function scopeAvailableForPlayer($query, $playerId)
    {
        return $query
            ->where('is_active', true)
            ->whereNotIn('id', function ($q) use ($playerId) {
                $q
                    ->select('quest_id')
                    ->from('player_quests')
                    ->where('player_id', $playerId)
                    ->where('status', 'in_progress');
            });
    }

    public function scopeCompletedByPlayer($query, $playerId)
    {
        return $query->whereIn('id', function ($q) use ($playerId) {
            $q
                ->select('quest_id')
                ->from('player_quests')
                ->where('player_id', $playerId)
                ->where('status', 'completed');
        });
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

    /**
     * Get quests with SmartCache optimization
     */
    public static function getCachedQuests($playerId = null, $filters = [])
    {
        $cacheKey = "quests_{$playerId}_" . md5(serialize($filters));

        return SmartCache::remember($cacheKey, now()->addMinutes(20), function () use ($playerId, $filters) {
            $query = static::active()->withPlayerStats($playerId);

            if (isset($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            if (isset($filters['difficulty'])) {
                $query->byDifficultyFilter($filters['difficulty']);
            }

            if (isset($filters['repeatable'])) {
                $query->repeatable();
            }

            if (isset($filters['search'])) {
                $query->search($filters['search']);
            }

            return $query->get();
        });
    }
}
