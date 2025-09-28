<?php

namespace App\Http\Controllers\Game;

use App\Models\Game\ChatMessage;
use App\Services\ChatService;
use App\Traits\GameValidationTrait;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use JonPurvis\Squeaky\Rules\Clean;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\FileProcessingTrait;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;

class ChatController extends CrudController
{
    use ApiResponseTrait;
    use FileProcessingTrait;
    use GameValidationTrait;
    use ValidationHelperTrait;

    protected Model $model;

    protected $chatService;

    protected RateLimiterUtil $rateLimiter;

    protected array $validationRules = [];

    protected array $searchableFields = ['message'];

    protected array $relationships = ['player', 'channel'];

    protected int $perPage = 50;

    protected function getValidationRules(): array
    {
        return [
            'channel_id' => 'nullable|exists:chat_channels,id',
            'channel_type' => 'required|in:global,alliance,private,trade,diplomacy',
            'message' => ['required', 'string', 'max:1000', new Clean()],
            'message_type' => 'required|in:text,system,announcement,emote,command',
            'recipient_id' => 'nullable|exists:players,id',
        ];
    }

    public function __construct(ChatMessage $chatMessage, RateLimiterUtil $rateLimiter)
    {
        $this->model = $chatMessage;
        $this->rateLimiter = $rateLimiter;
        $this->chatService = new ChatService();
        $this->validationRules = $this->getValidationRules();
        parent::__construct($this->model);
    }

    /**
     * Get messages for a channel
     */
    public function getChannelMessages(Request $request, int $channelId): JsonResponse
    {
        try {
            // Rate limiting for channel messages
            $rateLimitKey = 'channel_messages_'.($request->ip() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 100, 1)) {
                return $this->errorResponse('Too many requests. Please try again later.', 429);
            }

            $cacheKey = "channel_messages_{$channelId}_".md5(serialize($request->all()));

            $result = CachingUtil::remember($cacheKey, now()->addMinutes(2), function () use ($request, $channelId) {
                $limit = $request->get('limit', $this->perPage);
                $offset = $request->get('offset', 0);

                return $this->chatService->getChannelMessages($channelId, $limit, $offset);
            });

            LoggingUtil::info('Channel messages retrieved', [
                'user_id' => auth()->id(),
                'channel_id' => $channelId,
                'limit' => $request->get('limit', $this->perPage),
            ], 'chat_system');

            return $this->successResponse($result, 'Channel messages retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving channel messages', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId,
            ], 'chat_system');

