<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\User;
use App\Models\User;
use App\Services\RealTimeGameService;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class WebSocketController extends CrudController
{
    use ApiResponseTrait;
    use ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * Subscribe to real-time updates
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'channels' => 'array',
            'channels.*' => 'string|in:user,village,alliance,global',
        ]);

        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $channels = $validated['channels'] ?? ['user'];

            // Mark user as online
            RealTimeGameService::markUserOnline($user->id);

            LoggingUtil::info('User subscribed to real-time updates', [
                'user_id' => $user->id,
                'channels' => $channels,
            ], 'websocket');

            return $this->successResponse([
                'user_id' => $user->id,
                'channels' => $channels,
                'socket_url' => config('broadcasting.connections.pusher.options.host', 'localhost'),
                'auth_endpoint' => url('/api/websocket/auth'),
            ], 'Successfully subscribed to real-time updates');
        } catch (\Exception $e) {
            LoggingUtil::error('Error subscribing to real-time updates', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Failed to subscribe to updates', 500);
        }
    }

    /**
     * Unsubscribe from real-time updates
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Mark user as offline
            RealTimeGameService::markUserOffline($user->id);

            LoggingUtil::info('User unsubscribed from real-time updates', [
                'user_id' => $user->id,
            ], 'websocket');

            return $this->successResponse(null, 'Successfully unsubscribed from real-time updates');
        } catch (\Exception $e) {
            LoggingUtil::error('Error unsubscribing from real-time updates', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Failed to unsubscribe from updates', 500);
        }
    }

    /**
     * Get pending updates
     */
    public function getUpdates(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'limit' => 'integer|min:1|max:100',
            'clear' => 'boolean',
        ]);

        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $limit = $validated['limit'] ?? 50;
            $clear = $validated['clear'] ?? false;

            $updates = RealTimeGameService::getUserUpdates($user->id, $limit);

            if ($clear) {
                RealTimeGameService::clearUserUpdates($user->id);
            }

            LoggingUtil::info('User updates retrieved', [
                'user_id' => $user->id,
                'updates_count' => count($updates),
                'limit' => $limit,
                'cleared' => $clear,
            ], 'websocket');

            return $this->successResponse([
                'updates' => $updates,
                'count' => count($updates),
            ], 'Updates retrieved successfully');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving user updates', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Failed to get updates', 500);
        }
    }

    /**
     * Send test message
     */
    public function sendTestMessage(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'message' => 'required|string|max:255',
            'type' => 'string|in:info,warning,error,success',
        ]);

        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $message = $validated['message'];
            $type = $validated['type'] ?? 'info';

            RealTimeGameService::sendUpdate($user->id, 'test_message', [
                'message' => $message,
                'type' => $type,
            ]);

            LoggingUtil::info('Test message sent', [
                'user_id' => $user->id,
                'message' => $message,
                'type' => $type,
            ], 'websocket');

            return $this->successResponse(null, 'Test message sent successfully');
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending test message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Failed to send test message', 500);
        }
    }

    /**
     * Get real-time statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $stats = RealTimeGameService::getRealTimeStats();

            LoggingUtil::info('Real-time statistics retrieved', [
                'user_id' => $user->id,
            ], 'websocket');

            return $this->successResponse($stats, 'Real-time statistics retrieved successfully');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving real-time statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Failed to get statistics', 500);
        }
    }

    /**
     * WebSocket authentication endpoint
     */
    public function auth(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $channel = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            // Validate channel access
            if (! $this->canAccessChannel($user->id, $channel)) {
                return $this->errorResponse('Forbidden', 403);
            }

            // Generate authentication signature (simplified)
            $authData = [
                'auth' => base64_encode(json_encode([
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'timestamp' => now()->timestamp,
                ])),
            ];

            LoggingUtil::info('WebSocket authentication successful', [
                'user_id' => $user->id,
                'channel' => $channel,
            ], 'websocket');

            return $this->successResponse($authData, 'Authentication successful');
        } catch (\Exception $e) {
            LoggingUtil::error('WebSocket authentication failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Authentication failed', 500);
        }
    }

    /**
     * Check if user can access a channel
     */
    private function canAccessChannel(int $userId, string $channel): bool
    {
        // Basic channel access validation
        $allowedChannels = [
            "private-game.user.{$userId}",
            'presence-game.alliance',
            'presence-game.global',
        ];

        return in_array($channel, $allowedChannels);
    }

    /**
     * Broadcast system announcement
     */
    public function broadcastAnnouncement(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'string|in:low,normal,high,urgent',
        ]);

        try {
            $user = $request->user();
            if (! $user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Check if user is admin (simplified check)
            if (! $user->hasRole('admin')) {
                return $this->errorResponse('Forbidden', 403);
            }

            $title = $validated['title'];
            $message = $validated['message'];
            $priority = $validated['priority'] ?? 'normal';

            RealTimeGameService::sendSystemAnnouncement($title, $message, $priority);

            LoggingUtil::info('System announcement broadcasted', [
                'user_id' => $user->id,
                'title' => $title,
                'priority' => $priority,
            ], 'websocket');

            return $this->successResponse(null, 'Announcement broadcasted successfully');
        } catch (\Exception $e) {
            LoggingUtil::error('Error broadcasting system announcement', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'websocket');

            return $this->errorResponse('Failed to broadcast announcement', 500);
        }
    }
}
