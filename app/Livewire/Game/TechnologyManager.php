<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Technology;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;

class TechnologyManager extends Component
{
    use WithPagination;

    #[Reactive]
    public $village;

    public $technologies = [];

    public $availableTechnologies = [];

    public $researchedTechnologies = [];

    public $researchQueue = [];

    public $selectedTechnology = null;

    public $notifications = [];

    public $isLoading = false;

    public $realTimeUpdates = true;

    public $autoRefresh = true;

    public $refreshInterval = 20;

    public $gameSpeed = 1;

    public $showDetails = false;

    public $selectedTechnologyId = null;

    public $filterByCategory = null;

    public $filterByLevel = null;

    public $filterByStatus = null;

    public $sortBy = 'name';

    public $sortOrder = 'asc';

    public $searchQuery = '';

    public $showOnlyAvailable = false;

    public $showOnlyResearched = false;

    public $showOnlyResearching = false;

    public $technologyStats = [];

    public $researchProgress = [];

    public $researchHistory = [];

    public $technologyTree = [];

    public $researchCosts = [];

    public $researchBenefits = [];

    public $technologyCategories = [];

    public $researchPriorities = ['low', 'medium', 'high', 'critical'];

    protected $listeners = [
        'researchStarted',
        'researchCompleted',
        'researchCancelled',
        'technologyUnlocked',
        'villageSelected',
        'gameTickProcessed',
    ];

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::withStats()
                ->with(['player:id,name,level,points', 'resources:id,village_id,wood,clay,iron,crop'])
                ->findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())
                ->with(['villages' => function ($query): void {
                    $query
                        ->withStats()
                        ->with(['resources:id,village_id,wood,clay,iron,crop']);
                }])
                ->first();
            $this->village = $player?->villages->first();
        }

        if ($this->village) {
            $this->loadTechnologyData();
            $this->initializeTechnologyFeatures();
        }
    }

    public function initializeTechnologyFeatures()
    {
        $this->calculateTechnologyStats();
        $this->calculateResearchProgress();
        $this->calculateResearchHistory();
        $this->calculateTechnologyTree();
        $this->calculateResearchCosts();
        $this->calculateResearchBenefits();

        $this->dispatch('initializeTechnologyRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadTechnologyData()
    {
        $this->isLoading = true;

        try {
            // Load available technologies with optimized query
            $this->availableTechnologies = Technology::where('world_id', $this->village->world_id)
                ->where('is_active', true)
                ->where('min_level', '<=', $this->village->player->level ?? 1)
                ->whereNotIn('id', $this->village->player->technologies()->pluck('technology_id'))
                ->selectRaw('
                    technologies.*,
                    (SELECT COUNT(*) FROM player_technologies pt WHERE pt.technology_id = technologies.id) as total_researchers,
                    (SELECT COUNT(*) FROM player_technologies pt2 WHERE pt2.technology_id = technologies.id AND pt2.status = "completed") as completed_count,
                    (SELECT AVG(level) FROM player_technologies pt3 WHERE pt3.technology_id = technologies.id AND pt3.status = "completed") as avg_level
                ')
                ->get()
                ->toArray();

            // Load researched technologies with optimized query
            $this->researchedTechnologies = $this
                ->village
                ->player
                ->technologies()
                ->where('status', 'completed')
                ->with('technology:id,name,description,category,benefits')
                ->selectRaw('
                    player_technologies.*,
                    (SELECT COUNT(*) FROM player_technologies pt2 WHERE pt2.player_id = player_technologies.player_id AND pt2.status = "completed") as total_researched,
                    (SELECT AVG(level) FROM player_technologies pt3 WHERE pt3.player_id = player_technologies.player_id AND pt3.status = "completed") as avg_research_level
                ')
                ->get()
                ->toArray();

            // Load research queue with optimized query
            $this->researchQueue = $this
                ->village
                ->player
                ->technologies()
                ->where('status', 'researching')
                ->with('technology:id,name,description,category,research_time')
                ->selectRaw('
                    player_technologies.*,
                    (SELECT COUNT(*) FROM player_technologies pt2 WHERE pt2.player_id = player_technologies.player_id AND pt2.status = "researching") as total_researching,
                    (SELECT AVG(progress) FROM player_technologies pt3 WHERE pt3.player_id = player_technologies.player_id AND pt3.status = "researching") as avg_research_progress
                ')
                ->get()
                ->toArray();

            $this->addNotification('Technology data loaded successfully', 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to load technology data: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectTechnology($technologyId)
    {
        $this->selectedTechnology = Technology::selectRaw('
                technologies.*,
                (SELECT COUNT(*) FROM player_technologies pt WHERE pt.technology_id = technologies.id) as total_researchers,
                (SELECT COUNT(*) FROM player_technologies pt2 WHERE pt2.technology_id = technologies.id AND pt2.status = "completed") as completed_count,
                (SELECT AVG(level) FROM player_technologies pt3 WHERE pt3.technology_id = technologies.id AND pt3.status = "completed") as avg_level
            ')
            ->find($technologyId);
        $this->selectedTechnologyId = $technologyId;
        $this->showDetails = true;
        $this->addNotification('Technology selected', 'info');
    }

    public function toggleDetails()
    {
        $this->showDetails = ! $this->showDetails;
    }

    public function startResearch($technologyId)
    {
        $technology = Technology::find($technologyId);

        if (! $technology) {
            $this->addNotification('Technology not found', 'error');

            return;
        }

        if ($this->village->player->level < $technology->min_level) {
            $this->addNotification('Technology requires higher level', 'error');

            return;
        }

        if ($this->village->player->technologies()->where('technology_id', $technologyId)->exists()) {
            $this->addNotification('Technology already researched or in progress', 'error');

            return;
        }

        // Check prerequisites
        if (! $this->checkPrerequisites($technology)) {
            $this->addNotification('Technology prerequisites not met', 'error');

            return;
        }

        // Check resources
        if (! $this->checkResources($technology)) {
            $this->addNotification('Insufficient resources for research', 'error');

            return;
        }

        try {
            // Deduct resources
            $this->deductResources($technology);

            // Start research
            $this->village->player->technologies()->create([
                'technology_id' => $technologyId,
                'status' => 'researching',
                'progress' => 0,
                'started_at' => now(),
                'estimated_completion' => now()->addSeconds($technology->research_time),
            ]);

            $this->loadTechnologyData();
            $this->addNotification("Research started: {$technology->name}", 'success');

            $this->dispatch('researchStarted', [
                'technology_id' => $technologyId,
                'player_id' => $this->village->player_id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to start research: '.$e->getMessage(), 'error');
        }
    }

    public function cancelResearch($technologyId)
    {
        $research = $this->village->player->technologies()->where('technology_id', $technologyId)->first();

        if (! $research) {
            $this->addNotification('Research not found', 'error');

            return;
        }

        if ($research->status !== 'researching') {
            $this->addNotification('Research is not in progress', 'error');

            return;
        }

        try {
            $technology = $research->technology;

            // Refund partial resources
            $this->refundResources($technology, $research->progress);

            // Cancel research
            $research->update(['status' => 'cancelled']);

            $this->loadTechnologyData();
            $this->addNotification("Research cancelled: {$technology->name}", 'info');

            $this->dispatch('researchCancelled', [
                'technology_id' => $technologyId,
                'player_id' => $this->village->player_id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to cancel research: '.$e->getMessage(), 'error');
        }
    }

    public function completeResearch($technologyId)
    {
        $research = $this->village->player->technologies()->where('technology_id', $technologyId)->first();

        if (! $research) {
            $this->addNotification('Research not found', 'error');

            return;
        }

        if ($research->status !== 'researching') {
            $this->addNotification('Research is not in progress', 'error');

            return;
        }

        if ($research->progress < 100) {
            $this->addNotification('Research not completed yet', 'error');

            return;
        }

        try {
            $technology = $research->technology;

            // Complete research
            $research->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Apply technology benefits
            $this->applyTechnologyBenefits($technology);

            $this->loadTechnologyData();
            $this->addNotification("Research completed: {$technology->name}!", 'success');

            $this->dispatch('researchCompleted', [
                'technology_id' => $technologyId,
                'player_id' => $this->village->player_id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to complete research: '.$e->getMessage(), 'error');
        }
    }

    private function checkPrerequisites($technology)
    {
        $prerequisites = json_decode($technology->prerequisites, true) ?? [];

        foreach ($prerequisites as $prerequisite) {
            if (! $this->checkPrerequisite($prerequisite)) {
                return false;
            }
        }

        return true;
    }

    private function checkPrerequisite($prerequisite)
    {
        switch ($prerequisite['type']) {
            case 'technology':
                return $this
                    ->village
                    ->player
                    ->technologies()
                    ->where('technology_id', $prerequisite['technology_id'])
                    ->where('status', 'completed')
                    ->exists();
            case 'building_level':
                $building = $this->village->buildings()->where('type', $prerequisite['building'])->first();

                return $building && $building->level >= $prerequisite['level'];
            case 'resource_amount':
                $resource = $this->village->resources()->where('type', $prerequisite['resource'])->first();

                return $resource && $resource->amount >= $prerequisite['amount'];
            default:
                return false;
        }
    }

    private function checkResources($technology)
    {
        $costs = json_decode($technology->costs, true) ?? [];

        foreach ($costs as $resource => $amount) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if (! $resourceModel || $resourceModel->amount < $amount) {
                return false;
            }
        }

        return true;
    }

    private function deductResources($technology)
    {
        $costs = json_decode($technology->costs, true) ?? [];

        foreach ($costs as $resource => $amount) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $amount);
            }
        }
    }

    private function refundResources($technology, $progress)
    {
        $costs = json_decode($technology->costs, true) ?? [];
        $refundRate = (100 - $progress) / 100;

        foreach ($costs as $resource => $amount) {
            $refundAmount = $amount * $refundRate;
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->increment('amount', $refundAmount);
            }
        }
    }

    private function applyTechnologyBenefits($technology)
    {
        $benefits = json_decode($technology->benefits, true) ?? [];

        foreach ($benefits as $benefit) {
            $this->applyBenefit($benefit);
        }
    }

    private function applyBenefit($benefit)
    {
        switch ($benefit['type']) {
            case 'resource_production':
                foreach ($benefit['resources'] as $resource => $bonus) {
                    $resourceModel = $this->village->resources()->where('type', $resource)->first();
                    if ($resourceModel) {
                        $resourceModel->increment('production_rate', $bonus);
                    }
                }

                break;
            case 'building_efficiency':
                // Apply building efficiency bonuses
                break;
            case 'military_bonus':
                // Apply military bonuses
                break;
            case 'defense_bonus':
                // Apply defense bonuses
                break;
        }
    }

    public function filterByCategory($category)
    {
        $this->filterByCategory = $category;
        $this->addNotification("Filtering by category: {$category}", 'info');
    }

    public function filterByLevel($level)
    {
        $this->filterByLevel = $level;
        $this->addNotification("Filtering by level: {$level}", 'info');
    }

    public function filterByStatus($status)
    {
        $this->filterByStatus = $status;
        $this->addNotification("Filtering by status: {$status}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByCategory = null;
        $this->filterByLevel = null;
        $this->filterByStatus = null;
        $this->searchQuery = '';
        $this->showOnlyAvailable = false;
        $this->showOnlyResearched = false;
        $this->showOnlyResearching = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortTechnologies($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'asc';
        }

        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchTechnologies()
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
            $this->showOnlyAvailable ? 'Showing only available technologies' : 'Showing all technologies',
            'info'
        );
    }

    public function toggleResearchedFilter()
    {
        $this->showOnlyResearched = ! $this->showOnlyResearched;
        $this->addNotification(
            $this->showOnlyResearched ? 'Showing only researched technologies' : 'Showing all technologies',
            'info'
        );
    }

    public function toggleResearchingFilter()
    {
        $this->showOnlyResearching = ! $this->showOnlyResearching;
        $this->addNotification(
            $this->showOnlyResearching ? 'Showing only researching technologies' : 'Showing all technologies',
            'info'
        );
    }

    public function calculateTechnologyStats()
    {
        $this->technologyStats = [
            'total_technologies' => Technology::where('world_id', $this->village->world_id)->count(),
            'available_technologies' => count($this->availableTechnologies),
            'researched_technologies' => count($this->researchedTechnologies),
            'researching_technologies' => count($this->researchQueue),
            'research_progress' => $this->calculateOverallResearchProgress(),
            'technology_level' => $this->calculateTechnologyLevel(),
        ];
    }

    public function calculateResearchProgress()
    {
        $this->researchProgress = [];

        foreach ($this->researchQueue as $research) {
            $this->researchProgress[] = [
                'technology_id' => $research['technology_id'],
                'progress' => $research['progress'],
                'time_remaining' => $this->calculateTimeRemaining($research),
                'completion_percentage' => $research['progress'],
            ];
        }
    }

    public function calculateResearchHistory()
    {
        $this->researchHistory = $this
            ->village
            ->player
            ->technologies()
            ->where('status', 'completed')
            ->with('technology')
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function calculateTechnologyTree()
    {
        $this->technologyTree = [];

        foreach ($this->technologyCategories as $category) {
            $this->technologyTree[$category] = Technology::where('world_id', $this->village->world_id)
                ->where('category', $category)
                ->orderBy('level')
                ->get()
                ->toArray();
        }
    }

    public function calculateResearchCosts()
    {
        $this->researchCosts = [];

        foreach ($this->availableTechnologies as $technology) {
            $this->researchCosts[$technology['id']] = json_decode($technology['costs'], true) ?? [];
        }
    }

    public function calculateResearchBenefits()
    {
        $this->researchBenefits = [];

        foreach ($this->availableTechnologies as $technology) {
            $this->researchBenefits[$technology['id']] = json_decode($technology['benefits'], true) ?? [];
        }
    }

    private function calculateOverallResearchProgress()
    {
        $totalTechnologies = Technology::where('world_id', $this->village->world_id)->count();
        $researchedCount = count($this->researchedTechnologies);

        return $totalTechnologies > 0 ? ($researchedCount / $totalTechnologies) * 100 : 0;
    }

    private function calculateTechnologyLevel()
    {
        $totalLevel = 0;

        foreach ($this->researchedTechnologies as $research) {
            $totalLevel += $research['technology']['level'] ?? 1;
        }

        return $totalLevel;
    }

    private function calculateTimeRemaining($research)
    {
        if (! isset($research['estimated_completion'])) {
            return 'Unknown';
        }

        $completionTime = $research['estimated_completion'];
        $now = now();

        if ($now->gt($completionTime)) {
            return 'Ready to complete';
        }

        return $now->diffForHumans($completionTime, true);
    }

    public function getTechnologyIcon($technology)
    {
        $icons = [
            'military' => '⚔️',
            'economy' => '💰',
            'infrastructure' => '🏗️',
            'defense' => '🛡️',
            'special' => '⭐',
        ];

        return $icons[$technology['category']] ?? '🔬';
    }

    public function getTechnologyColor($technology)
    {
        $colors = [
            'military' => 'red',
            'economy' => 'green',
            'infrastructure' => 'blue',
            'defense' => 'purple',
            'special' => 'gold',
        ];

        return $colors[$technology['category']] ?? 'gray';
    }

    public function getTechnologyStatus($technology)
    {
        if ($technology['status'] === 'researching') {
            return 'Researching';
        }

        if ($technology['status'] === 'completed') {
            return 'Completed';
        }

        if ($technology['status'] === 'cancelled') {
            return 'Cancelled';
        }

        return 'Available';
    }

    public function getResearchPriority($technology)
    {
        $priorities = [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];

        return $priorities[$technology['priority']] ?? 'Medium';
    }

    public function getResearchTime($technology)
    {
        $timeInSeconds = $technology['research_time'] ?? 0;

        if ($timeInSeconds < 60) {
            return "{$timeInSeconds}s";
        } elseif ($timeInSeconds < 3600) {
            return round($timeInSeconds / 60).'m';
        } else {
            return round($timeInSeconds / 3600).'h';
        }
    }

    public function getResearchCost($technology)
    {
        $costs = json_decode($technology['costs'], true) ?? [];
        $totalCost = 0;

        foreach ($costs as $amount) {
            $totalCost += $amount;
        }

        return $totalCost;
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
            $this->loadTechnologyData();
            $this->calculateTechnologyStats();
            $this->calculateResearchProgress();
            $this->calculateResearchHistory();
            $this->calculateTechnologyTree();
            $this->calculateResearchCosts();
            $this->calculateResearchBenefits();
        }
    }

    #[On('researchStarted')]
    public function handleResearchStarted($data)
    {
        $this->loadTechnologyData();
        $this->addNotification('Research started', 'success');
    }

    #[On('researchCompleted')]
    public function handleResearchCompleted($data)
    {
        $this->loadTechnologyData();
        $this->addNotification('Research completed', 'success');
    }

    #[On('researchCancelled')]
    public function handleResearchCancelled($data)
    {
        $this->loadTechnologyData();
        $this->addNotification('Research cancelled', 'info');
    }

    #[On('technologyUnlocked')]
    public function handleTechnologyUnlocked($data)
    {
        $this->loadTechnologyData();
        $this->addNotification('Technology unlocked', 'success');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->village = Village::findOrFail($villageId);
        $this->loadTechnologyData();
        $this->addNotification('Village selected - technology data updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.technology-manager', [
            'village' => $this->village,
            'technologies' => $this->technologies,
            'availableTechnologies' => $this->availableTechnologies,
            'researchedTechnologies' => $this->researchedTechnologies,
            'researchQueue' => $this->researchQueue,
            'selectedTechnology' => $this->selectedTechnology,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'showDetails' => $this->showDetails,
            'selectedTechnologyId' => $this->selectedTechnologyId,
            'filterByCategory' => $this->filterByCategory,
            'filterByLevel' => $this->filterByLevel,
            'filterByStatus' => $this->filterByStatus,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyAvailable' => $this->showOnlyAvailable,
            'showOnlyResearched' => $this->showOnlyResearched,
            'showOnlyResearching' => $this->showOnlyResearching,
            'technologyStats' => $this->technologyStats,
            'researchProgress' => $this->researchProgress,
            'researchHistory' => $this->researchHistory,
            'technologyTree' => $this->technologyTree,
            'researchCosts' => $this->researchCosts,
            'researchBenefits' => $this->researchBenefits,
            'technologyCategories' => $this->technologyCategories,
            'researchPriorities' => $this->researchPriorities,
        ]);
    }
}
