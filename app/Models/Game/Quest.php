<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    use HasFactory, HasTaxonomy;

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
    ];

    protected $casts = [
        'requirements' => 'array',
        'rewards' => 'array',
        'resource_rewards' => 'array',
        'is_repeatable' => 'boolean',
        'is_active' => 'boolean',
    ];

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

    // Optimized query scopes using when() and selectRaw
    public function scopeWithPlayerStats($query, $playerId = null)
    {
        return $query->selectRaw('
            quests.*,
            (SELECT COUNT(*) FROM player_quests WHERE quest_id = quests.id) as total_players,
            (SELECT COUNT(*) FROM player_quests WHERE quest_id = quests.id AND status = "completed") as completed_count,
            (SELECT AVG(progress) FROM player_quests WHERE quest_id = quests.id) as avg_progress
        ')->when($playerId, function ($q) use ($playerId) {
            return $q->addSelect([
                'player_status' => \DB::table('player_quests')
                    ->select('status')
                    ->whereColumn('quest_id', 'quests.id')
                    ->where('player_id', $playerId)
                    ->limit(1),
                'player_progress' => \DB::table('player_quests')
                    ->select('progress')
                    ->whereColumn('quest_id', 'quests.id')
                    ->where('player_id', $playerId)
                    ->limit(1),
            ]);
        });
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
}
