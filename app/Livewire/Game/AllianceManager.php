<?php

namespace App\Livewire\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceDiplomacy;
use App\Models\Game\AllianceLog;
use App\Models\Game\AllianceMessage;
use App\Models\Game\AllianceWar;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;
use SmartCache\Facades\SmartCache;

class AllianceManager extends Component
{
    use WithPagination;

    #[Reactive]
    public $world;

    public $alliances = [];
    public $myAlliance = null;
    public $selectedAlliance = null;
    public $allianceMembers = [];
    public $allianceInvites = [];
    public $allianceApplications = [];
    public $notifications = [];
    public $isLoading = false;
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 10;
    public $gameSpeed = 1;
    public $showDetails = false;
    public $selectedMember = null;
    public $filterByRank = null;
    public $sortBy = 'name';
    public $sortOrder = 'asc';
    public $searchQuery = '';
    public $showOnlyActive = false;
    public $showOnlyInvites = false;
    public $showOnlyApplications = false;
    public $allianceStats = [];
    public $memberStats = [];
    public $inviteStats = [];
    public $applicationStats = [];
    
    // Diplomacy and communication features
    public $allianceDiplomacy = [];
    public $allianceWars = [];
    public $allianceMessages = [];
    public $allianceLogs = [];
    public $selectedDiplomacy = null;
    public $selectedWar = null;
    public $selectedMessage = null;
    public $showDiplomacy = false;
    public $showWars = false;
    public $showMessages = false;
    public $showLogs = false;
    public $diplomacyForm = [
        'target_alliance_id' => null,
        'status' => 'ally',
        'message' => '',
        'expires_at' => null,
    ];
    public $messageForm = [
        'type' => 'general',
        'title' => '',
        'content' => '',
        'is_pinned' => false,
        'is_important' => false,
    ];
    public $warForm = [
        'target_alliance_id' => null,
        'declaration_message' => '',
    ];

