<?php

namespace App\Livewire\Game;

use Aliziodev\LaravelTaxonomy\Facades\Taxonomy;
use App\Models\Game\Achievement;
use App\Models\Game\Player;
use App\Models\Game\Quest;
use App\Models\Game\World;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;

class QuestManager extends Component
{
    use ApiResponseTrait;
    use WithPagination;

    #[Reactive]
    public $world;

    public $quests = [];

    public $availableQuests = [];

    public $completedQuests = [];

    public $activeQuests = [];

    public $achievements = [];

    public $playerAchievements = [];

    public $selectedQuest = null;

    public $selectedAchievement = null;

    public $notifications = [];

    public $isLoading = false;

    public $realTimeUpdates = true;

    public $autoRefresh = true;

    public $refreshInterval = 15;

    public $gameSpeed = 1;

    public $showDetails = false;

    public $selectedQuestId = null;

    public $selectedAchievementId = null;

    public $filterByType = null;

    public $filterByDifficulty = null;

    public $filterByStatus = null;

    public $sortBy = 'created_at';

    public $sortOrder = 'desc';

    public $searchQuery = '';

    public $showOnlyAvailable = false;

    public $showOnlyActive = false;

    public $showOnlyCompleted = false;

    public $showOnlyAchievements = false;

    public $questStats = [];

    public $achievementStats = [];

    public $playerProgress = [];

    public $questHistory = [];

    public $achievementHistory = [];

    public $rewards = [];

    public $questCategories = [];

    public $questDifficulties = [];

    public $achievementCategories = ['building', 'combat', 'alliance', 'resource', 'special', 'milestone'];

    protected $listeners = [
        'questAccepted',
        'questCompleted',
        'questAbandoned',
        'achievementUnlocked',
        'rewardClaimed',
        'villageSelected',
        'gameTickProcessed',
    ];

