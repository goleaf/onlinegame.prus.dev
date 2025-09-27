<?php

namespace App\Livewire\Game;

use App\Models\Game\Artifact;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\ArtifactEffectService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ArtifactManager extends Component
{
    use WithPagination;

    public $player;
    public $selectedVillage;
    public $artifacts = [];
    public $activeArtifacts = [];
    public $inactiveArtifacts = [];
    public $selectedArtifact = null;
    public $isLoading = false;
    public $notifications = [];
    public $showDetails = false;
    public $filterByType = null;
    public $filterByRarity = null;
    public $filterByStatus = null;
    public $sortBy = 'created_at';
    public $sortOrder = 'desc';
    public $searchQuery = '';
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 30;

    protected $listeners = [
        'artifactActivated',
        'artifactDeactivated',
        'artifactTransferred',
        'artifactRepaired',
        'gameTickProcessed',
    ];

    public function mount($villageId = null)
    {
        $this->loadPlayer();
        $this->loadVillage($villageId);
        $this->loadArtifacts();
    }

    public function loadPlayer()
    {
        $this->player = Player::where('user_id', Auth::id())->first();
        
        if (!$this->player) {
            $this->addNotification('Player not found', 'error');
            return;
        }
    }

    public function loadVillage($villageId = null)
    {
        if ($villageId) {
            $this->selectedVillage = Village::where('id', $villageId)
                ->where('player_id', $this->player->id)
                ->first();
        } else {
            $this->selectedVillage = $this->player->villages()->first();
        }

        if (!$this->selectedVillage) {
            $this->addNotification('No village found', 'error');
            return;
        }
    }

    public function loadArtifacts()
    {
        try {
            $this->isLoading = true;

            $query = Artifact::where('owner_id', $this->player->id)
                ->with(['artifactEffects', 'village']);

            // Apply filters
            if ($this->filterByType) {
                $query->where('type', $this->filterByType);
            }

            if ($this->filterByRarity) {
                $query->where('rarity', $this->filterByRarity);
            }

            if ($this->filterByStatus) {
                $query->where('status', $this->filterByStatus);
            }

            // Apply search
            if ($this->searchQuery) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchQuery . '%')
                      ->orWhere('description', 'like', '%' . $this->searchQuery . '%');
                });
            }

            // Apply sorting
            $query->orderBy($this->sortBy, $this->sortOrder);

            $this->artifacts = $query->get()->toArray();

            // Separate active and inactive artifacts
            $this->activeArtifacts = collect($this->artifacts)
                ->where('status', 'active')
                ->values()
                ->toArray();

            $this->inactiveArtifacts = collect($this->artifacts)
                ->where('status', '!=', 'active')
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            $this->addNotification('Failed to load artifacts: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectArtifact($artifactId)
    {
        $this->selectedArtifact = Artifact::with(['artifactEffects', 'village', 'owner'])
            ->find($artifactId);
        
        $this->showDetails = true;
        $this->addNotification('Artifact selected', 'info');
    }

    public function activateArtifact($artifactId)
    {
        try {
            $artifact = Artifact::find($artifactId);
            
            if (!$artifact) {
                $this->addNotification('Artifact not found', 'error');
                return;
            }

            if ($artifact->canActivate()) {
                $artifact->activate();
                $this->addNotification('Artifact activated successfully', 'success');
                $this->loadArtifacts();
                $this->dispatch('artifactActivated', $artifactId);
            } else {
                $this->addNotification('Cannot activate this artifact', 'error');
            }

        } catch (\Exception $e) {
            $this->addNotification('Failed to activate artifact: ' . $e->getMessage(), 'error');
        }
    }

    public function deactivateArtifact($artifactId)
    {
        try {
            $artifact = Artifact::find($artifactId);
            
            if (!$artifact) {
                $this->addNotification('Artifact not found', 'error');
                return;
            }

            if ($artifact->status === 'active') {
                $artifact->deactivate();
                $this->addNotification('Artifact deactivated successfully', 'success');
                $this->loadArtifacts();
                $this->dispatch('artifactDeactivated', $artifactId);
            } else {
                $this->addNotification('Artifact is not active', 'error');
            }

        } catch (\Exception $e) {
            $this->addNotification('Failed to deactivate artifact: ' . $e->getMessage(), 'error');
        }
    }

    public function transferArtifact($artifactId, $newVillageId)
    {
        try {
            $artifact = Artifact::find($artifactId);
            $newVillage = Village::where('id', $newVillageId)
                ->where('player_id', $this->player->id)
                ->first();
            
            if (!$artifact || !$newVillage) {
                $this->addNotification('Artifact or village not found', 'error');
                return;
            }

            if ($artifact->transfer($this->player, $newVillage)) {
                $this->addNotification('Artifact transferred successfully', 'success');
                $this->loadArtifacts();
                $this->dispatch('artifactTransferred', $artifactId, $newVillageId);
            } else {
                $this->addNotification('Cannot transfer this artifact', 'error');
            }

        } catch (\Exception $e) {
            $this->addNotification('Failed to transfer artifact: ' . $e->getMessage(), 'error');
        }
    }

    public function repairArtifact($artifactId, $repairAmount)
    {
        try {
            $artifact = Artifact::find($artifactId);
            
            if (!$artifact) {
                $this->addNotification('Artifact not found', 'error');
                return;
            }

            if ($artifact->isDamaged) {
                $artifact->repair($repairAmount);
                $this->addNotification('Artifact repaired successfully', 'success');
                $this->loadArtifacts();
                $this->dispatch('artifactRepaired', $artifactId);
            } else {
                $this->addNotification('Artifact is not damaged', 'info');
            }

        } catch (\Exception $e) {
            $this->addNotification('Failed to repair artifact: ' . $e->getMessage(), 'error');
        }
    }

    public function discoverRandomArtifact()
    {
        try {
            $artifact = Artifact::generateRandomArtifact();
            $artifact->discover($this->player, $this->selectedVillage);
            
            $this->addNotification('New artifact discovered!', 'success');
            $this->loadArtifacts();

        } catch (\Exception $e) {
            $this->addNotification('Failed to discover artifact: ' . $e->getMessage(), 'error');
        }
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function refreshArtifacts()
    {
        $this->loadArtifacts();
        $this->addNotification('Artifacts refreshed', 'info');
    }

    public function applyFilters()
    {
        $this->loadArtifacts();
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->filterByRarity = null;
        $this->filterByStatus = null;
        $this->searchQuery = '';
        $this->loadArtifacts();
    }

    public function addNotification(string $message, string $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function ($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    #[On('gameTickProcessed')]
    public function onGameTickProcessed()
    {
        if ($this->autoRefresh) {
            $this->loadArtifacts();
        }
    }

    public function getArtifactTypesProperty()
    {
        return [
            'weapon' => 'Weapon',
            'armor' => 'Armor',
            'tool' => 'Tool',
            'mystical' => 'Mystical',
            'relic' => 'Relic',
            'crystal' => 'Crystal',
        ];
    }

    public function getArtifactRaritiesProperty()
    {
        return [
            'common' => 'Common',
            'uncommon' => 'Uncommon',
            'rare' => 'Rare',
            'epic' => 'Epic',
            'legendary' => 'Legendary',
            'mythic' => 'Mythic',
        ];
    }

    public function getArtifactStatusesProperty()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'hidden' => 'Hidden',
            'destroyed' => 'Destroyed',
        ];
    }

    public function render()
    {
        return view('livewire.game.artifact-manager');
    }
}
