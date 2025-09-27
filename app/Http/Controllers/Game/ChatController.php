<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\ChatMessage;
use App\Models\Game\ChatChannel;
use App\Services\ChatService;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use App\Traits\GameValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use JonPurvis\Squeaky\Rules\Clean;

class ChatController extends Controller
{
    use GameValidationTrait;
    
    protected $chatService;

    public function __construct()
    {
        $this->chatService = new ChatService();
    }

    /**
     * Get messages for a channel
     */
    public function getChannelMessages(Request $request, int $channelId): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $result = $this->chatService->getChannelMessages($channelId, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get messages by channel type
     */
    public function getMessagesByType(Request $request, string $channelType): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $result = $this->chatService->getMessagesByType($channelType, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'channel_id' => 'nullable|exists:chat_channels,id',
            'channel_type' => 'required|in:global,alliance,private,trade,diplomacy',
            'message' => ['required', 'string', 'max:1000', new Clean],
            'message_type' => 'required|in:text,system,announcement,emote,command',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->chatService->sendMessage(
                Auth::user()->player->id,
                $request->channel_id,
                $request->channel_type,
                $request->message,
                $request->message_type
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
            'message' => ['required', 'string', 'max:1000', new Clean],
            'message_type' => 'required|in:text,system,announcement,emote,command',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->chatService->sendGlobalMessage(
                Auth::user()->player->id,
                $request->message,
                $request->message_type
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
        $player = Auth::user()->player;
        
        if (!$player->alliance_id) {
            return response()->json([
                'success' => false,
                'message' => 'Player is not in an alliance',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'message' => ['required', 'string', 'max:1000', new Clean],
            'message_type' => 'required|in:text,system,announcement,emote,command',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->chatService->sendAllianceMessage(
                $player->id,
                $player->alliance_id,
                $request->message,
                $request->message_type
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
     * Send a private message
     */
    public function sendPrivateMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:players,id',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->chatService->sendPrivateMessage(
                Auth::user()->player->id,
                $request->recipient_id,
                $request->message
            );

            return response()->json([
                'success' => true,
                'message' => 'Private message sent successfully',
                'data' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send private message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a trade message
     */
    public function sendTradeMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->chatService->sendTradeMessage(
                Auth::user()->player->id,
                $request->message
            );

            return response()->json([
                'success' => true,
                'message' => 'Trade message sent successfully',
                'data' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send trade message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send a diplomacy message
     */
    public function sendDiplomacyMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->chatService->sendDiplomacyMessage(
                Auth::user()->player->id,
                $request->message
            );

            return response()->json([
                'success' => true,
                'message' => 'Diplomacy message sent successfully',
                'data' => $message,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send diplomacy message: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId): JsonResponse
    {
        try {
            $success = $this->chatService->deleteMessage($messageId, Auth::user()->player->id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message deleted successfully',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found or access denied',
                ], 404);
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
            $channels = $this->chatService->getAvailableChannels(Auth::user()->player->id);

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
     * Get channel statistics
     */
    public function getChannelStats(int $channelId): JsonResponse
    {
        try {
            $stats = $this->chatService->getChannelStats($channelId);

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get channel statistics: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search messages
     */
    public function searchMessages(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:3|max:100',
            'channel_type' => 'nullable|in:global,alliance,private,trade,diplomacy',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->chatService->searchMessages(
                $request->query,
                $request->channel_type,
                $request->get('limit', 50)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to search messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get message statistics
     */
    public function getMessageStats(): JsonResponse
    {
        try {
            $stats = $this->chatService->getMessageStats();

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get message statistics: ' . $e->getMessage(),
            ], 500);
        }
    }
}