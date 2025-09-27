<?php

namespace App\Livewire\Game;

use App\Models\User;
use App\Services\LarautilxIntegrationService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\PaginationUtil;
use SmartCache\Facades\SmartCache;
use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination, ApiResponseTrait;

    public $users = [];
    public $selectedUser = null;
    public $searchQuery = '';
    public $filterByWorld = '';
    public $filterByTribe = '';
    public $filterByAlliance = '';
    public $filterByStatus = '';
    public $showOnlyOnline = false;
    public $showOnlyActive = false;
    public $sortBy = 'created_at';
    public $sortOrder = 'desc';
    public $perPage = 20;
    public $isLoading = false;
    public $statistics = [];
    public $selectedUserIds = [];
    public $bulkAction = '';
    public $showBulkActions = false;

    protected $listeners = [
        'userSelected' => 'handleUserSelection',
        'userUpdated' => 'handleUserUpdate',
        'bulkActionCompleted' => 'handleBulkActionCompleted',
    ];

    public function mount()
    {
        $this->loadUsers();
        $this->loadStatistics();
    }

    public function loadUsers()
    {
        $this->isLoading = true;

        try {
            $query = User::withGamePlayers()
                ->with(['player.world', 'player.alliance']);

            // Apply search
            if (!empty($this->searchQuery)) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchQuery . '%')
                      ->orWhere('email', 'like', '%' . $this->searchQuery . '%')
                      ->orWhereHas('player', function ($playerQuery) {
                          $playerQuery->where('name', 'like', '%' . $this->searchQuery . '%');
                      });
                });
            }

            // Apply filters
            if (!empty($this->filterByWorld)) {
                $query->byWorld($this->filterByWorld);
            }

            if (!empty($this->filterByTribe)) {
                $query->byTribe($this->filterByTribe);
            }

            if (!empty($this->filterByAlliance)) {
                $query->byAlliance($this->filterByAlliance);
            }

            if ($this->showOnlyOnline) {
                $query->onlineUsers();
            }

            if ($this->showOnlyActive) {
                $query->activeGameUsers();
            }

            // Apply sorting
            $query->orderBy($this->sortBy, $this->sortOrder);

            $this->users = $query->get();

            // Add game statistics to each user
            $this->users->transform(function ($user) {
                $user->game_stats = $user->getGameStats();
                $user->is_online = $user->isOnline();
                $user->last_activity = $user->getLastActivity();
                return $user;
            });

            // Apply additional filtering using FilteringUtil
            if (!empty($this->filterByStatus)) {
                $this->users = FilteringUtil::filter(
                    $this->users,
                    'is_online',
                    $this->filterByStatus === 'online' ? 'equals' : 'not_equals',
                    true
                );
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error loading users', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'user_management');

            $this->addNotification('Error loading users: ' . $e->getMessage(), 'error');
            $this->users = collect();
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadStatistics()
    {
        try {
            $integrationService = app(LarautilxIntegrationService::class);

            $this->statistics = $integrationService->cacheGameData(
                'user_management_stats',
                function() {
                    return [
                        'total_users' => User::count(),
                        'users_with_players' => User::withGamePlayers()->count(),
                        'active_game_users' => User::activeGameUsers()->count(),
                        'online_users' => User::onlineUsers()->count(),
                        'users_by_tribe' => User::withGamePlayers()
                            ->join('players', 'users.id', '=', 'players.user_id')
                            ->selectRaw('players.tribe, COUNT(*) as count')
                            ->groupBy('players.tribe')
                            ->pluck('count', 'tribe'),
                        'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
                    ];
                },
                300 // 5 minutes cache
            );

        } catch (\Exception $e) {
            LoggingUtil::error('Error loading user statistics', [
                'error' => $e->getMessage()
            ], 'user_management');

            $this->statistics = [
                'total_users' => 0,
                'users_with_players' => 0,
                'active_game_users' => 0,
                'online_users' => 0,
                'users_by_tribe' => collect(),
                'recent_registrations' => 0,
            ];
        }
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedFilterByWorld()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedFilterByTribe()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedFilterByAlliance()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedFilterByStatus()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedShowOnlyOnline()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedShowOnlyActive()
    {
        $this->resetPage();
        $this->loadUsers();
    }

    public function updatedSortBy()
    {
        $this->loadUsers();
    }

    public function updatedSortOrder()
    {
        $this->loadUsers();
    }

    public function selectUser($userId)
    {
        $user = $this->users->firstWhere('id', $userId);
        if ($user) {
            $this->selectedUser = $user;
            $this->dispatch('userSelected', ['user' => $user]);
        }
    }

    public function toggleUserSelection($userId)
    {
        if (in_array($userId, $this->selectedUserIds)) {
            $this->selectedUserIds = array_diff($this->selectedUserIds, [$userId]);
        } else {
            $this->selectedUserIds[] = $userId;
        }

        $this->showBulkActions = count($this->selectedUserIds) > 0;
    }

    public function selectAllUsers()
    {
        $this->selectedUserIds = $this->users->pluck('id')->toArray();
        $this->showBulkActions = true;
    }

    public function clearSelection()
    {
        $this->selectedUserIds = [];
        $this->showBulkActions = false;
    }

    public function executeBulkAction()
    {
        if (empty($this->selectedUserIds) || empty($this->bulkAction)) {
            return;
        }

        try {
            $updatedCount = 0;

            foreach ($this->selectedUserIds as $userId) {
                $user = User::find($userId);
                if ($user && $user->player) {
                    switch ($this->bulkAction) {
                        case 'activate':
                            $user->player->update(['is_active' => true]);
                            $updatedCount++;
                            break;
                        case 'deactivate':
                            $user->player->update(['is_active' => false]);
                            $updatedCount++;
                            break;
                        case 'set_online':
                            $user->player->update(['is_online' => true, 'last_active_at' => now()]);
                            $updatedCount++;
                            break;
                        case 'set_offline':
                            $user->player->update(['is_online' => false]);
                            $updatedCount++;
                            break;
                    }
                }
            }

            LoggingUtil::info('Bulk user action executed', [
                'action' => $this->bulkAction,
                'user_ids' => $this->selectedUserIds,
                'updated_count' => $updatedCount,
                'executed_by' => Auth::id(),
            ], 'user_management');

            $this->addNotification("Bulk action completed. {$updatedCount} users updated.", 'success');
            $this->clearSelection();
            $this->loadUsers();
            $this->loadStatistics();

        } catch (\Exception $e) {
            LoggingUtil::error('Error executing bulk action', [
                'error' => $e->getMessage(),
                'action' => $this->bulkAction,
                'user_ids' => $this->selectedUserIds,
            ], 'user_management');

            $this->addNotification('Error executing bulk action: ' . $e->getMessage(), 'error');
        }
    }

    public function refreshUsers()
    {
        $this->loadUsers();
        $this->loadStatistics();
        $this->addNotification('User list refreshed successfully', 'success');
    }

    public function exportUsers()
    {
        try {
            $users = $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'player_name' => $user->game_stats['player_name'] ?? 'N/A',
                    'tribe' => $user->game_stats['tribe'] ?? 'N/A',
                    'points' => $user->game_stats['points'] ?? 0,
                    'village_count' => $user->game_stats['village_count'] ?? 0,
                    'is_online' => $user->is_online ? 'Yes' : 'No',
                    'is_active' => $user->game_stats['is_active'] ?? false ? 'Yes' : 'No',
                    'last_active' => $user->last_activity ? $user->last_activity->format('Y-m-d H:i:s') : 'N/A',
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            });

            $this->dispatch('exportUsers', ['data' => $users->toArray()]);
            $this->addNotification('User data exported successfully', 'success');

        } catch (\Exception $e) {
            LoggingUtil::error('Error exporting users', [
                'error' => $e->getMessage()
            ], 'user_management');

            $this->addNotification('Error exporting users: ' . $e->getMessage(), 'error');
        }
    }

    public function handleUserSelection($data)
    {
        $this->selectedUser = $data['user'] ?? null;
    }

    public function handleUserUpdate($data)
    {
        $this->loadUsers();
        $this->loadStatistics();
        $this->addNotification('User updated successfully', 'success');
    }

    public function handleBulkActionCompleted($data)
    {
        $this->clearSelection();
        $this->loadUsers();
        $this->loadStatistics();
        $this->addNotification('Bulk action completed successfully', 'success');
    }

    public function addNotification($message, $type = 'info')
    {
        $this->dispatch('notification', [
            'message' => $message,
            'type' => $type
        ]);
    }

    public function render()
    {
        // Use PaginationUtil for consistent pagination
        $paginatedUsers = PaginationUtil::paginate(
            $this->users->toArray(),
            $this->perPage,
            $this->getPage(),
            ['path' => request()->url()]
        );

        return view('livewire.game.user-management', [
            'paginatedUsers' => $paginatedUsers,
            'worlds' => \App\Models\Game\World::active()->get(),
            'tribes' => ['roman', 'teuton', 'gaul'],
            'alliances' => \App\Models\Game\Alliance::active()->get(),
        ]);
    }
}
