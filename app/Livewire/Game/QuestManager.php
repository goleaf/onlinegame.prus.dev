<?php

namespace App\Livewire\Game;

use App\Models\Game\Building;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Quest;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class QuestManager extends Component
{
    use WithPagination;

    public $player;
    public $village;
    public $availableQuests = [];
    public $activeQuests = [];
    public $completedQuests = [];
    public $selectedQuest = null;
    public $showQuestModal = false;

    protected $listeners = ['refreshQuests', 'questCompleted', 'questStarted'];

    public function mount()
    {
        $user = Auth::user();
        $this->player = $user->player;

        if ($this->player) {
            $this->village = $this->player->villages()->with(['resources', 'buildings'])->first();
            $this->loadQuestData();
        }
    }

    public function loadQuestData()
    {
        if ($this->player) {
            $this->loadAvailableQuests();
            $this->loadActiveQuests();
            $this->loadCompletedQuests();
        }
    }

    public function loadAvailableQuests()
    {
        $completedQuestIds = $this
            ->player
            ->quests()
            ->wherePivot('status', 'completed')
            ->pluck('quests.id')
            ->toArray();

        $this->availableQuests = Quest::where('is_active', true)
            ->whereNotIn('id', $completedQuestIds)
            ->orderBy('order')
            ->get();
    }

    public function loadActiveQuests()
    {
        $this->activeQuests = $this
            ->player
            ->quests()
            ->wherePivot('status', 'in_progress')
            ->withPivot(['progress', 'progress_data', 'started_at'])
            ->get();
    }

    public function loadCompletedQuests()
    {
        $this->completedQuests = $this
            ->player
            ->quests()
            ->wherePivot('status', 'completed')
            ->withPivot(['completed_at'])
            ->orderBy('player_quests.completed_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function startQuest($questId)
    {
        $quest = Quest::find($questId);
        if (!$quest)
            return;

        try {
            $this->player->quests()->attach($questId, [
                'status' => 'in_progress',
                'progress' => 0,
                'progress_data' => [],
                'started_at' => now()
            ]);

            $this->loadQuestData();
            $this->dispatch('questStarted', ['quest' => $quest->name]);
        } catch (\Exception $e) {
            $this->dispatch('questError', ['message' => $e->getMessage()]);
        }
    }

    public function selectQuest($questId)
    {
        $this->selectedQuest = Quest::find($questId);
        $this->showQuestModal = true;
    }

    public function checkQuestProgress()
    {
        foreach ($this->activeQuests as $quest) {
            $this->updateQuestProgress($quest);
        }
    }

    public function updateQuestProgress($playerQuest)
    {
        $quest = $playerQuest;
        $requirements = $quest->requirements ?? [];
        $progress = 0;
        $progressData = [];

        // Check different types of requirements
        foreach ($requirements as $type => $requirement) {
            switch ($type) {
                case 'resources':
                    $resourceProgress = $this->checkResourceRequirements($requirement);
                    $progress += $resourceProgress['progress'];
                    $progressData['resources'] = $resourceProgress['data'];
                    break;

                case 'buildings':
                    $buildingProgress = $this->checkBuildingRequirements($requirement);
                    $progress += $buildingProgress['progress'];
                    $progressData['buildings'] = $buildingProgress['data'];
                    break;

                case 'villages':
                    $villageProgress = $this->checkVillageRequirements($requirement);
                    $progress += $villageProgress['progress'];
                    $progressData['villages'] = $villageProgress['data'];
                    break;
            }
        }

        // Update quest progress
        $this->player->quests()->updateExistingPivot($quest->id, [
            'progress' => min(100, $progress),
            'progress_data' => $progressData
        ]);

        // Check if quest is completed
        if ($progress >= 100) {
            $this->completeQuest($quest->id);
        }
    }

    public function checkResourceRequirements($requirements)
    {
        $progress = 0;
        $data = [];

        foreach ($requirements as $resource => $amount) {
            $villageResource = $this->village->resources->where('type', $resource)->first();
            $currentAmount = $villageResource ? $villageResource->amount : 0;
            $resourceProgress = min(100, ($currentAmount / $amount) * 100);
            $progress += $resourceProgress;
            $data[$resource] = [
                'current' => $currentAmount,
                'required' => $amount,
                'progress' => $resourceProgress
            ];
        }

        return ['progress' => $progress / count($requirements), 'data' => $data];
    }

    public function checkBuildingRequirements($requirements)
    {
        $progress = 0;
        $data = [];

        foreach ($requirements as $buildingType => $level) {
            $building = $this
                ->village
                ->buildings()
                ->whereHas('buildingType', function ($query) use ($buildingType) {
                    $query->where('key', $buildingType);
                })
                ->first();

            $currentLevel = $building ? $building->level : 0;
            $buildingProgress = min(100, ($currentLevel / $level) * 100);
            $progress += $buildingProgress;
            $data[$buildingType] = [
                'current' => $currentLevel,
                'required' => $level,
                'progress' => $buildingProgress
            ];
        }

        return ['progress' => $progress / count($requirements), 'data' => $data];
    }

    public function checkVillageRequirements($requirements)
    {
        $progress = 0;
        $data = [];

        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'count':
                    $villageCount = $this->player->villages()->count();
                    $villageProgress = min(100, ($villageCount / $value) * 100);
                    $progress += $villageProgress;
                    $data['count'] = [
                        'current' => $villageCount,
                        'required' => $value,
                        'progress' => $villageProgress
                    ];
                    break;
            }
        }

        return ['progress' => $progress / count($requirements), 'data' => $data];
    }

    public function completeQuest($questId)
    {
        $quest = Quest::find($questId);
        if (!$quest)
            return;

        try {
            // Update quest status
            $this->player->quests()->updateExistingPivot($questId, [
                'status' => 'completed',
                'completed_at' => now()
            ]);

            // Give rewards
            $this->giveQuestRewards($quest);

            $this->loadQuestData();
            $this->dispatch('questCompleted', ['quest' => $quest->name, 'rewards' => $quest->rewards]);
        } catch (\Exception $e) {
            $this->dispatch('questError', ['message' => $e->getMessage()]);
        }
    }

    public function giveQuestRewards($quest)
    {
        $rewards = $quest->rewards ?? [];

        foreach ($rewards as $type => $amount) {
            switch ($type) {
                case 'resources':
                    foreach ($amount as $resource => $resourceAmount) {
                        $villageResource = $this->village->resources->where('type', $resource)->first();
                        if ($villageResource) {
                            $villageResource->increment('amount', $resourceAmount);
                        }
                    }
                    break;

                case 'points':
                    $this->player->increment('points', $amount);
                    break;
            }
        }
    }

    public function refreshQuests()
    {
        $this->loadQuestData();
    }

    public function render()
    {
        return view('livewire.game.quest-manager', [
            'player' => $this->player,
            'village' => $this->village,
            'availableQuests' => $this->availableQuests,
            'activeQuests' => $this->activeQuests,
            'completedQuests' => $this->completedQuests,
            'selectedQuest' => $this->selectedQuest
        ]);
    }
}
