<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\World;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use SmartCache\Facades\SmartCache;

class ReportManager extends Component
{
    use WithPagination;

    public $world;

    public $reports = [];

    public $selectedReport = null;

    public $notifications = [];

    public $isLoading = false;

    // Real-time features
    public $realTimeUpdates = true;

    public $autoRefresh = true;

    public $refreshInterval = 15;  // seconds

    public $gameSpeed = 1;

    // Filtering and Sorting
    public $filterByType = null;  // 'attack', 'defense', 'support', 'spy', 'trade'

    public $filterByStatus = null;  // 'victory', 'defeat', 'draw'

    public $filterByDate = null;  // 'today', 'week', 'month', 'all'

    public $sortBy = 'created_at';

    public $sortOrder = 'desc';

    public $searchQuery = '';

    public $showOnlyUnread = false;

    public $showOnlyImportant = false;

    public $showOnlyMyReports = true;

    // Report details
    public $showDetails = false;

    public $selectedReportId = null;

    public $reportContent = '';

    public $reportAttachments = [];

    // Stats
    public $reportStats = [];

    public $battleStats = [];

    public $recentActivity = [];

    public $reportHistory = [];

    // Report types for filtering
    public $reportTypes = ['attack', 'defense', 'support', 'spy', 'trade', 'system'];

    public $statusTypes = ['victory', 'defeat', 'draw', 'pending'];

    public $dateRanges = ['today', 'week', 'month', 'all'];

    protected $listeners = [
        'refreshReports',
        'reportReceived',
        'reportUpdated',
        'reportDeleted',
        'markAsRead',
        'markAsUnread',
        'markAsImportant',
        'markAsUnimportant',
        'gameTickProcessed',
        'villageSelected',
    ];