            return $this->errorResponse('Failed to retrieve channel messages.', 500);
        }
    }

    /**
     * Get messages by channel type
     */
    public function getMessagesByType(Request $request, string $channelType): JsonResponse
    {
        try {
            $cacheKey = "messages_by_type_{$channelType}_".md5(serialize($request->all()));

            $result = CachingUtil::remember($cacheKey, now()->addMinutes(3), function () use ($request, $channelType) {
                $limit = $request->get('limit', $this->perPage);
                $offset = $request->get('offset', 0);

                return $this->chatService->getMessagesByType($channelType, $limit, $offset);
            });

            LoggingUtil::info('Messages by type retrieved', [
                'user_id' => auth()->id(),
                'channel_type' => $channelType,
                'limit' => $request->get('limit', $this->perPage),
            ], 'chat_system');

            return $this->successResponse($result, 'Messages by type retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving messages by type', [
                'error' => $e->getMessage(),
                'channel_type' => $channelType,
            ], 'chat_system');

            return $this->errorResponse('Failed to retrieve messages by type.', 500);
        }
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'channel_id' => 'nullable|exists:chat_channels,id',
            'channel_type' => 'required|in:global,alliance,private,trade,diplomacy',
            'message' => ['required', 'string', 'max:1000', new Clean()],
            'message_type' => 'required|in:text,system,announcement,emote,command',
        ]);

        try {
            $message = $this->chatService->sendMessage(
                Auth::user()->player->id,
                $validated['channel_id'],
                $validated['channel_type'],
                $validated['message'],
                $validated['message_type']
            );

            LoggingUtil::info('Chat message sent', [
                'user_id' => auth()->id(),
                'channel_type' => $validated['channel_type'],
                'message_type' => $validated['message_type'],
            ], 'chat_system');

            return $this->successResponse($message, 'Message sent successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Failed to send chat message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to send message: '.$e->getMessage(), 500);
        }
    }

    /**
     * Send a global message
     */
    public function sendGlobalMessage(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'message' => ['required', 'string', 'max:1000', new Clean()],
            'message_type' => 'required|in:text,system,announcement,emote,command',
        ]);

        try {
            $message = $this->chatService->sendGlobalMessage(
                Auth::user()->player->id,
                $validated['message'],
                $validated['message_type']
            );

            LoggingUtil::info('Global chat message sent', [
                'user_id' => auth()->id(),
                'message_type' => $validated['message_type'],
            ], 'chat_system');

            return $this->successResponse($message, 'Global message sent successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Failed to send global chat message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to send global message: '.$e->getMessage(), 500);
        }
    }

    /**
     * Send an alliance message
     */
    public function sendAllianceMessage(Request $request): JsonResponse
    {
        try {
            // Rate limiting for alliance messages
            $rateLimitKey = 'alliance_message_'.(auth()->id() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 20, 1)) {
                return $this->errorResponse('Too many requests. Please try again later.', 429);
            }

            $player = Auth::user()->player;

            if (! $player->alliance_id) {
                return $this->errorResponse('Player is not in an alliance', 400);
            }

            $validated = $this->validateRequestData($request, [
                'message' => ['required', 'string', 'max:1000', new Clean()],
                'message_type' => 'required|in:text,system,announcement,emote,command',
            ]);

            $message = $this->chatService->sendAllianceMessage(
                $player->id,
                $player->alliance_id,
                $validated['message'],
                $validated['message_type']
            );

            // Clear related caches
            CachingUtil::forget('messages_by_type_alliance');
            CachingUtil::forget("channel_messages_{$player->alliance_id}");

            LoggingUtil::info('Alliance message sent', [
                'user_id' => auth()->id(),
                'alliance_id' => $player->alliance_id,
                'message_type' => $validated['message_type'],
            ], 'chat_system');

            return $this->successResponse($message, 'Alliance message sent successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending alliance message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to send alliance message.', 500);
        }
    }

    /**
     * Send a private message
     */
    public function sendPrivateMessage(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequestData($request, [
                'recipient_id' => 'required|exists:players,id',
                'message' => ['required', 'string', 'max:1000', new Clean()],
            ]);

            $message = $this->chatService->sendPrivateMessage(
                Auth::user()->player->id,
                $validated['recipient_id'],
                $validated['message']
            );

            // Clear related caches
            CachingUtil::forget('messages_by_type_private');

            LoggingUtil::info('Private message sent', [
                'user_id' => auth()->id(),
                'recipient_id' => $validated['recipient_id'],
            ], 'chat_system');

            return $this->successResponse($message, 'Private message sent successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending private message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to send private message.', 500);
        }
    }

    /**
     * Send a trade message
     */
    public function sendTradeMessage(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequestData($request, [
                'message' => 'required|string|max:1000',
            ]);

            $message = $this->chatService->sendTradeMessage(
                Auth::user()->player->id,
                $validated['message']
            );

            // Clear related caches
            CachingUtil::forget('messages_by_type_trade');

            LoggingUtil::info('Trade message sent', [
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->successResponse($message, 'Trade message sent successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending trade message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to send trade message.', 500);
        }
    }

    /**
     * Send a diplomacy message
     */
    public function sendDiplomacyMessage(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequestData($request, [
                'message' => 'required|string|max:1000',
            ]);

            $message = $this->chatService->sendDiplomacyMessage(
                Auth::user()->player->id,
                $validated['message']
            );

            // Clear related caches
            CachingUtil::forget('messages_by_type_diplomacy');

            LoggingUtil::info('Diplomacy message sent', [
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->successResponse($message, 'Diplomacy message sent successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error sending diplomacy message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to send diplomacy message.', 500);
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
                // Clear related caches
                CachingUtil::forget("message_{$messageId}");

                LoggingUtil::info('Message deleted', [
                    'user_id' => auth()->id(),
                    'message_id' => $messageId,
                ], 'chat_system');

                return $this->successResponse(null, 'Message deleted successfully.');
            } else {
                return $this->errorResponse('Message not found or access denied', 404);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error deleting message', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'message_id' => $messageId,
            ], 'chat_system');

            return $this->errorResponse('Failed to delete message.', 500);
        }
    }

    /**
     * Get available channels
     */
    public function getAvailableChannels(): JsonResponse
    {
        try {
            $cacheKey = 'available_channels_'.auth()->id();

            $channels = CachingUtil::remember($cacheKey, now()->addMinutes(10), function () {
                return $this->chatService->getAvailableChannels(Auth::user()->player->id);
            });

            LoggingUtil::info('Available channels retrieved', [
                'user_id' => auth()->id(),
                'channels_count' => count($channels),
            ], 'chat_system');

            return $this->successResponse($channels, 'Available channels retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving available channels', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to get available channels.', 500);
        }
    }

    /**
     * Get channel statistics
     */
    public function getChannelStats(int $channelId): JsonResponse
    {
        try {
            $cacheKey = "channel_stats_{$channelId}";

            $stats = CachingUtil::remember($cacheKey, now()->addMinutes(15), function () use ($channelId) {
                return $this->chatService->getChannelStats($channelId);
            });

            LoggingUtil::info('Channel statistics retrieved', [
                'user_id' => auth()->id(),
                'channel_id' => $channelId,
            ], 'chat_system');

            return $this->successResponse($stats, 'Channel statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving channel statistics', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId,
            ], 'chat_system');

            return $this->errorResponse('Failed to get channel statistics.', 500);
        }
    }

    /**
     * Search messages
     */
    public function searchMessages(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateRequestData($request, [
                'query' => 'required|string|min:3|max:100',
                'channel_type' => 'nullable|in:global,alliance,private,trade,diplomacy',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $result = $this->chatService->searchMessages(
                $validated['query'],
                $validated['channel_type'] ?? null,
                $validated['limit'] ?? 50
            );

            LoggingUtil::info('Messages searched', [
                'user_id' => auth()->id(),
                'query' => $validated['query'],
                'channel_type' => $validated['channel_type'] ?? null,
                'results_count' => count($result),
            ], 'chat_system');

            return $this->successResponse($result, 'Messages searched successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error searching messages', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to search messages.', 500);
        }
    }

    /**
     * Get message statistics
     */
    public function getMessageStats(): JsonResponse
    {
        try {
            $cacheKey = 'message_statistics';

            $stats = CachingUtil::remember($cacheKey, now()->addMinutes(30), function () {
                return $this->chatService->getMessageStats();
            });

            LoggingUtil::info('Message statistics retrieved', [
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->successResponse($stats, 'Message statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving message statistics', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ], 'chat_system');

            return $this->errorResponse('Failed to get message statistics.', 500);
        }
    }
}
