<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    protected $chatService;

    public function __construct()
    {
        $this->chatService = new ChatService();
    }

    /**
     * Get messages for a channel
     */
    public function getChannelMessages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|integer',
            'channel_type' => 'required|string|in:global,alliance,private,trade,diplomacy,custom',
            'limit' => 'integer|min:1|max:100',
            'offset' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->chatService->getChannelMessages(
                $request->channel_id,
                $request->channel_type,
                $request->get('limit', 50),
                $request->get('offset', 0)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get channel messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get global messages
     */
    public function getGlobalMessages(Request $request): JsonResponse
    {
        try {
            $result = $this->chatService->getGlobalMessages(
                $request->get('limit', 50),
                $request->get('offset', 0)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get global messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(Request $request): JsonResponse
    {
        $player = Auth::user()->player;
        
        if (!$player || !$player->alliance_id) {
            return response()->json([
                'success' => false,
                'message' => 'Player is not in an alliance',
            ], 400);
        }

        try {
            $result = $this->chatService->getAllianceMessages(
                $player->alliance_id,
                $request->get('limit', 50),
                $request->get('offset', 0)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get alliance messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'required|integer',
            'channel_type' => 'required|string|in:global,alliance,private,trade,diplomacy,custom',
            'message' => 'required|string|max:500',
            'message_type' => 'string|in:text,system,announcement,emote,command',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $player = Auth::user()->player;
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found',
                ], 404);
            }

            $message = $this->chatService->sendMessage(
                $player->id,
                $request->channel_id,
                $request->channel_type,
                $request->message,
                $request->get('message_type', 'text')
            );

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a global message
     */
    public function sendGlobalMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
            'message_type' => 'string|in:text,system,announcement,emote,command',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $player = Auth::user()->player;
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found',
                ], 404);
            }

            $message = $this->chatService->sendGlobalMessage(
                $player->id,
                $request->message,
                $request->get('message_type', 'text')
            );

            return response()->json([
                'success' => true,
                'message' => 'Global message sent successfully',
                'data' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send global message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:500',
            'message_type' => 'string|in:text,system,announcement,emote,command',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $player = Auth::user()->player;
            if (!$player || !$player->alliance_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player is not in an alliance',
                ], 400);
            }

            $message = $this->chatService->sendAllianceMessage(
                $player->id,
                $player->alliance_id,
                $request->message,
                $request->get('message_type', 'text')
            );

            return response()->json([
                'success' => true,
                'message' => 'Alliance message sent successfully',
                'data' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send alliance message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId): JsonResponse
    {
        try {
            $player = Auth::user()->player;
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found',
                ], 404);
            }

            $success = $this->chatService->deleteMessage($messageId, $player->id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete this message',
                ], 403);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available channels
     */
    public function getAvailableChannels(): JsonResponse
    {
        try {
            $player = Auth::user()->player;
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found',
                ], 404);
            }

            $channels = $this->chatService->getAvailableChannels($player->id);

            return response()->json([
                'success' => true,
                'data' => $channels,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get available channels: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a custom channel
     */
    public function createChannel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'description' => 'string|max:255',
            'is_public' => 'boolean',
            'max_members' => 'integer|min:2|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $player = Auth::user()->player;
            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found',
                ], 404);
            }

            $channel = $this->chatService->createChannel(
                $player->id,
                $request->name,
                $request->get('description', ''),
                $request->get('is_public', true),
                $request->get('max_members')
            );

            return response()->json([
                'success' => true,
                'message' => 'Channel created successfully',
                'data' => $channel,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create channel: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chat statistics
     */
    public function getChatStats(): JsonResponse
    {
        try {
            $stats = $this->chatService->getChatStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get chat statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}