    public function mount($worldId = null, $world = null)
    {
        if ($world) {
            $this->world = $world;
        } elseif ($worldId) {
            $this->world = World::findOrFail($worldId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->world = $player?->village?->world;
        }

        if ($this->world) {
            $this->loadReportData();
            $this->initializeReportFeatures();
        }
    }

    public function initializeReportFeatures()
    {
        $this->calculateReportStats();
        $this->calculateBattleStats();
        $this->calculateRecentActivity();
        $this->calculateReportHistory();

        $this->dispatch('initializeReportRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadReportData()
    {
        $this->isLoading = true;

        try {
            // Use SmartCache for report data with automatic optimization
            $cacheKey = "world_{$this->world->id}_reports_{$this->filterByType}_{$this->filterByStatus}_{$this->showOnlyMyReports}_{$this->showOnlyUnread}_{$this->showOnlyImportant}_{$this->sortBy}_{$this->sortOrder}";

            $this->reports = SmartCache::remember($cacheKey, now()->addMinutes(3), function () {
                $query = Report::where('world_id', $this->world->id)
                    ->with(['attacker:id,name', 'defender:id,name', 'fromVillage:id,name', 'toVillage:id,name']);

                // Build eloquent filters array
                $eloquentFilters = [];

                if ($this->showOnlyMyReports) {
                    $eloquentFilters[] = [
                        'type' => '$or',
                        'value' => [
                            ['target' => 'attacker_id', 'type' => '$eq', 'value' => Auth::id()],
                            ['target' => 'defender_id', 'type' => '$eq', 'value' => Auth::id()]
                        ]
                    ];
                }

                if ($this->filterByType) {
                    $eloquentFilters[] = ['target' => 'type', 'type' => '$eq', 'value' => $this->filterByType];
                }

                if ($this->filterByStatus) {
                    $eloquentFilters[] = ['target' => 'status', 'type' => '$eq', 'value' => $this->filterByStatus];
                }

                if ($this->filterByDate) {
                    $dateFilter = $this->getDateFilterValue();
                    if ($dateFilter) {
                        $eloquentFilters[] = ['target' => 'created_at', 'type' => '$gte', 'value' => $dateFilter];
                    }
                }

                if ($this->showOnlyUnread) {
                    $eloquentFilters[] = ['target' => 'is_read', 'type' => '$eq', 'value' => false];
                }

                if ($this->showOnlyImportant) {
                    $eloquentFilters[] = ['target' => 'is_important', 'type' => '$eq', 'value' => true];
                }

                if ($this->searchQuery) {
                    $eloquentFilters[] = [
                        'type' => '$or',
                        'value' => [
                            ['target' => 'title', 'type' => '$like', 'value' => $this->searchQuery],
                            ['target' => 'content', 'type' => '$like', 'value' => $this->searchQuery],
                            [
                                'type' => '$has',
                                'target' => 'attacker',
                                'value' => [
                                    ['target' => 'name', 'type' => '$like', 'value' => $this->searchQuery]
                                ]
                            ],
                            [
                                'type' => '$has',
                                'target' => 'defender',
                                'value' => [
                                    ['target' => 'name', 'type' => '$like', 'value' => $this->searchQuery]
                                ]
                            ]
                        ]
                    ];
                }

                // Apply eloquent filtering
                if (!empty($eloquentFilters)) {
                    $query = $query->filter($eloquentFilters);
                }

                // Apply sorting
                $query = $query->orderBy($this->sortBy, $this->sortOrder);

                return $query->get();
            });
        } catch (\Exception $e) {
            $this->addNotification('Error loading report data: ' . $e->getMessage(), 'error');
            $this->reports = collect();
        }

        $this->isLoading = false;
    }

    private function getDateFilterValue()
    {
        switch ($this->filterByDate) {
            case 'today':
                return today()->startOfDay();
            case 'week':
                return now()->subWeek();
            case 'month':
                return now()->subMonth();
            case 'year':
                return now()->subYear();
            default:
                return null;
        }
    }

    private function applyDateFilter($query)
    {
        switch ($this->filterByDate) {
            case 'today':
                $query->whereDate('created_at', today());

                break;
            case 'week':
                $query->where('created_at', '>=', now()->subWeek());

                break;
            case 'month':
                $query->where('created_at', '>=', now()->subMonth());

                break;
            case 'all':
            default:
                // No date filter
                break;
        }
    }

    public function selectReport($reportId)
    {
        $this->selectedReport = Report::with(['attacker', 'defender', 'fromVillage', 'toVillage'])->find($reportId);
        $this->selectedReportId = $reportId;
        $this->showDetails = true;
        $this->loadReportContent();
        $this->addNotification("Selected report: {$reportId}", 'info');
    }

    public function loadReportContent()
    {
        if ($this->selectedReport) {
            $this->reportContent = $this->selectedReport->content;
            $this->reportAttachments = $this->selectedReport->attachments ?? [];
        }
    }

    public function markAsRead($reportId)
    {
        // Handle both direct calls and event dispatches
        if (is_array($reportId)) {
            $reportId = $reportId['reportId'] ?? null;
        }

        if (!$reportId) {
            return;
        }

        $report = Report::find($reportId);
        if ($report && ($report->attacker_id === Auth::id() || $report->defender_id === Auth::id())) {
            $report->update(['is_read' => true]);
            $this->loadReportData();
            $this->addNotification("Report {$reportId} marked as read", 'success');
            $this->dispatch('reportUpdated', ['reportId' => $reportId]);
        }
    }

    public function markAsUnread($reportId)
    {
        // Handle both direct calls and event dispatches
        if (is_array($reportId)) {
            $reportId = $reportId['reportId'] ?? null;
        }

        if (!$reportId) {
            return;
        }

        $report = Report::find($reportId);
        if ($report && ($report->attacker_id === Auth::id() || $report->defender_id === Auth::id())) {
            $report->update(['is_read' => false]);
            $this->loadReportData();
            $this->addNotification("Report {$reportId} marked as unread", 'info');
            $this->dispatch('reportUpdated', ['reportId' => $reportId]);
        }
    }

    public function markAsImportant($reportId)
    {
        // Handle both direct calls and event dispatches
        if (is_array($reportId)) {
            $reportId = $reportId['reportId'] ?? null;
        }

        if (!$reportId) {
            return;
        }

        $report = Report::find($reportId);
        if ($report && ($report->attacker_id === Auth::id() || $report->defender_id === Auth::id())) {
            $report->update(['is_important' => true]);
            $this->loadReportData();
            $this->addNotification("Report {$reportId} marked as important", 'success');
            $this->dispatch('reportUpdated', ['reportId' => $reportId]);
        }
    }

    public function markAsUnimportant($reportId)
    {
        // Handle both direct calls and event dispatches
        if (is_array($reportId)) {
            $reportId = $reportId['reportId'] ?? null;
        }

        if (!$reportId) {
            return;
        }

        $report = Report::find($reportId);
        if ($report && ($report->attacker_id === Auth::id() || $report->defender_id === Auth::id())) {
            $report->update(['is_important' => false]);
            $this->loadReportData();
            $this->addNotification("Report {$reportId} marked as unimportant", 'info');
            $this->dispatch('reportUpdated', ['reportId' => $reportId]);
        }
    }

    public function deleteReport($reportId)
    {
        $report = Report::find($reportId);
        if ($report && ($report->attacker_id === Auth::id() || $report->defender_id === Auth::id())) {
            $report->delete();
            $this->loadReportData();
            $this->addNotification("Report {$reportId} deleted", 'info');
            $this->dispatch('reportDeleted', ['reportId' => $reportId]);
        }
    }

    public function markAllAsRead()
    {
        Report::where('world_id', $this->world->id)
            ->where(function ($q) {
                $q
                    ->where('attacker_id', Auth::id())
                    ->orWhere('defender_id', Auth::id());
            })
            ->update(['is_read' => true]);

        $this->loadReportData();
        $this->addNotification('All reports marked as read', 'success');
    }

    public function deleteAllRead()
    {
        $deletedCount = Report::where('world_id', $this->world->id)
            ->where(function ($q) {
                $q
                    ->where('attacker_id', Auth::id())
                    ->orWhere('defender_id', Auth::id());
            })
            ->where('is_read', true)
            ->delete();

        $this->loadReportData();
        $this->addNotification("Deleted {$deletedCount} read reports", 'info');
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function filterByType($type)
    {
        $this->filterByType = $type;
        $this->addNotification("Filtered by type: {$type}", 'info');
    }

    public function filterByStatus($status)
    {
        $this->filterByStatus = $status;
        $this->addNotification("Filtered by status: {$status}", 'info');
    }

    public function filterByDate($date)
    {
        $this->filterByDate = $date;
        $this->addNotification("Filtered by date: {$date}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->filterByStatus = null;
        $this->filterByDate = null;
        $this->searchQuery = '';
        $this->showOnlyUnread = false;
        $this->showOnlyImportant = false;
        $this->showOnlyMyReports = true;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortReports($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'desc';
        }
        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchReports()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }
        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleUnreadFilter()
    {
        $this->showOnlyUnread = !$this->showOnlyUnread;
        $this->addNotification(
            $this->showOnlyUnread ? 'Showing only unread reports' : 'Showing all reports',
            'info'
        );
    }

    public function toggleImportantFilter()
    {
        $this->showOnlyImportant = !$this->showOnlyImportant;
        $this->addNotification(
            $this->showOnlyImportant ? 'Showing only important reports' : 'Showing all reports',
            'info'
        );
    }

    public function toggleMyReportsFilter()
    {
        $this->showOnlyMyReports = !$this->showOnlyMyReports;
        $this->addNotification(
            $this->showOnlyMyReports ? 'Showing only my reports' : 'Showing all reports',
            'info'
        );
    }

    public function calculateReportStats()
    {
        // Use SmartCache for report statistics with automatic optimization
        $statsCacheKey = "world_{$this->world->id}_player_" . Auth::id() . '_report_stats';

        $stats = SmartCache::remember($statsCacheKey, now()->addMinutes(5), function () {
            // Use single query with selectRaw to get all stats at once
            return Report::where('world_id', $this->world->id)
                ->where(function ($q) {
                    $q
                        ->where('attacker_id', Auth::id())
                        ->orWhere('defender_id', Auth::id());
                })
                ->selectRaw('
                    COUNT(*) as total_reports,
                    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_reports,
                    SUM(CASE WHEN is_important = 1 THEN 1 ELSE 0 END) as important_reports,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_reports
                ')
                ->first();
        });

        $this->reportStats = [
            'total_reports' => $stats->total_reports ?? 0,
            'unread_reports' => $stats->unread_reports ?? 0,
            'important_reports' => $stats->important_reports ?? 0,
            'today_reports' => $stats->today_reports ?? 0,
        ];
    }

    public function calculateBattleStats()
    {
        // Use single query with selectRaw to get all battle stats at once
        $stats = Report::where('world_id', $this->world->id)
            ->where(function ($q) {
                $q
                    ->where('attacker_id', Auth::id())
                    ->orWhere('defender_id', Auth::id());
            })
            ->selectRaw('
                SUM(CASE WHEN type = "attack" THEN 1 ELSE 0 END) as total_battles,
                SUM(CASE WHEN type = "attack" AND status = "victory" AND attacker_id = ? THEN 1 ELSE 0 END) as victories,
                SUM(CASE WHEN type = "attack" AND status = "defeat" AND attacker_id = ? THEN 1 ELSE 0 END) as defeats,
                SUM(CASE WHEN type = "defense" AND defender_id = ? THEN 1 ELSE 0 END) as defenses
            ', [Auth::id(), Auth::id(), Auth::id()])
            ->first();

        $this->battleStats = [
            'total_battles' => $stats->total_battles ?? 0,
            'victories' => $stats->victories ?? 0,
            'defeats' => $stats->defeats ?? 0,
            'defenses' => $stats->defenses ?? 0,
        ];
    }

    public function calculateRecentActivity()
    {
        $this->recentActivity = Report::where('world_id', $this->world->id)
            ->where(function ($q) {
                $q
                    ->where('attacker_id', Auth::id())
                    ->orWhere('defender_id', Auth::id());
            })
            ->orderByDesc('created_at')
            ->take(10)
            ->get();
    }

    public function calculateReportHistory()
    {
        $this->reportHistory = Report::where('world_id', $this->world->id)
            ->where(function ($q) {
                $q
                    ->where('attacker_id', Auth::id())
                    ->orWhere('defender_id', Auth::id());
            })
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderByDesc('date')
            ->take(30)
            ->get();
    }

    public function getReportIcon($report)
    {
        return match ($report['type']) {
            'attack' => '⚔️',
            'defense' => '🛡️',
            'support' => '🤝',
            'spy' => '🕵️',
            'trade' => '💰',
            'system' => '📢',
            default => '📄',
        };
    }

    public function getReportColor($report)
    {
        if ($report['is_important']) {
            return 'red';
        }
        if (!$report['is_read']) {
            return 'blue';
        }

        return match ($report['status']) {
            'victory' => 'green',
            'defeat' => 'red',
            'draw' => 'yellow',
            default => 'gray',
        };
    }

    public function getReportStatus($report)
    {
        return match ($report['status']) {
            'victory' => 'Victory',
            'defeat' => 'Defeat',
            'draw' => 'Draw',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    public function getTimeAgo($date)
    {
        return \Carbon\Carbon::parse($date)->diffForHumans();
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
        $this->refreshInterval = max(5, min(300, $interval));
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
            $this->loadReportData();
            $this->calculateReportStats();
            $this->calculateBattleStats();
            $this->calculateRecentActivity();
            $this->calculateReportHistory();
        }
    }

    #[On('reportReceived')]
    public function handleReportReceived($data)
    {
        $this->loadReportData();
        $this->addNotification('New report received', 'success');
    }

    #[On('reportUpdated')]
    public function handleReportUpdated($data)
    {
        $this->loadReportData();
        $this->addNotification('Report updated', 'info');
    }

    #[On('reportDeleted')]
    public function handleReportDeleted($data)
    {
        $this->loadReportData();
        $this->addNotification('Report deleted', 'info');
    }

    #[On('markAsRead')]
    public function handleMarkAsRead($data)
    {
        $this->loadReportData();
        $this->addNotification('Report marked as read', 'success');
    }

    #[On('markAsUnread')]
    public function handleMarkAsUnread($data)
    {
        $this->loadReportData();
        $this->addNotification('Report marked as unread', 'info');
    }

    #[On('markAsImportant')]
    public function handleMarkAsImportant($data)
    {
        $this->loadReportData();
        $this->addNotification('Report marked as important', 'success');
    }

    #[On('markAsUnimportant')]
    public function handleMarkAsUnimportant($data)
    {
        $this->loadReportData();
        $this->addNotification('Report marked as unimportant', 'info');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $player = Player::where('user_id', Auth::id())->first();
        $this->world = $player?->village?->world;
        $this->loadReportData();
        $this->addNotification('Village selected - report data updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.report-manager', [
            'reports' => $this->reports,
            'selectedReport' => $this->selectedReport,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'filterByType' => $this->filterByType,
            'filterByStatus' => $this->filterByStatus,
            'filterByDate' => $this->filterByDate,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyUnread' => $this->showOnlyUnread,
            'showOnlyImportant' => $this->showOnlyImportant,
            'showOnlyMyReports' => $this->showOnlyMyReports,
            'showDetails' => $this->showDetails,
            'selectedReportId' => $this->selectedReportId,
            'reportContent' => $this->reportContent,
            'reportAttachments' => $this->reportAttachments,
            'reportStats' => $this->reportStats,
            'battleStats' => $this->battleStats,
            'recentActivity' => $this->recentActivity,
            'reportHistory' => $this->reportHistory,
            'reportTypes' => $this->reportTypes,
            'statusTypes' => $this->statusTypes,
            'dateRanges' => $this->dateRanges,
        ]);
    }

    /**
     * Initialize real-time features for the component
     */
    public function initializeRealTimeFeatures(): void
    {
        try {
            GameIntegrationService::initializeUserRealTime(Auth::id());
            
            $this->dispatch('realtime-initialized', [
                'message' => 'Real-time features activated for Report Manager',
                'refreshInterval' => $this->refreshInterval,
            ]);

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize real-time features: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle real-time report updates
     */
    #[On('report-updated')]
    public function handleReportUpdate($reportData): void
    {
        try {
            // Update local reports array
            $this->reports = $this->getReports();
            
            // Send notification if it's a new report
            if (isset($reportData['is_new']) && $reportData['is_new']) {
                GameNotificationService::sendNotification(
                    [Auth::id()],
                    'new_report',
                    [
                        'report_id' => $reportData['id'],
                        'type' => $reportData['type'],
                        'title' => $reportData['title'] ?? 'New Report',
                    ]
                );
            }

            $this->dispatch('reports-updated');

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to handle report update: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get comprehensive report statistics with real-time data
     */
    public function getReportStatisticsWithRealTime(): array
    {
        try {
            $stats = GameIntegrationService::getGameStatisticsWithRealTime();
            
            return [
                'total_reports' => $this->reports->count(),
                'recent_reports' => $this->reports->where('created_at', '>=', now()->subHours(24))->count(),
                'battle_reports' => $this->reports->where('type', 'battle')->count(),
                'spy_reports' => $this->reports->where('type', 'spy')->count(),
                'trade_reports' => $this->reports->where('type', 'trade')->count(),
                'game_stats' => $stats,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            return [
                'error' => 'Failed to retrieve report statistics',
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Send report notification to relevant players
     */
    public function sendReportNotification(array $playerIds, string $type, array $data): void
    {
        try {
            GameNotificationService::sendNotification(
                $playerIds,
                "report_{$type}",
                $data,
                'normal'
            );

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to send report notification: ' . $e->getMessage(),
            ]);
        }
    }
}
