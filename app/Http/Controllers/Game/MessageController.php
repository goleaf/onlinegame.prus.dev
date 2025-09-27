<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    protected $messageService;

    public function __construct()
    {
        $this->messageService = new MessageService();
    }

    /**
     * Get inbox messages
     */
    public function getInbox(Request $request): JsonResponse
    {
        $playerId = Auth::user()->player->id;
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $result = $this->messageService->getInbox($playerId, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get sent messages
     */
    public function getSent(Request $request): JsonResponse
    {
        $playerId = Auth::user()->player->id;
        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $result = $this->messageService->getSentMessages($playerId, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get conversation between two players
     */
    public function getConversation(Request $request, int $otherPlayerId): JsonResponse
    {
        $playerId = Auth::user()->player->id;
        $limit = $request->get('limit', 50);

        $result = $this->messageService->getConversation($playerId, $otherPlayerId, $limit);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(Request $request): JsonResponse
    {
        $player = Auth::user()->player;

        if (!$player->alliance_id) {
            return response()->json([
                'success' => false,
                'message' => 'Player is not in an alliance',
            ], 400);
        }

        $limit = $request->get('limit', 50);
        $offset = $request->get('offset', 0);

        $result = $this->messageService->getAllianceMessages($player->alliance_id, $limit, $offset);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Send a private message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_id' => 'required|exists:players,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->messageService->sendPrivateMessage(
                Auth::user()->player->id,
                $request->recipient_id,
                $request->subject,
                $request->body,
                $request->priority
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
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|in:low,normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->messageService->sendAllianceMessage(
                $player->id,
                $player->alliance_id,
                $request->subject,
                $request->body,
                $request->priority
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
     * Mark a message as read
     */
    public function markAsRead(int $messageId): JsonResponse
    {
        try {
            $success = $this->messageService->markAsRead($messageId, Auth::user()->player->id);

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message marked as read',
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
                'message' => 'Failed to mark message as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a message
     */
    public function deleteMessage(int $messageId): JsonResponse
    {
        try {
            $success = $this->messageService->deleteMessage($messageId, Auth::user()->player->id);

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
     * Get message statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = $this->messageService->getMessageStats(Auth::user()->player->id);

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

    /**
     * Bulk mark messages as read
     */
    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updated = $this->messageService->bulkMarkAsRead(
                $request->message_ids,
                Auth::user()->player->id
            );

            return response()->json([
                'success' => true,
                'message' => "{$updated} messages marked as read",
                'updated_count' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk mark messages as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk delete messages
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message_ids' => 'required|array',
            'message_ids.*' => 'integer|exists:messages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $deleted = $this->messageService->bulkDeleteMessages(
                $request->message_ids,
                Auth::user()->player->id
            );

            return response()->json([
                'success' => true,
                'message' => "{$deleted} messages deleted",
                'deleted_count' => $deleted,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk delete messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get players for message composition
     */
    public function getPlayers(): JsonResponse
    {
        try {
            $players = Player::where('id', '!=', Auth::user()->player->id)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $players,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get players: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific message
     */
    public function getMessage(int $messageId): JsonResponse
    {
        try {
            $message = Message::with(['sender', 'recipient', 'alliance'])
                ->where('id', $messageId)
                ->where(function ($q) {
                    $q
                        ->where('sender_id', Auth::user()->player->id)
                        ->orWhere('recipient_id', Auth::user()->player->id);
                })
                ->first();

            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message not found or access denied',
                ], 404);
            }

            // Mark as read if recipient
            if ($message->recipient_id === Auth::user()->player->id) {
                $this->messageService->markAsRead($messageId, Auth::user()->player->id);
            }

            return response()->json([
                'success' => true,
                'data' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get message: ' . $e->getMessage(),
            ], 500);
        }
    }
}
