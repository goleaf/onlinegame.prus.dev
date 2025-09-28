<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\Message;
use App\Models\Game\Player;
use App\Services\MessageService;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;

class MessageController extends CrudController
{
    use ApiResponseTrait;
    use ValidationHelperTrait;

    protected Model $model;

    protected MessageService $messageService;

    protected RateLimiterUtil $rateLimiter;

    protected array $validationRules = [];

    protected array $searchableFields = ['subject', 'body'];

    protected array $relationships = ['sender', 'recipient', 'alliance'];

    protected int $perPage = 20;

    protected function getValidationRules(): array
    {
        return [
            'recipient_id' => 'required|exists:players,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|in:low,normal,high,urgent',
        ];
    }

    public function __construct(MessageService $messageService, RateLimiterUtil $rateLimiter)
    {
        $this->model = new Message();
        $this->messageService = $messageService;
        $this->rateLimiter = $rateLimiter;
        $this->validationRules = $this->getValidationRules();
        parent::__construct($this->model);
    }

    /**
     * Get inbox messages
     */
    public function getInbox(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $cacheKey = "inbox_messages_{$playerId}_".md5(serialize($request->all()));

            $result = CachingUtil::remember($cacheKey, now()->addMinutes(5), function () use ($request, $playerId) {
                $limit = $request->get('limit', 50);
                $offset = $request->get('offset', 0);

                return $this->messageService->getInbox($playerId, $limit, $offset);
            });

            LoggingUtil::info('Inbox messages retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'limit' => $request->get('limit', 50),
            ], 'message_system');