    public function mount($worldId = null)
    {
        if ($worldId) {
            $this->world = World::findOrFail($worldId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->world = $player?->world;
        }

        if ($this->world) {
            $this->loadTaxonomies();
            $this->loadQuestData();
            $this->initializeQuestFeatures();
        }
    }

    public function loadTaxonomies()
    {
        $this->questCategories = Taxonomy::findByType('quest_category')
            ->map(function ($taxonomy) {
                return [
                    'id' => $taxonomy->id,
                    'name' => $taxonomy->name,
                    'slug' => $taxonomy->slug,
                    'description' => $taxonomy->description,
                    'meta' => $taxonomy->meta,
                ];
            })
            ->toArray();

        $this->questDifficulties = Taxonomy::findByType('quest_difficulty')
            ->map(function ($taxonomy) {
                return [
                    'id' => $taxonomy->id,
                    'name' => $taxonomy->name,
                    'slug' => $taxonomy->slug,
                    'description' => $taxonomy->description,
                    'meta' => $taxonomy->meta,
                ];
            })
            ->toArray();
    }

    public function initializeQuestFeatures()
    {
        $this->calculateQuestStats();
        $this->calculateAchievementStats();
        $this->calculatePlayerProgress();
        $this->calculateQuestHistory();
        $this->calculateAchievementHistory();
        $this->calculateRewards();

        $this->dispatch('initializeQuestRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadQuestData()
    {
        $this->isLoading = true;

        try {
            $player = Player::where('user_id', Auth::id())
                ->with(['quests.quest:id,name,description,category,difficulty', 'achievements.achievement:id,name,description,category,points'])
                ->first();

            // Use optimized scopes for quest loading
            $this->availableQuests = Quest::where('world_id', $this->world->id)
                ->active()
                ->where('min_level', '<=', $player->level ?? 1)
                ->whereNotIn('id', $player->quests()->pluck('quest_id'))
                ->withPlayerStats($player->id)
                ->get()
                ->toArray();

            // Load active quests using optimized scopes
            $this->activeQuests = $player
                ->quests()
                ->where('status', 'in_progress')
                ->with('quest:id,name,description,category,difficulty')
                ->selectRaw('player_quests.*, (SELECT COUNT(*) FROM player_quests pq2 WHERE pq2.player_id = player_quests.player_id AND pq2.status = "in_progress") as total_active')
                ->get()
                ->toArray();

            // Load completed quests using optimized scopes
            $this->completedQuests = $player
                ->quests()
                ->where('status', 'completed')
                ->with('quest:id,name,description,category,difficulty')
                ->selectRaw('player_quests.*, (SELECT COUNT(*) FROM player_quests pq2 WHERE pq2.player_id = player_quests.player_id AND pq2.status = "completed") as total_completed')
                ->get()
                ->toArray();

            // Load achievements using optimized scopes
            $this->achievements = Achievement::byWorld($this->world->id)
                ->active()
                ->withStats()
                ->withPlayerInfo()
                ->get()
                ->toArray();

            // Load player achievements using optimized scopes
            $this->playerAchievements = $player
                ->achievements()
                ->withStats()
                ->withPlayerInfo()
                ->get()
                ->toArray();

            $this->addNotification('Quest data loaded successfully', 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to load quest data: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectQuest($questId)
    {
        $this->selectedQuest = Quest::find($questId);
        $this->selectedQuestId = $questId;
        $this->showDetails = true;
        $this->addNotification('Quest selected', 'info');
    }

    public function selectAchievement($achievementId)
    {
        $this->selectedAchievement = Achievement::find($achievementId);
        $this->selectedAchievementId = $achievementId;
        $this->showDetails = true;
        $this->addNotification('Achievement selected', 'info');
    }

    public function toggleDetails()
    {
        $this->showDetails = ! $this->showDetails;
    }

    public function acceptQuest($questId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $quest = Quest::find($questId);

        if (! $quest) {
            $this->addNotification('Quest not found', 'error');

            return;
        }

        if ($player->level < $quest->min_level) {
            $this->addNotification('Quest requires higher level', 'error');

            return;
        }

        if ($player->quests()->where('quest_id', $questId)->exists()) {
            $this->addNotification('Quest already accepted', 'error');

            return;
        }

        try {
            $player->quests()->create([
                'quest_id' => $questId,
                'status' => 'active',
                'progress' => 0,
                'started_at' => now(),
            ]);

            $this->loadQuestData();
            $this->addNotification("Quest \"{$quest->title}\" accepted", 'success');

            $this->dispatch('questAccepted', [
                'quest_id' => $questId,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to accept quest: '.$e->getMessage(), 'error');
        }
    }

    public function completeQuest($questId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $questProgress = $player->quests()->where('quest_id', $questId)->first();

        if (! $questProgress) {
            $this->addNotification('Quest not found', 'error');

            return;
        }

        if ($questProgress->status !== 'active') {
            $this->addNotification('Quest is not active', 'error');

            return;
        }

        $quest = $questProgress->quest;
        if ($questProgress->progress < $quest->target_value) {
            $this->addNotification('Quest not completed yet', 'error');

            return;
        }

        try {
            $questProgress->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Give rewards
            $this->giveQuestRewards($quest, $player);

            $this->loadQuestData();
            $this->addNotification("Quest \"{$quest->title}\" completed!", 'success');

            $this->dispatch('questCompleted', [
                'quest_id' => $questId,
                'player_id' => $player->id,
                'rewards' => $quest->rewards,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to complete quest: '.$e->getMessage(), 'error');
        }
    }

    public function abandonQuest($questId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $questProgress = $player->quests()->where('quest_id', $questId)->first();

        if (! $questProgress) {
            $this->addNotification('Quest not found', 'error');

            return;
        }

        if ($questProgress->status !== 'active') {
            $this->addNotification('Quest is not active', 'error');

            return;
        }

        try {
            $quest = $questProgress->quest;
            $questProgress->update(['status' => 'abandoned']);

            $this->loadQuestData();
            $this->addNotification("Quest \"{$quest->title}\" abandoned", 'info');

            $this->dispatch('questAbandoned', [
                'quest_id' => $questId,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to abandon quest: '.$e->getMessage(), 'error');
        }
    }

    public function claimAchievement($achievementId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $achievement = Achievement::find($achievementId);

        if (! $achievement) {
            $this->addNotification('Achievement not found', 'error');

            return;
        }

        if ($player->achievements()->where('achievement_id', $achievementId)->exists()) {
            $this->addNotification('Achievement already claimed', 'error');

            return;
        }

        if (! $this->checkAchievementRequirements($achievement, $player)) {
            $this->addNotification('Achievement requirements not met', 'error');

            return;
        }

        try {
            $player->achievements()->create([
                'achievement_id' => $achievementId,
                'unlocked_at' => now(),
            ]);

            // Give rewards
            $this->giveAchievementRewards($achievement, $player);

            $this->loadQuestData();
            $this->addNotification("Achievement \"{$achievement->title}\" unlocked!", 'success');

            $this->dispatch('achievementUnlocked', [
                'achievement_id' => $achievementId,
                'player_id' => $player->id,
                'rewards' => $achievement->rewards,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to claim achievement: '.$e->getMessage(), 'error');
        }
    }

    public function claimReward($rewardId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $reward = $player->rewards()->find($rewardId);

        if (! $reward) {
            $this->addNotification('Reward not found', 'error');

            return;
        }

        if ($reward->claimed_at) {
            $this->addNotification('Reward already claimed', 'error');

            return;
        }

        try {
            $reward->update(['claimed_at' => now()]);
            $this->loadQuestData();
            $this->addNotification('Reward claimed successfully', 'success');

            $this->dispatch('rewardClaimed', [
                'reward_id' => $rewardId,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to claim reward: '.$e->getMessage(), 'error');
        }
    }

    private function giveQuestRewards($quest, $player)
    {
        $rewards = json_decode($quest->rewards, true) ?? [];

        foreach ($rewards as $reward) {
            $this->giveReward($player, $reward);
        }
    }

    private function giveAchievementRewards($achievement, $player)
    {
        $rewards = json_decode($achievement->rewards, true) ?? [];

        foreach ($rewards as $reward) {
            $this->giveReward($player, $reward);
        }
    }

    private function giveReward($player, $reward)
    {
        switch ($reward['type']) {
            case 'experience':
                $player->increment('experience', $reward['amount']);

                break;
            case 'resources':
                foreach ($reward['resources'] as $resource => $amount) {
                    $resourceModel = $player->villages()->first()->resources()->where('type', $resource)->first();
                    if ($resourceModel) {
                        $resourceModel->increment('amount', $amount);
                    }
                }

                break;
            case 'items':
                // Handle item rewards
                break;
            case 'currency':
                $player->increment('currency', $reward['amount']);

                break;
        }
    }

    private function checkAchievementRequirements($achievement, $player)
    {
        $requirements = json_decode($achievement->requirements, true) ?? [];

        foreach ($requirements as $requirement) {
            if (! $this->checkRequirement($requirement, $player)) {
                return false;
            }
        }

        return true;
    }

    private function checkRequirement($requirement, $player)
    {
        switch ($requirement['type']) {
            case 'level':
                return $player->level >= $requirement['value'];
            case 'building_level':
                $building = $player->villages()->first()->buildings()->where('type', $requirement['building'])->first();

                return $building && $building->level >= $requirement['value'];
            case 'resource_amount':
                $resource = $player->villages()->first()->resources()->where('type', $requirement['resource'])->first();

                return $resource && $resource->amount >= $requirement['value'];
            case 'quests_completed':
                return $player->quests()->where('status', 'completed')->count() >= $requirement['value'];
            case 'achievements_unlocked':
                return $player->achievements()->count() >= $requirement['value'];
            default:
                return false;
        }
    }

    public function filterByType($type)
    {
        $this->filterByType = $type;
        $this->addNotification("Filtering by type: {$type}", 'info');
    }

    public function filterByDifficulty($difficulty)
    {
        $this->filterByDifficulty = $difficulty;
        $this->addNotification("Filtering by difficulty: {$difficulty}", 'info');
    }

    public function filterByStatus($status)
    {
        $this->filterByStatus = $status;
        $this->addNotification("Filtering by status: {$status}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->filterByDifficulty = null;
        $this->filterByStatus = null;
        $this->searchQuery = '';
        $this->showOnlyAvailable = false;
        $this->showOnlyActive = false;
        $this->showOnlyCompleted = false;
        $this->showOnlyAchievements = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortQuests($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'desc';
        }

        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchQuests()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }

        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleAvailableFilter()
    {
        $this->showOnlyAvailable = ! $this->showOnlyAvailable;
        $this->addNotification(
            $this->showOnlyAvailable ? 'Showing only available quests' : 'Showing all quests',
            'info'
        );
    }

    public function toggleActiveFilter()
    {
        $this->showOnlyActive = ! $this->showOnlyActive;
        $this->addNotification(
            $this->showOnlyActive ? 'Showing only active quests' : 'Showing all quests',
            'info'
        );
    }

    public function toggleCompletedFilter()
    {
        $this->showOnlyCompleted = ! $this->showOnlyCompleted;
        $this->addNotification(
            $this->showOnlyCompleted ? 'Showing only completed quests' : 'Showing all quests',
            'info'
        );
    }

    public function toggleAchievementsFilter()
    {
        $this->showOnlyAchievements = ! $this->showOnlyAchievements;
        $this->addNotification(
            $this->showOnlyAchievements ? 'Showing only achievements' : 'Showing all content',
            'info'
        );
    }

    public function calculateQuestStats()
    {
        $player = Player::where('user_id', Auth::id())->first();

        $this->questStats = [
            'total_quests' => Quest::where('world_id', $this->world->id)->count(),
            'available_quests' => count($this->availableQuests),
            'active_quests' => count($this->activeQuests),
            'completed_quests' => count($this->completedQuests),
            'completion_rate' => $player ? ($player->quests()->where('status', 'completed')->count() / Quest::where('world_id', $this->world->id)->count()) * 100 : 0,
            'total_rewards' => $player ? $player->rewards()->sum('value') : 0,
        ];
    }

    public function calculateAchievementStats()
    {
        $player = Player::where('user_id', Auth::id())->first();

        $this->achievementStats = [
            'total_achievements' => Achievement::where('world_id', $this->world->id)->count(),
            'unlocked_achievements' => count($this->playerAchievements),
            'unlock_rate' => $player ? (count($this->playerAchievements) / Achievement::where('world_id', $this->world->id)->count()) * 100 : 0,
            'recent_achievements' => $player ? $player->achievements()->orderBy('unlocked_at', 'desc')->limit(5)->get()->toArray() : [],
        ];
    }

    public function calculatePlayerProgress()
    {
        $player = Player::where('user_id', Auth::id())->first();

        $this->playerProgress = [
            'level' => $player->level ?? 1,
            'experience' => $player->experience ?? 0,
            'next_level_exp' => $this->getNextLevelExp($player->level ?? 1),
            'quests_completed' => $player ? $player->quests()->where('status', 'completed')->count() : 0,
            'achievements_unlocked' => count($this->playerAchievements),
            'total_rewards' => $player ? $player->rewards()->sum('value') : 0,
        ];
    }

    public function calculateQuestHistory()
    {
        $player = Player::where('user_id', Auth::id())->first();

        $this->questHistory = $player ? $player
            ->quests()
            ->with('quest')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray() : [];
    }

    public function calculateAchievementHistory()
    {
        $player = Player::where('user_id', Auth::id())->first();

        $this->achievementHistory = $player ? $player
            ->achievements()
            ->with('achievement')
            ->orderBy('unlocked_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray() : [];
    }

    public function calculateRewards()
    {
        $player = Player::where('user_id', Auth::id())->first();

        $this->rewards = $player ? $player
            ->rewards()
            ->whereNull('claimed_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray() : [];
    }

    private function getNextLevelExp($level)
    {
        return $level * 1000;  // Simple formula: 1000 exp per level
    }

    public function getQuestIcon($quest)
    {
        $icons = [
            'tutorial' => '📚',
            'building' => '🏗️',
            'resource' => '💰',
            'combat' => '⚔️',
            'alliance' => '🤝',
            'special' => '⭐',
        ];

        return $icons[$quest['category']] ?? '📋';
    }

    public function getQuestColor($quest)
    {
        $colors = [
            'easy' => 'green',
            'medium' => 'yellow',
            'hard' => 'red',
            'expert' => 'purple',
        ];

        return $colors[$quest['difficulty']] ?? 'blue';
    }

    public function getQuestStatus($quest)
    {
        if ($quest['status'] === 'active') {
            return 'Active';
        }

        if ($quest['status'] === 'completed') {
            return 'Completed';
        }

        if ($quest['status'] === 'abandoned') {
            return 'Abandoned';
        }

        return 'Available';
    }

    public function getAchievementIcon($achievement)
    {
        $icons = [
            'building' => '🏗️',
            'combat' => '⚔️',
            'alliance' => '🤝',
            'resource' => '💰',
            'special' => '⭐',
            'milestone' => '🏆',
        ];

        return $icons[$achievement['category']] ?? '🏅';
    }

    public function getAchievementColor($achievement)
    {
        $colors = [
            'bronze' => 'brown',
            'silver' => 'gray',
            'gold' => 'yellow',
            'platinum' => 'purple',
        ];

        return $colors[$achievement['rarity']] ?? 'blue';
    }

    public function getProgressPercentage($quest)
    {
        if (! isset($quest['progress']) || ! isset($quest['target_value'])) {
            return 0;
        }

        return min(100, ($quest['progress'] / $quest['target_value']) * 100);
    }

    public function getTimeRemaining($quest)
    {
        if (! isset($quest['expires_at'])) {
            return 'No time limit';
        }

        $expiresAt = $quest['expires_at'];
        $now = now();

        if ($now->gt($expiresAt)) {
            return 'Expired';
        }

        return $now->diffForHumans($expiresAt, true);
    }

    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = ! $this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = ! $this->autoRefresh;
        $this->addNotification(
            $this->autoRefresh ? 'Auto-refresh enabled' : 'Auto-refresh disabled',
            'info'
        );
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = max(5, min(60, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function setGameSpeed($speed)
    {
        $this->gameSpeed = max(0.5, min(3.0, $speed));
        $this->addNotification("Game speed set to {$this->gameSpeed}x", 'info');
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];

        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function ($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadQuestData();
            $this->calculateQuestStats();
            $this->calculateAchievementStats();
            $this->calculatePlayerProgress();
            $this->calculateQuestHistory();
            $this->calculateAchievementHistory();
            $this->calculateRewards();
        }
    }

    #[On('questAccepted')]
    public function handleQuestAccepted($data)
    {
        $this->loadQuestData();
        $this->addNotification('Quest accepted', 'success');
    }

    #[On('questCompleted')]
    public function handleQuestCompleted($data)
    {
        $this->loadQuestData();
        $this->addNotification('Quest completed', 'success');
    }

    #[On('questAbandoned')]
    public function handleQuestAbandoned($data)
    {
        $this->loadQuestData();
        $this->addNotification('Quest abandoned', 'info');
    }

    #[On('achievementUnlocked')]
    public function handleAchievementUnlocked($data)
    {
        $this->loadQuestData();
        $this->addNotification('Achievement unlocked', 'success');
    }

    #[On('rewardClaimed')]
    public function handleRewardClaimed($data)
    {
        $this->loadQuestData();
        $this->addNotification('Reward claimed', 'success');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->loadQuestData();
        $this->addNotification('Village selected - quest data updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.quest-manager', [
            'world' => $this->world,
            'quests' => $this->quests,
            'availableQuests' => $this->availableQuests,
            'completedQuests' => $this->completedQuests,
            'activeQuests' => $this->activeQuests,
            'achievements' => $this->achievements,
            'playerAchievements' => $this->playerAchievements,
            'selectedQuest' => $this->selectedQuest,
            'selectedAchievement' => $this->selectedAchievement,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'showDetails' => $this->showDetails,
            'selectedQuestId' => $this->selectedQuestId,
            'selectedAchievementId' => $this->selectedAchievementId,
            'filterByType' => $this->filterByType,
            'filterByDifficulty' => $this->filterByDifficulty,
            'filterByStatus' => $this->filterByStatus,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyAvailable' => $this->showOnlyAvailable,
            'showOnlyActive' => $this->showOnlyActive,
            'showOnlyCompleted' => $this->showOnlyCompleted,
            'showOnlyAchievements' => $this->showOnlyAchievements,
            'questStats' => $this->questStats,
            'achievementStats' => $this->achievementStats,
            'playerProgress' => $this->playerProgress,
            'questHistory' => $this->questHistory,
            'achievementHistory' => $this->achievementHistory,
            'rewards' => $this->rewards,
            'questCategories' => $this->questCategories,
            'achievementCategories' => $this->achievementCategories,
        ]);
    }
}