    protected $listeners = [
        'allianceUpdated',
        'memberJoined',
        'memberLeft',
        'inviteSent',
        'inviteAccepted',
        'inviteDeclined',
        'applicationSubmitted',
        'applicationAccepted',
        'applicationDeclined',
        'villageSelected',
        'gameTickProcessed',
        'diplomacyProposed',
        'diplomacyAccepted',
        'diplomacyDeclined',
        'warDeclared',
        'warEnded',
        'messagePosted',
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
            $this->loadAllianceData();
            $this->initializeAllianceFeatures();
        }
    }

    public function initializeAllianceFeatures()
    {
        $this->calculateAllianceStats();
        $this->calculateMemberStats();
        $this->calculateInviteStats();
        $this->calculateApplicationStats();

        $this->dispatch('initializeAllianceRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadAllianceData()
    {
        $this->isLoading = true;

        try {
            // Use SmartCache for alliance data with automatic optimization
            $alliancesCacheKey = "world_{$this->world->id}_alliances_data";
            $this->alliances = SmartCache::remember($alliancesCacheKey, now()->addMinutes(5), function () {
                $query = Alliance::where('world_id', $this->world->id)
                    ->with(['members:id,name,alliance_id,points,created_at', 'invites:id,alliance_id,player_id,status', 'applications:id,alliance_id,player_id,status'])
                    ->selectRaw('
                        alliances.*,
                        (SELECT COUNT(*) FROM players p WHERE p.alliance_id = alliances.id) as member_count,
                        (SELECT SUM(points) FROM players p2 WHERE p2.alliance_id = alliances.id) as total_points,
                        (SELECT AVG(points) FROM players p3 WHERE p3.alliance_id = alliances.id) as avg_points,
                        (SELECT MAX(points) FROM players p4 WHERE p4.alliance_id = alliances.id) as max_points
                    ');

                // Apply filters using the new eloquent filtering system
                $filters = [];
                
                if ($this->searchQuery) {
                    $filters[] = [
                        'target' => 'name',
                        'type' => '$contains',
                        'value' => $this->searchQuery
                    ];
                }
                
                if ($this->showOnlyActive) {
                    $filters[] = [
                        'target' => 'is_active',
                        'type' => '$eq',
                        'value' => true
                    ];
                }
                
                if ($this->filterByRank) {
                    $filters[] = [
                        'type' => '$has',
                        'target' => 'members',
                        'value' => [
                            [
                                'target' => 'alliance_rank',
                                'type' => '$eq',
                                'value' => $this->filterByRank
                            ]
                        ]
                    ];
                }

                if (!empty($filters)) {
                    $query = $query->filter($filters);
                }

                // Apply sorting
                $query = $query->orderBy($this->sortBy, $this->sortOrder);

                return $query->get()->toArray();
            });

            // Use SmartCache for player alliance data with automatic optimization
            $playerAllianceCacheKey = 'player_' . Auth::id() . '_alliance_data';
            $player = SmartCache::remember($playerAllianceCacheKey, now()->addMinutes(3), function () {
                return Player::where('user_id', Auth::id())
                    ->with(['alliance' => function ($query) {
                        $query->with(['members:id,name,alliance_id,points,created_at', 'invites:id,alliance_id,player_id,status', 'applications:id,alliance_id,player_id,status']);
                    }])
                    ->first();
            });

            if ($player && $player->alliance) {
                $this->myAlliance = $player->alliance;
                $this->allianceMembers = $player->alliance->members;
                $this->allianceInvites = $player->alliance->invites;
                $this->allianceApplications = $player->alliance->applications;
            }

            $this->addNotification('Alliance data loaded successfully', 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to load alliance data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectAlliance($allianceId)
    {
        // Use SmartCache for selected alliance data with automatic optimization
        $selectedAllianceCacheKey = "alliance_{$allianceId}_detailed";
        $this->selectedAlliance = SmartCache::remember($selectedAllianceCacheKey, now()->addMinutes(5), function () use ($allianceId) {
            return Alliance::with(['members:id,name,alliance_id,points,created_at', 'invites:id,alliance_id,player_id,status', 'applications:id,alliance_id,player_id,status'])
                ->selectRaw('
                    alliances.*,
                    (SELECT COUNT(*) FROM players p WHERE p.alliance_id = alliances.id) as member_count,
                    (SELECT SUM(points) FROM players p2 WHERE p2.alliance_id = alliances.id) as total_points,
                    (SELECT AVG(points) FROM players p3 WHERE p3.alliance_id = alliances.id) as avg_points,
                    (SELECT MAX(points) FROM players p4 WHERE p4.alliance_id = alliances.id) as max_points
                ')
                ->find($allianceId);
        });
        $this->showDetails = true;
        $this->addNotification('Alliance selected', 'info');
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function selectMember($memberId)
    {
        $this->selectedMember = $memberId;
        $this->addNotification('Member selected', 'info');
    }

    public function filterByRank($rank)
    {
        $this->filterByRank = $rank;
        $this->addNotification("Filtering by rank: {$rank}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByRank = null;
        $this->searchQuery = '';
        $this->showOnlyActive = false;
        $this->showOnlyInvites = false;
        $this->showOnlyApplications = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortAlliances($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'asc';
        }

        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchAlliances()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }

        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleActiveFilter()
    {
        $this->showOnlyActive = !$this->showOnlyActive;
        $this->addNotification(
            $this->showOnlyActive ? 'Showing only active alliances' : 'Showing all alliances',
            'info'
        );
    }

    public function toggleInvitesFilter()
    {
        $this->showOnlyInvites = !$this->showOnlyInvites;
        $this->addNotification(
            $this->showOnlyInvites ? 'Showing only invites' : 'Showing all data',
            'info'
        );
    }

    public function toggleApplicationsFilter()
    {
        $this->showOnlyApplications = !$this->showOnlyApplications;
        $this->addNotification(
            $this->showOnlyApplications ? 'Showing only applications' : 'Showing all data',
            'info'
        );
    }

    public function createAlliance($name, $tag, $description = '')
    {
        if (empty($name) || empty($tag)) {
            $this->addNotification('Alliance name and tag are required', 'error');

            return;
        }

        $player = Player::where('user_id', Auth::id())->first();
        if ($player->alliance) {
            $this->addNotification('You are already in an alliance', 'error');

            return;
        }

        try {
            $alliance = Alliance::create([
                'name' => $name,
                'tag' => $tag,
                'description' => $description,
                'world_id' => $this->world->id,
                'leader_id' => $player->id,
                'created_at' => now(),
            ]);

            $player->update(['alliance_id' => $alliance->id, 'alliance_rank' => 'leader']);

            $this->loadAllianceData();
            $this->addNotification("Alliance '{$name}' created successfully", 'success');

            $this->dispatch('allianceCreated', [
                'alliance_id' => $alliance->id,
                'alliance_name' => $alliance->name,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to create alliance: ' . $e->getMessage(), 'error');
        }
    }

    public function joinAlliance($allianceId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if ($player->alliance) {
            $this->addNotification('You are already in an alliance', 'error');

            return;
        }

        $alliance = Alliance::find($allianceId);
        if (!$alliance) {
            $this->addNotification('Alliance not found', 'error');

            return;
        }

        if ($alliance->members->count() >= $alliance->max_members) {
            $this->addNotification('Alliance is full', 'error');

            return;
        }

        try {
            $player->update(['alliance_id' => $allianceId, 'alliance_rank' => 'member']);
            $this->loadAllianceData();
            $this->addNotification("Joined alliance '{$alliance->name}'", 'success');

            $this->dispatch('memberJoined', [
                'alliance_id' => $allianceId,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to join alliance: ' . $e->getMessage(), 'error');
        }
    }

    public function leaveAlliance()
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance) {
            $this->addNotification('You are not in an alliance', 'error');

            return;
        }

        $alliance = $player->alliance;
        if ($player->alliance_rank === 'leader' && $alliance->members->count() > 1) {
            $this->addNotification('You cannot leave as leader with other members', 'error');

            return;
        }

        try {
            $player->update(['alliance_id' => null, 'alliance_rank' => null]);

            if ($alliance->members->count() <= 1) {
                $alliance->delete();
                $this->addNotification('Alliance disbanded', 'info');
            } else {
                $this->addNotification("Left alliance '{$alliance->name}'", 'success');
            }

            $this->loadAllianceData();

            $this->dispatch('memberLeft', [
                'alliance_id' => $alliance->id,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to leave alliance: ' . $e->getMessage(), 'error');
        }
    }

    public function invitePlayer($playerId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance || !in_array($player->alliance_rank, ['leader', 'co_leader'])) {
            $this->addNotification('You do not have permission to invite players', 'error');

            return;
        }

        $targetPlayer = Player::find($playerId);
        if (!$targetPlayer) {
            $this->addNotification('Player not found', 'error');

            return;
        }

        if ($targetPlayer->alliance) {
            $this->addNotification('Player is already in an alliance', 'error');

            return;
        }

        try {
            $alliance = $player->alliance;
            $alliance->invites()->create([
                'player_id' => $playerId,
                'invited_by' => $player->id,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            $this->addNotification("Invitation sent to {$targetPlayer->name}", 'success');

            $this->dispatch('inviteSent', [
                'alliance_id' => $alliance->id,
                'player_id' => $playerId,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to send invitation: ' . $e->getMessage(), 'error');
        }
    }

    public function acceptInvite($inviteId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $invite = $player->allianceInvites()->find($inviteId);

        if (!$invite) {
            $this->addNotification('Invitation not found', 'error');

            return;
        }

        if ($invite->status !== 'pending') {
            $this->addNotification('Invitation is no longer valid', 'error');

            return;
        }

        try {
            $alliance = $invite->alliance;
            if ($alliance->members->count() >= $alliance->max_members) {
                $this->addNotification('Alliance is full', 'error');

                return;
            }

            $player->update(['alliance_id' => $alliance->id, 'alliance_rank' => 'member']);
            $invite->update(['status' => 'accepted']);

            $this->loadAllianceData();
            $this->addNotification("Joined alliance '{$alliance->name}'", 'success');

            $this->dispatch('inviteAccepted', [
                'alliance_id' => $alliance->id,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to accept invitation: ' . $e->getMessage(), 'error');
        }
    }

    public function declineInvite($inviteId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $invite = $player->allianceInvites()->find($inviteId);

        if (!$invite) {
            $this->addNotification('Invitation not found', 'error');

            return;
        }

        try {
            $invite->update(['status' => 'declined']);
            $this->addNotification('Invitation declined', 'info');

            $this->dispatch('inviteDeclined', [
                'alliance_id' => $invite->alliance_id,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to decline invitation: ' . $e->getMessage(), 'error');
        }
    }

    public function applyToAlliance($allianceId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if ($player->alliance) {
            $this->addNotification('You are already in an alliance', 'error');

            return;
        }

        $alliance = Alliance::find($allianceId);
        if (!$alliance) {
            $this->addNotification('Alliance not found', 'error');

            return;
        }

        try {
            $alliance->applications()->create([
                'player_id' => $player->id,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            $this->addNotification("Application sent to '{$alliance->name}'", 'success');

            $this->dispatch('applicationSubmitted', [
                'alliance_id' => $allianceId,
                'player_id' => $player->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to submit application: ' . $e->getMessage(), 'error');
        }
    }

    public function acceptApplication($applicationId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance || !in_array($player->alliance_rank, ['leader', 'co_leader'])) {
            $this->addNotification('You do not have permission to accept applications', 'error');

            return;
        }

        $application = $player->alliance->applications()->find($applicationId);
        if (!$application) {
            $this->addNotification('Application not found', 'error');

            return;
        }

        if ($application->status !== 'pending') {
            $this->addNotification('Application is no longer valid', 'error');

            return;
        }

        try {
            $alliance = $player->alliance;
            if ($alliance->members->count() >= $alliance->max_members) {
                $this->addNotification('Alliance is full', 'error');

                return;
            }

            $applicant = $application->player;
            $applicant->update(['alliance_id' => $alliance->id, 'alliance_rank' => 'member']);
            $application->update(['status' => 'accepted']);

            $this->loadAllianceData();
            $this->addNotification("Application accepted for {$applicant->name}", 'success');

            $this->dispatch('applicationAccepted', [
                'alliance_id' => $alliance->id,
                'player_id' => $applicant->id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to accept application: ' . $e->getMessage(), 'error');
        }
    }

    public function declineApplication($applicationId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance || !in_array($player->alliance_rank, ['leader', 'co_leader'])) {
            $this->addNotification('You do not have permission to decline applications', 'error');

            return;
        }

        $application = $player->alliance->applications()->find($applicationId);
        if (!$application) {
            $this->addNotification('Application not found', 'error');

            return;
        }

        try {
            $application->update(['status' => 'declined']);
            $this->addNotification('Application declined', 'info');

            $this->dispatch('applicationDeclined', [
                'alliance_id' => $player->alliance->id,
                'player_id' => $application->player_id,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to decline application: ' . $e->getMessage(), 'error');
        }
    }

    public function promoteMember($memberId, $newRank)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance || $player->alliance_rank !== 'leader') {
            $this->addNotification('Only the leader can promote members', 'error');

            return;
        }

        $member = Player::find($memberId);
        if (!$member || $member->alliance_id !== $player->alliance_id) {
            $this->addNotification('Member not found', 'error');

            return;
        }

        try {
            $member->update(['alliance_rank' => $newRank]);
            $this->loadAllianceData();
            $this->addNotification("Promoted {$member->name} to {$newRank}", 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to promote member: ' . $e->getMessage(), 'error');
        }
    }

    public function demoteMember($memberId, $newRank)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance || !in_array($player->alliance_rank, ['leader', 'co_leader'])) {
            $this->addNotification('You do not have permission to demote members', 'error');

            return;
        }

        $member = Player::find($memberId);
        if (!$member || $member->alliance_id !== $player->alliance_id) {
            $this->addNotification('Member not found', 'error');

            return;
        }

        if ($member->alliance_rank === 'leader') {
            $this->addNotification('Cannot demote the leader', 'error');

            return;
        }

        try {
            $member->update(['alliance_rank' => $newRank]);
            $this->loadAllianceData();
            $this->addNotification("Demoted {$member->name} to {$newRank}", 'success');
        } catch (\Exception $e) {
            $this->addNotification('Failed to demote member: ' . $e->getMessage(), 'error');
        }
    }

    public function kickMember($memberId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        if (!$player->alliance || !in_array($player->alliance_rank, ['leader', 'co_leader'])) {
            $this->addNotification('You do not have permission to kick members', 'error');

            return;
        }

        $member = Player::find($memberId);
        if (!$member || $member->alliance_id !== $player->alliance_id) {
            $this->addNotification('Member not found', 'error');

            return;
        }

        if ($member->alliance_rank === 'leader') {
            $this->addNotification('Cannot kick the leader', 'error');

            return;
        }

        try {
            $member->update(['alliance_id' => null, 'alliance_rank' => null]);
            $this->loadAllianceData();
            $this->addNotification("Kicked {$member->name} from alliance", 'success');

            $this->dispatch('memberLeft', [
                'alliance_id' => $player->alliance->id,
                'player_id' => $memberId,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to kick member: ' . $e->getMessage(), 'error');
        }
    }

    public function calculateAllianceStats()
    {
        $this->allianceStats = [
            'total_alliances' => Alliance::where('world_id', $this->world->id)->count(),
            'active_alliances' => Alliance::where('world_id', $this->world->id)->where('is_active', true)->count(),
            'total_members' => Player::where('world_id', $this->world->id)->whereNotNull('alliance_id')->count(),
            'average_members' => Alliance::where('world_id', $this->world->id)->withCount('members')->avg('members_count'),
            'largest_alliance' => Alliance::where('world_id', $this->world->id)->withCount('members')->orderBy('members_count', 'desc')->first(),
            'newest_alliance' => Alliance::where('world_id', $this->world->id)->orderBy('created_at', 'desc')->first(),
        ];
    }

    public function calculateMemberStats()
    {
        if (!$this->myAlliance) {
            $this->memberStats = [];

            return;
        }

        $this->memberStats = [
            'total_members' => $this->myAlliance->members->count(),
            'leaders' => $this->myAlliance->members->where('alliance_rank', 'leader')->count(),
            'co_leaders' => $this->myAlliance->members->where('alliance_rank', 'co_leader')->count(),
            'members' => $this->myAlliance->members->where('alliance_rank', 'member')->count(),
            'average_population' => $this->myAlliance->members->avg('population'),
            'total_population' => $this->myAlliance->members->sum('population'),
        ];
    }

    public function calculateInviteStats()
    {
        if (!$this->myAlliance) {
            $this->inviteStats = [];

            return;
        }

        $this->inviteStats = [
            'total_invites' => $this->myAlliance->invites->count(),
            'pending_invites' => $this->myAlliance->invites->where('status', 'pending')->count(),
            'accepted_invites' => $this->myAlliance->invites->where('status', 'accepted')->count(),
            'declined_invites' => $this->myAlliance->invites->where('status', 'declined')->count(),
        ];
    }

    public function calculateApplicationStats()
    {
        if (!$this->myAlliance) {
            $this->applicationStats = [];

            return;
        }

        $this->applicationStats = [
            'total_applications' => $this->myAlliance->applications->count(),
            'pending_applications' => $this->myAlliance->applications->where('status', 'pending')->count(),
            'accepted_applications' => $this->myAlliance->applications->where('status', 'accepted')->count(),
            'declined_applications' => $this->myAlliance->applications->where('status', 'declined')->count(),
        ];
    }

    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = !$this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
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

    public function getAllianceIcon($alliance)
    {
        $icons = [
            'leader' => '👑',
            'co_leader' => '⭐',
            'member' => '👤',
        ];

        return $icons[$alliance['rank']] ?? '🏰';
    }

    public function getAllianceColor($alliance)
    {
        if ($alliance['is_active']) {
            return 'green';
        }

        if ($alliance['members_count'] >= $alliance['max_members']) {
            return 'red';
        }

        return 'blue';
    }

    public function getAllianceStatus($alliance)
    {
        if (!$alliance['is_active']) {
            return 'Inactive';
        }

        if ($alliance['members_count'] >= $alliance['max_members']) {
            return 'Full';
        }

        return 'Active';
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
            $this->loadAllianceData();
            $this->calculateAllianceStats();
            $this->calculateMemberStats();
            $this->calculateInviteStats();
            $this->calculateApplicationStats();
        }
    }

    #[On('allianceUpdated')]
    public function handleAllianceUpdated($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Alliance updated', 'info');
    }

    #[On('memberJoined')]
    public function handleMemberJoined($data)
    {
        $this->loadAllianceData();
        $this->addNotification('New member joined', 'success');
    }

    #[On('memberLeft')]
    public function handleMemberLeft($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Member left alliance', 'info');
    }

    #[On('inviteSent')]
    public function handleInviteSent($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Invitation sent', 'success');
    }

    #[On('inviteAccepted')]
    public function handleInviteAccepted($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Invitation accepted', 'success');
    }

    #[On('inviteDeclined')]
    public function handleInviteDeclined($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Invitation declined', 'info');
    }

    #[On('applicationSubmitted')]
    public function handleApplicationSubmitted($data)
    {
        $this->loadAllianceData();
        $this->addNotification('New application received', 'info');
    }

    #[On('applicationAccepted')]
    public function handleApplicationAccepted($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Application accepted', 'success');
    }

    #[On('applicationDeclined')]
    public function handleApplicationDeclined($data)
    {
        $this->loadAllianceData();
        $this->addNotification('Application declined', 'info');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->loadAllianceData();
        $this->addNotification('Village selected - alliance data updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.alliance-manager', [
            'world' => $this->world,
            'alliances' => $this->alliances,
            'myAlliance' => $this->myAlliance,
            'selectedAlliance' => $this->selectedAlliance,
            'allianceMembers' => $this->allianceMembers,
            'allianceInvites' => $this->allianceInvites,
            'allianceApplications' => $this->allianceApplications,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'showDetails' => $this->showDetails,
            'selectedMember' => $this->selectedMember,
            'filterByRank' => $this->filterByRank,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyActive' => $this->showOnlyActive,
            'showOnlyInvites' => $this->showOnlyInvites,
            'showOnlyApplications' => $this->showOnlyApplications,
            'allianceStats' => $this->allianceStats,
            'memberStats' => $this->memberStats,
            'inviteStats' => $this->inviteStats,
            'applicationStats' => $this->applicationStats,
        ]);
    }
}