            return $this->successResponse($result, 'Inbox messages retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving inbox messages', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to retrieve inbox messages.', 500);
        }
    }

    /**
     * Get sent messages
     */
    public function getSent(Request $request): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $cacheKey = "sent_messages_{$playerId}_".md5(serialize($request->all()));

            $result = CachingUtil::remember($cacheKey, now()->addMinutes(5), function () use ($request, $playerId) {
                $limit = $request->get('limit', 50);
                $offset = $request->get('offset', 0);

                return $this->messageService->getSentMessages($playerId, $limit, $offset);
            });

            LoggingUtil::info('Sent messages retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'limit' => $request->get('limit', 50),
            ], 'message_system');

            return $this->successResponse($result, 'Sent messages retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving sent messages', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to retrieve sent messages.', 500);
        }
    }

    /**
     * Get conversation between two players
     */
    public function getConversation(Request $request, int $otherPlayerId): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $cacheKey = "conversation_{$playerId}_{$otherPlayerId}_".md5(serialize($request->all()));

            $result = CachingUtil::remember($cacheKey, now()->addMinutes(2), function () use ($request, $playerId, $otherPlayerId) {
                $limit = $request->get('limit', 50);

                return $this->messageService->getConversation($playerId, $otherPlayerId, $limit);
            });

            LoggingUtil::info('Conversation retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'other_player_id' => $otherPlayerId,
                'limit' => $request->get('limit', 50),
            ], 'message_system');

            return $this->successResponse($result, 'Conversation retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving conversation', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'other_player_id' => $otherPlayerId,
            ], 'message_system');

            return $this->errorResponse('Failed to retrieve conversation.', 500);
        }
    }

    /**
     * Get alliance messages
     */
    public function getAllianceMessages(Request $request): JsonResponse
    {
        try {
            $player = Auth::user()->player;

            if (! $player->alliance_id) {
                return $this->errorResponse('Player is not in an alliance', 400);
            }

            $cacheKey = "alliance_messages_{$player->alliance_id}_".md5(serialize($request->all()));

            $result = CachingUtil::remember($cacheKey, now()->addMinutes(3), function () use ($request, $player) {
                $limit = $request->get('limit', 50);
                $offset = $request->get('offset', 0);

                return $this->messageService->getAllianceMessages($player->alliance_id, $limit, $offset);
            });

            LoggingUtil::info('Alliance messages retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $player->id,
                'alliance_id' => $player->alliance_id,
                'limit' => $request->get('limit', 50),
            ], 'message_system');

            return $this->successResponse($result, 'Alliance messages retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving alliance messages', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to retrieve alliance messages.', 500);
        }
    }

    /**
     * Send a private message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            // Rate limiting for sending messages
            $rateLimitKey = 'send_message_'.(auth()->id() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 20, 1)) {
                return $this->errorResponse('Too many messages sent. Please try again later.', 429);
            }

            $validated = $this->validateRequestData($request, $this->validationRules);

            $message = $this->messageService->sendPrivateMessage(
                Auth::user()->player->id,
                $validated['recipient_id'],
                $validated['subject'],
                $validated['body'],
                $validated['priority']
            );

            // Clear related caches
            CachingUtil::forget("inbox_messages_{$validated['recipient_id']}");
            CachingUtil::forget("sent_messages_{$message->sender_id}");

            LoggingUtil::info('Private message sent', [
                'user_id' => auth()->id(),
                'sender_id' => $message->sender_id,
                'recipient_id' => $message->recipient_id,
                'subject' => $message->subject,
                'priority' => $message->priority,
            ], 'message_system');

            return $this->successResponse($message, 'Message sent successfully.', 201);
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending private message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to send message: '.$e->getMessage(), 500);
        }
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(Request $request): JsonResponse
    {
        try {
            $player = Auth::user()->player;

            if (! $player->alliance_id) {
                return $this->errorResponse('Player is not in an alliance', 400);
            }

            // Rate limiting for sending alliance messages
            $rateLimitKey = 'send_alliance_message_'.(auth()->id() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
                return $this->errorResponse('Too many alliance messages sent. Please try again later.', 429);
            }

            $validated = $this->validateRequestData($request, [
                'subject' => 'required|string|max:255',
                'body' => 'required|string|max:5000',
                'priority' => 'required|in:low,normal,high,urgent',
            ]);

            $message = $this->messageService->sendAllianceMessage(
                $player->id,
                $player->alliance_id,
                $validated['subject'],
                $validated['body'],
                $validated['priority']
            );

            // Clear related caches
            CachingUtil::forget("alliance_messages_{$player->alliance_id}");

            LoggingUtil::info('Alliance message sent', [
                'user_id' => auth()->id(),
                'player_id' => $player->id,
                'alliance_id' => $player->alliance_id,
                'subject' => $message->subject,
                'priority' => $message->priority,
            ], 'message_system');

            return $this->successResponse($message, 'Alliance message sent successfully.', 201);
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending alliance message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to send alliance message: '.$e->getMessage(), 500);
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
                // Clear related caches
                CachingUtil::forget("inbox_messages_{$messageId}");

                LoggingUtil::info('Message marked as read', [
                    'user_id' => auth()->id(),
                    'message_id' => $messageId,
                ], 'message_system');

                return $this->successResponse(null, 'Message marked as read.');
            } else {
                return $this->errorResponse('Message not found or access denied', 404);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error marking message as read', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'message_id' => $messageId,
            ], 'message_system');

            return $this->errorResponse('Failed to mark message as read: '.$e->getMessage(), 500);
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
                // Clear related caches
                CachingUtil::forget("inbox_messages_{$messageId}");
                CachingUtil::forget("sent_messages_{$messageId}");

                LoggingUtil::info('Message deleted', [
                    'user_id' => auth()->id(),
                    'message_id' => $messageId,
                ], 'message_system');

                return $this->successResponse(null, 'Message deleted successfully.');
            } else {
                return $this->errorResponse('Message not found or access denied', 404);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error deleting message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'message_id' => $messageId,
            ], 'message_system');

            return $this->errorResponse('Failed to delete message: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get message statistics
     */
    public function getStats(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $cacheKey = "message_stats_{$playerId}";

            $stats = CachingUtil::remember($cacheKey, now()->addMinutes(10), function () use ($playerId) {
                return $this->messageService->getMessageStats($playerId);
            });

            LoggingUtil::info('Message statistics retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
            ], 'message_system');

            return $this->successResponse($stats, 'Message statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving message statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to get message statistics: '.$e->getMessage(), 500);
        }
    }

    /**
     * Bulk mark messages as read
     */
    public function bulkMarkAsRead(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequestData($request, [
                'message_ids' => 'required|array',
                'message_ids.*' => 'integer|exists:messages,id',
            ]);

            $updated = $this->messageService->bulkMarkAsRead(
                $validated['message_ids'],
                Auth::user()->player->id
            );

            // Clear related caches
            foreach ($validated['message_ids'] as $messageId) {
                CachingUtil::forget("inbox_messages_{$messageId}");
            }

            LoggingUtil::info('Bulk mark messages as read', [
                'user_id' => auth()->id(),
                'message_ids' => $validated['message_ids'],
                'updated_count' => $updated,
            ], 'message_system');

            return $this->successResponse([
                'updated_count' => $updated,
            ], "{$updated} messages marked as read.");
        } catch (\Exception $e) {
            LoggingUtil::error('Error bulk marking messages as read', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to bulk mark messages as read: '.$e->getMessage(), 500);
        }
    }

    /**
     * Bulk delete messages
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequestData($request, [
                'message_ids' => 'required|array',
                'message_ids.*' => 'integer|exists:messages,id',
            ]);

            $deleted = $this->messageService->bulkDeleteMessages(
                $validated['message_ids'],
                Auth::user()->player->id
            );

            // Clear related caches
            foreach ($validated['message_ids'] as $messageId) {
                CachingUtil::forget("inbox_messages_{$messageId}");
                CachingUtil::forget("sent_messages_{$messageId}");
            }

            LoggingUtil::info('Bulk delete messages', [
                'user_id' => auth()->id(),
                'message_ids' => $validated['message_ids'],
                'deleted_count' => $deleted,
            ], 'message_system');

            return $this->successResponse([
                'deleted_count' => $deleted,
            ], "{$deleted} messages deleted.");
        } catch (\Exception $e) {
            LoggingUtil::error('Error bulk deleting messages', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to bulk delete messages: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get players for message composition
     */
    public function getPlayers(): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $cacheKey = "message_players_{$playerId}";

            $players = CachingUtil::remember($cacheKey, now()->addMinutes(15), function () use ($playerId) {
                return Player::where('id', '!=', $playerId)
                    ->select('id', 'name')
                    ->orderBy('name')
                    ->get();
            });

            LoggingUtil::info('Players for message composition retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'players_count' => $players->count(),
            ], 'message_system');

            return $this->successResponse($players, 'Players retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving players for message composition', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'message_system');

            return $this->errorResponse('Failed to get players: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get a specific message
     */
    public function getMessage(int $messageId): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            $cacheKey = "message_{$messageId}_{$playerId}";

            $message = CachingUtil::remember($cacheKey, now()->addMinutes(5), function () use ($messageId, $playerId) {
                return Message::with(['sender', 'recipient', 'alliance'])
                    ->where('id', $messageId)
                    ->where(function ($q) use ($playerId): void {
                        $q
                            ->where('sender_id', $playerId)
                            ->orWhere('recipient_id', $playerId);
                    })
                    ->first();
            });

            if (! $message) {
                return $this->errorResponse('Message not found or access denied', 404);
            }

            // Mark as read if recipient
            if ($message->recipient_id === $playerId) {
                $this->messageService->markAsRead($messageId, $playerId);
                // Clear cache after marking as read
                CachingUtil::forget($cacheKey);
            }

            LoggingUtil::info('Message retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'message_id' => $messageId,
            ], 'message_system');

            return $this->successResponse($message, 'Message retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'message_id' => $messageId,
            ], 'message_system');

            return $this->errorResponse('Failed to get message: '.$e->getMessage(), 500);
        }
    }
}
