<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RealTimeGameService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class WebSocketController extends Controller
{
    /**
     * Subscribe to real-time updates
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channels' => 'array',
            'channels.*' => 'string|in:user,village,alliance,global',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $channels = $request->input('channels', ['user']);
            
            // Mark user as online
            RealTimeGameService::markUserOnline($user->id);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'channels' => $channels,
                    'socket_url' => config('broadcasting.connections.pusher.options.host', 'localhost'),
                    'auth_endpoint' => url('/api/websocket/auth'),
                ],
                'message' => 'Successfully subscribed to real-time updates',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to subscribe to updates',
            ], 500);
        }
    }

    /**
     * Unsubscribe from real-time updates
     */
    public function unsubscribe(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Mark user as offline
            RealTimeGameService::markUserOffline($user->id);

            return response()->json([
                'success' => true,
                'message' => 'Successfully unsubscribed from real-time updates',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to unsubscribe from updates',
            ], 500);
        }
    }

    /**
     * Get pending updates
     */
    public function getUpdates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:100',
            'clear' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $limit = $request->input('limit', 50);
            $clear = $request->input('clear', false);

            $updates = RealTimeGameService::getUserUpdates($user->id, $limit);

            if ($clear) {
                RealTimeGameService::clearUserUpdates($user->id);
            }

            return response()->json([
                'success' => true,
                'data' => $updates,
                'count' => count($updates),
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get updates',
            ], 500);
        }
    }

    /**
     * Send test message
     */
    public function sendTestMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:255',
            'type' => 'string|in:info,warning,error,success',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $message = $request->input('message');
            $type = $request->input('type', 'info');

            RealTimeGameService::sendUpdate($user->id, 'test_message', [
                'message' => $message,
                'type' => $type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test message sent successfully',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to send test message',
            ], 500);
        }
    }

    /**
     * Get real-time statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $stats = RealTimeGameService::getRealTimeStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get statistics',
            ], 500);
        }
    }

    /**
     * WebSocket authentication endpoint
     */
    public function auth(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $channel = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            // Validate channel access
            if (!$this->canAccessChannel($user->id, $channel)) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            // Generate authentication signature (simplified)
            $authData = [
                'auth' => base64_encode(json_encode([
                    'user_id' => $user->id,
                    'channel' => $channel,
                    'timestamp' => now()->timestamp,
                ])),
            ];

            return response()->json($authData);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed'], 500);
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
            "presence-game.alliance",
            "presence-game.global",
        ];

        return in_array($channel, $allowedChannels);
    }

    /**
     * Broadcast system announcement
     */
    public function broadcastAnnouncement(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
            'priority' => 'string|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Check if user is admin (simplified check)
            if (!$user->hasRole('admin')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $title = $request->input('title');
            $message = $request->input('message');
            $priority = $request->input('priority', 'normal');

            RealTimeGameService::sendSystemAnnouncement($title, $message, $priority);

            return response()->json([
                'success' => true,
                'message' => 'Announcement broadcasted successfully',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to broadcast announcement',
            ], 500);
        }
    }
}

