<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\Notification;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\FilteringUtil;

/**
 * @group Notification Management
 *
 * API endpoints for managing game notifications and alerts.
 * Notifications provide real-time updates about game events and activities.
 *
 * @authenticated
 *
 * @tag Notification System
 * @tag Game Alerts
 * @tag Real-time Updates
 */
class NotificationController extends CrudController
{
    use ApiResponseTrait;
    use ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct(new Notification());
    }

    /**
     * Get all notifications
     *
     * @authenticated
     *
     * @description Retrieve a paginated list of all notifications for the authenticated player.
     *
     * @queryParam page int The page number for pagination. Example: 1
     * @queryParam per_page int Number of items per page. Example: 15
     * @queryParam type string Filter by notification type. Example: "battle"
     * @queryParam is_read boolean Filter by read status. Example: false
     * @queryParam priority string Filter by priority (low, normal, high, urgent). Example: "high"
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "player_id": 1,
     *       "type": "battle",
     *       "title": "Village Under Attack!",
     *       "message": "Your village 'Capital City' is being attacked by PlayerTwo",
     *       "data": {
     *         "attacker_id": 2,
     *         "village_id": 5,
     *         "attack_time": "2023-01-01T12:00:00Z"
     *       },
     *       "priority": "urgent",
     *       "is_read": false,
     *       "expires_at": "2023-01-01T13:00:00.000000Z",
     *       "created_at": "2023-01-01T12:00:00.000000Z"
     *     }
     *   ]
     * }
     *
     * @tag Notification System
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $query = Notification::where('player_id', $playerId);

            // Apply filters using FilteringUtil
            $filters = [];

            if ($request->has('type')) {
                $filters[] = ['target' => 'type', 'type' => '$eq', 'value' => $request->input('type')];
            }

            if ($request->has('is_read')) {
                $filters[] = ['target' => 'is_read', 'type' => '$eq', 'value' => $request->boolean('is_read')];
            }

            if ($request->has('priority')) {
                $filters[] = ['target' => 'priority', 'type' => '$eq', 'value' => $request->input('priority')];
            }

            if (! empty($filters)) {
                $query = $query->filter($filters);
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 15));

            return $this->paginatedResponse($notifications, 'Notifications retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving notifications', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Failed to retrieve notifications: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get specific notification
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific notification.
     *
     * @urlParam id int required The ID of the notification. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "player_id": 1,
     *   "type": "battle",
     *   "title": "Village Under Attack!",
     *   "message": "Your village 'Capital City' is being attacked by PlayerTwo",
     *   "data": {
     *     "attacker_id": 2,
     *     "village_id": 5,
     *     "attack_time": "2023-01-01T12:00:00Z"
     *   },
     *   "priority": "urgent",
     *   "is_read": true,
     *   "expires_at": "2023-01-01T13:00:00.000000Z",
     *   "created_at": "2023-01-01T12:00:00.000000Z",
     *   "updated_at": "2023-01-01T12:00:00.000000Z"
     * }
     * @response 404 {
     *   "message": "Notification not found"
     * }
     *
     * @tag Notification System
     */
    public function show(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $notification = Notification::where('player_id', $playerId)
                ->findOrFail($id);

            // Mark as read if unread
            if (! $notification->is_read) {
                $notification->update(['is_read' => true]);
            }

            return $this->successResponse($notification, 'Notification retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving notification', [
                'error' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Notification not found', 404);
        }
    }

    /**
     * Mark notification as read
     *
     * @authenticated
     *
     * @description Mark a specific notification as read.
     *
     * @urlParam id int required The ID of the notification. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Notification marked as read"
     * }
     * @response 404 {
     *   "message": "Notification not found"
     * }
     *
     * @tag Notification System
     */
    public function markAsRead(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $notification = Notification::where('player_id', $playerId)
                ->findOrFail($id);

            $notification->update(['is_read' => true]);

            return $this->successResponse(null, 'Notification marked as read');

        } catch (\Exception $e) {
            LoggingUtil::error('Error marking notification as read', [
                'error' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Notification not found', 404);
        }
    }

    /**
     * Mark all notifications as read
     *
     * @authenticated
     *
     * @description Mark all unread notifications as read for the authenticated player.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "All notifications marked as read",
     *   "updated_count": 8
     * }
     *
     * @tag Notification System
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $updatedCount = Notification::where('player_id', $playerId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            return $this->successResponse(['updated_count' => $updatedCount], 'All notifications marked as read');

        } catch (\Exception $e) {
            LoggingUtil::error('Error marking all notifications as read', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Failed to mark notifications as read: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete notification
     *
     * @authenticated
     *
     * @description Delete a specific notification.
     *
     * @urlParam id int required The ID of the notification. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Notification deleted successfully"
     * }
     * @response 404 {
     *   "message": "Notification not found"
     * }
     *
     * @tag Notification System
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $notification = Notification::where('player_id', $playerId)
                ->findOrFail($id);

            $notification->delete();

            return $this->successResponse(null, 'Notification deleted successfully');

        } catch (\Exception $e) {
            LoggingUtil::error('Error deleting notification', [
                'error' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Notification not found', 404);
        }
    }

    /**
     * Get unread notifications count
     *
     * @authenticated
     *
     * @description Get the count of unread notifications for the authenticated player.
     *
     * @response 200 {
     *   "unread_count": 5
     * }
     *
     * @tag Notification System
     */
    public function unreadCount(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $unreadCount = Notification::where('player_id', $playerId)
                ->where('is_read', false)
                ->count();

            return $this->successResponse(['unread_count' => $unreadCount], 'Unread count retrieved successfully');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving unread count', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Failed to retrieve unread count: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get notification statistics
     *
     * @authenticated
     *
     * @description Get comprehensive notification statistics for the authenticated player.
     *
     * @response 200 {
     *   "total_notifications": 50,
     *   "unread_notifications": 8,
     *   "read_notifications": 42,
     *   "by_type": {
     *     "battle": 25,
     *     "system": 15,
     *     "alliance": 7,
     *     "trade": 3
     *   },
     *   "by_priority": {
     *     "urgent": 5,
     *     "high": 10,
     *     "normal": 30,
     *     "low": 5
     *   }
     * }
     *
     * @tag Notification System
     */
    public function statistics(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;

            $totalNotifications = Notification::where('player_id', $playerId)->count();
            $unreadNotifications = Notification::where('player_id', $playerId)
                ->where('is_read', false)
                ->count();
            $readNotifications = Notification::where('player_id', $playerId)
                ->where('is_read', true)
                ->count();

            $notificationsByType = Notification::where('player_id', $playerId)
                ->select('type', \DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray();

            $notificationsByPriority = Notification::where('player_id', $playerId)
                ->select('priority', \DB::raw('COUNT(*) as count'))
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray();

            return $this->successResponse([
                'total_notifications' => $totalNotifications,
                'unread_notifications' => $unreadNotifications,
                'read_notifications' => $readNotifications,
                'by_type' => $notificationsByType,
                'by_priority' => $notificationsByPriority,
            ], 'Notification statistics retrieved successfully');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving notification statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'notification_system');

            return $this->errorResponse('Failed to retrieve notification statistics: '.$e->getMessage(), 500);
        }
    }

    /**
     * Create notification
     *
     * @authenticated
     *
     * @description Create a new notification for a player (typically done by the game system).
     *
     * @bodyParam player_id int required The ID of the player to notify. Example: 1
     * @bodyParam type string required The type of notification. Example: "battle"
     * @bodyParam title string required The notification title. Example: "Village Under Attack!"
     * @bodyParam message string required The notification message. Example: "Your village is being attacked"
     * @bodyParam data object Additional data for the notification. Example: {"attacker_id": 2, "village_id": 5}
     * @bodyParam priority string The priority level (low, normal, high, urgent). Example: "urgent"
     * @bodyParam expires_at string When the notification expires. Example: "2023-01-01T13:00:00Z"
     *
     * @response 201 {
     *   "success": true,
     *   "notification": {
     *     "id": 1,
     *     "player_id": 1,
     *     "type": "battle",
     *     "title": "Village Under Attack!",
     *     "message": "Your village is being attacked",
     *     "priority": "urgent",
     *     "is_read": false,
     *     "created_at": "2023-01-01T12:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "player_id": ["The player id field is required."],
     *     "title": ["The title field is required."]
     *   }
     * }
     *
     * @tag Notification System
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validationRules = [
                'player_id' => 'required|exists:players,id',
                'type' => 'required|string|max:255',
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'data' => 'nullable|array',
                'priority' => 'nullable|in:low,normal,high,urgent',
                'expires_at' => 'nullable|date',
            ];

            $validator = $this->validateRequestData($request, $validationRules);
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $notification = Notification::create([
                'player_id' => $request->input('player_id'),
                'type' => $request->input('type'),
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'data' => $request->input('data', []),
                'priority' => $request->input('priority', 'normal'),
                'expires_at' => $request->input('expires_at'),
                'is_read' => false,
            ]);

            return $this->successResponse($notification, 'Notification created successfully.', 201);

        } catch (\Exception $e) {
            LoggingUtil::error('Error creating notification', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ], 'notification_system');

            return $this->errorResponse('Failed to create notification: '.$e->getMessage(), 500);
        }
    }
}
