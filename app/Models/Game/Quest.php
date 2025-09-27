<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Traits\Commentable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use MohamedSaid\Referenceable\Traits\HasReference;
use SmartCache\Facades\SmartCache;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;
use WendellAdriel\Lift\Lift;

class Quest extends Model
{
    use HasFactory, HasTaxonomy, HasNotables, HasReference, Commentable, Lift;

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
        $selectColumns = [
            'quests.*',
            QE::select(QE::count(c('id')))
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->as('total_players'),
            QE::select(QE::count(c('id')))
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->where(c('status'), '=', 'completed')
                ->as('completed_count'),
            QE::select(QE::avg(c('progress')))
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->as('avg_progress')
        ];

        if ($playerId) {
            $selectColumns[] = QE::select(c('status'))
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->where('player_id', $playerId)
                ->limit(1)
                ->as('player_status');
                
            $selectColumns[] = QE::select(c('progress'))
                ->from('player_quests')
                ->whereColumn('quest_id', c('quests.id'))
                ->where('player_id', $playerId)
                ->limit(1)
                ->as('player_progress');
        }

        return $query->select($selectColumns);
    }

    public function scopeByDifficultyFilter($query, $difficulty = null)
    {
        return $query->when($difficulty, function ($q) use ($difficulty) {
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
