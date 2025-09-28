<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Traits\GameValidationTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FilteringUtil;

/**
 * @group Report Management
 *
 * API endpoints for managing game reports, battle reports, and system notifications.
 * Reports provide detailed information about game events and activities.
 *
 * @authenticated
 *
 * @tag Report System
 * @tag Battle Reports
 * @tag Game Events
 */
class ReportController extends CrudController
{
    use ApiResponseTrait;
    use GameValidationTrait;

    protected Model $model;

    protected array $validationRules = [
        'player_id' => 'required|exists:players,id',
        'type' => 'required|string|in:battle,resource,construction,attack,defense,system',
        'title' => 'required|string|max:255',
        'content' => 'required|string',
        'data' => 'nullable|json',
        'status' => 'required|string|in:unread,read,archived',
        'priority' => 'required|string|in:low,medium,high,critical',
        'is_important' => 'boolean',
    ];

    protected array $searchableFields = ['title', 'content', 'type'];

    protected array $relationships = ['player'];

    protected int $perPage = 20;

    public function __construct()
    {
        $this->model = new Report();
        parent::__construct($this->model);
    }

    /**
     * Get all reports
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all reports for the authenticated player.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam type string Filter by report type. Example: "battle"
     * @queryParam status string Filter by report status. Example: "unread"
     * @queryParam date_from string Filter reports from date. Example: "2023-01-01"
     * @queryParam date_to string Filter reports to date. Example: "2023-12-31"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "player_id": 1,
     *       "type": "battle",
     *       "title": "Battle Report: Victory",
     *       "content": "You successfully attacked PlayerTwo's village",
     *       "data": {
     *         "attacker_id": 1,
     *         "defender_id": 2,
     *         "result": "victory",
     *         "loot": {
     *           "wood": 1000,
     *           "clay": 800
     *         }
     *       },
     *       "status": "unread",
     *       "created_at": "2023-01-01T12:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @tag Report System
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $query = Report::where('player_id', $playerId);

            // Apply filters using FilteringUtil
            $filters = [];

            if ($request->has('type')) {
                $filters[] = ['target' => 'type', 'type' => '$eq', 'value' => $request->input('type')];
            }

            if ($request->has('status')) {
                $filters[] = ['target' => 'status', 'type' => '$eq', 'value' => $request->input('status')];
            }

            if ($request->has('date_from')) {
                $filters[] = ['target' => 'created_at', 'type' => '$gte', 'value' => $request->input('date_from')];
            }

            if ($request->has('date_to')) {
                $filters[] = ['target' => 'created_at', 'type' => '$lte', 'value' => $request->input('date_to')];
            }

            if (! empty($filters)) {
                $query = $query->filter($filters);
            }

            $reports = $query
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            LoggingUtil::info('Reports retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'filters' => $request->only(['type', 'status', 'date_from', 'date_to']),
                'count' => $reports->count(),
            ], 'report_system');

            return $this->paginatedResponse($reports, 'Reports retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving reports', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'report_system');

            return $this->errorResponse('Failed to retrieve reports: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get specific report
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific report.
     *
     * @urlParam id int required The ID of the report. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "player_id": 1,
     *   "type": "battle",
     *   "title": "Battle Report: Victory",
     *   "content": "You successfully attacked PlayerTwo's village",
     *   "data": {
     *     "attacker_id": 1,
     *     "defender_id": 2,
     *     "result": "victory",
     *     "loot": {
     *       "wood": 1000,
     *       "clay": 800
     *     }
     *   },
     *   "status": "read",
     *   "created_at": "2023-01-01T12:00:00.000000Z",
     *   "updated_at": "2023-01-01T12:00:00.000000Z"
     * }
     * @response 404 {
     *   "message": "Report not found"
     * }
     *
     * @tag Report System
     */
    public function show(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $report = Report::where('player_id', $playerId)
                ->findOrFail($id);

            // Mark as read if unread
            if ($report->status === 'unread') {
                $report->update(['status' => 'read']);
            }

            LoggingUtil::info('Report details retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'report_id' => $id,
            ], 'report_system');

