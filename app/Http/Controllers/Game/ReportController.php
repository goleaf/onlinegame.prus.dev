<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Report;
use App\Models\Game\Player;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;

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
    use ApiResponseTrait, GameValidationTrait;

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

            // Apply filters
            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->has('date_from')) {
                $query->where('created_at', '>=', $request->input('date_from'));
            }

            if ($request->has('date_to')) {
                $query->where('created_at', '<=', $request->input('date_to'));
            }

            $reports = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return response()->json($reports);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve reports: ' . $e->getMessage()
            ], 500);
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
     *
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

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
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
     *
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

            return response()->json([
                'success' => true,
                'message' => 'Report marked as read'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
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

            return response()->json([
                'success' => true,
                'message' => 'All reports marked as read',
                'updated_count' => $updatedCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark reports as read: ' . $e->getMessage()
            ], 500);
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
     *
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

            return response()->json([
                'success' => true,
                'message' => 'Report deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found'
            ], 404);
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

            return response()->json([
                'total_reports' => $totalReports,
                'unread_reports' => $unreadReports,
                'read_reports' => $readReports,
                'by_type' => $reportsByType,
                'recent_reports' => $recentReports
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve report statistics: ' . $e->getMessage()
            ], 500);
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

            return response()->json([
                'unread_count' => $unreadCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve unread count: ' . $e->getMessage()
            ], 500);
        }
    }
}