            return $this->successResponse($report, 'Report details retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving report details', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'report_id' => $id,
            ], 'report_system');

            return $this->errorResponse('Report not found', 404);
        }
    }

    /**
     * Mark report as read
     *
     * @authenticated
     *
     * @description Mark a specific report as read.
     *
     * @urlParam id int required The ID of the report. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Report marked as read"
     * }
     * @response 404 {
     *   "message": "Report not found"
     * }
     *
     * @tag Report System
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $report = Report::where('player_id', $playerId)
                ->findOrFail($id);

            $report->update(['status' => 'read']);

            LoggingUtil::info('Report marked as read', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'report_id' => $id,
            ], 'report_system');

            return $this->successResponse(null, 'Report marked as read.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error marking report as read', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'report_id' => $id,
            ], 'report_system');

            return $this->errorResponse('Report not found', 404);
        }
    }

    /**
     * Mark all reports as read
     *
     * @authenticated
     *
     * @description Mark all unread reports as read for the authenticated player.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "All reports marked as read",
     *   "updated_count": 5
     * }
     *
     * @tag Report System
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $updatedCount = Report::where('player_id', $playerId)
                ->where('status', 'unread')
                ->update(['status' => 'read']);

            LoggingUtil::info('All reports marked as read', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'updated_count' => $updatedCount,
            ], 'report_system');

            return $this->successResponse([
                'updated_count' => $updatedCount,
            ], 'All reports marked as read.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error marking all reports as read', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'report_system');

            return $this->errorResponse('Failed to mark reports as read: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete report
     *
     * @authenticated
     *
     * @description Delete a specific report.
     *
     * @urlParam id int required The ID of the report. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Report deleted successfully"
     * }
     * @response 404 {
     *   "message": "Report not found"
     * }
     *
     * @tag Report System
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $report = Report::where('player_id', $playerId)
                ->findOrFail($id);

            $report->delete();

            LoggingUtil::info('Report deleted', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'report_id' => $id,
            ], 'report_system');

            return $this->successResponse(null, 'Report deleted successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error deleting report', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'report_id' => $id,
            ], 'report_system');

            return $this->errorResponse('Report not found', 404);
        }
    }

    /**
     * Get report statistics
     *
     * @authenticated
     *
     * @description Get comprehensive report statistics for the authenticated player.
     *
     * @response 200 {
     *   "total_reports": 100,
     *   "unread_reports": 15,
     *   "read_reports": 85,
     *   "by_type": {
     *     "battle": 60,
     *     "system": 25,
     *     "alliance": 10,
     *     "trade": 5
     *   },
     *   "recent_reports": [
     *     {
     *       "id": 1,
     *       "type": "battle",
     *       "title": "Battle Report: Victory",
     *       "status": "unread",
     *       "created_at": "2023-01-01T12:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @tag Report System
     */
    public function statistics(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $totalReports = Report::where('player_id', $playerId)->count();
            $unreadReports = Report::where('player_id', $playerId)
                ->where('status', 'unread')
                ->count();
            $readReports = Report::where('player_id', $playerId)
                ->where('status', 'read')
                ->count();

            $reportsByType = Report::where('player_id', $playerId)
                ->select('type', \DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            $recentReports = Report::where('player_id', $playerId)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(['id', 'type', 'title', 'status', 'created_at']);

            $stats = [
                'total_reports' => $totalReports,
                'unread_reports' => $unreadReports,
                'read_reports' => $readReports,
                'by_type' => $reportsByType,
                'recent_reports' => $recentReports,
            ];

            LoggingUtil::info('Report statistics retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'total_reports' => $totalReports,
                'unread_reports' => $unreadReports,
            ], 'report_system');

            return $this->successResponse($stats, 'Report statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving report statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'report_system');

            return $this->errorResponse('Failed to retrieve report statistics: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get unread reports count
     *
     * @authenticated
     *
     * @description Get the count of unread reports for the authenticated player.
     *
     * @response 200 {
     *   "unread_count": 5
     * }
     *
     * @tag Report System
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $unreadCount = Report::where('player_id', $playerId)
                ->where('status', 'unread')
                ->count();

            LoggingUtil::info('Unread reports count retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'unread_count' => $unreadCount,
            ], 'report_system');

            return $this->successResponse([
                'unread_count' => $unreadCount,
            ], 'Unread count retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving unread count', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'report_system');

            return $this->errorResponse('Failed to retrieve unread count: '.$e->getMessage(), 500);
        }
    }
}
