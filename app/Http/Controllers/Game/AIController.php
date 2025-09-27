<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;

class AIController extends CrudController
{
    use ApiResponseTrait, GameValidationTrait;

    protected Model $model;
    protected AIService $aiService;
    protected RateLimiterUtil $rateLimiter;

    protected array $validationRules = [
        'prompt' => 'required|string|max:1000',
        'model' => 'nullable|string|in:gpt-3.5-turbo,gpt-4,gemini-pro',
        'temperature' => 'nullable|numeric|min:0|max:2',
        'max_tokens' => 'nullable|integer|min:1|max:4000',
    ];

    protected array $searchableFields = ['prompt'];
    protected array $relationships = [];
    protected int $perPage = 10;

    public function __construct(AIService $aiService, RateLimiterUtil $rateLimiter)
    {
        $this->aiService = $aiService;
        $this->rateLimiter = $rateLimiter;
        // AI Controller doesn't have a specific model, so we'll use a generic approach
        parent::__construct();
    }

    /**
     * Get AI service status
     */
    public function getStatus()
    {
        try {
            $status = $this->aiService->getStatus();

            LoggingUtil::info('AI service status retrieved', [
                'user_id' => auth()->id(),
                'available' => $status['available'],
                'current_provider' => $status['current_provider'],
                'providers_count' => $status['providers_count'],
            ], 'ai_service');

            return $this->successResponse($status, 'AI service status retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving AI service status', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            return $this->errorResponse('Failed to retrieve AI service status.', 500);
        }
    }

    /**
     * Generate village names
     */
    public function generateVillageNames(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_village_names_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'count' => 'integer|min:1|max:10',
                'tribe' => 'string|in:roman,teuton,gaul',
            ]);

            $count = $validated['count'] ?? 5;
            $tribe = $validated['tribe'] ?? 'roman';

            $names = $this->aiService->generateVillageNames($count, $tribe);

            LoggingUtil::info('Village names generated', [
                'user_id' => auth()->id(),
                'count' => $count,
                'tribe' => $tribe,
                'names_generated' => count($names),
            ], 'ai_service');

            return $this->successResponse([
                'names' => $names,
                'count' => count($names),
                'tribe' => $tribe,
            ], 'Village names generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating village names', [
                'error' => $e->getMessage(),
                'count' => $request->get('count'),
                'tribe' => $request->get('tribe'),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate village names.', 500);
        }
    }

    /**
     * Generate alliance names
     */
    public function generateAllianceNames(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_alliance_names_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'count' => 'integer|min:1|max:10',
            ]);

            $count = $validated['count'] ?? 5;
            $names = $this->aiService->generateAllianceNames($count);

            LoggingUtil::info('Alliance names generated', [
                'user_id' => auth()->id(),
                'count' => $count,
                'names_generated' => count($names),
            ], 'ai_service');

            return $this->successResponse([
                'names' => $names,
                'count' => count($names),
            ], 'Alliance names generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating alliance names', [
                'error' => $e->getMessage(),
                'count' => $request->get('count'),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate alliance names.', 500);
        }
    }

    /**
     * Generate quest description
     */
    public function generateQuestDescription(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_quest_description_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 5, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'quest_type' => 'required|string|max:255',
                'context' => 'array',
                'context.*' => 'string|max:255',
            ]);

            $description = $this->aiService->generateQuestDescription(
                $validated['quest_type'],
                $validated['context'] ?? []
            );

            LoggingUtil::info('Quest description generated', [
                'user_id' => auth()->id(),
                'quest_type' => $validated['quest_type'],
                'context' => $validated['context'] ?? [],
                'description_length' => strlen($description),
            ], 'ai_service');

            return $this->successResponse([
                'description' => $description,
                'quest_type' => $validated['quest_type'],
                'context' => $validated['context'] ?? [],
            ], 'Quest description generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating quest description', [
                'error' => $e->getMessage(),
                'quest_type' => $request->get('quest_type'),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate quest description.', 500);
        }
    }

    /**
     * Generate battle report
     */
    public function generateBattleReport(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_battle_report_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 5, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'attacker' => 'required|string|max:255',
                'defender' => 'required|string|max:255',
                'attacker_troops' => 'required|integer|min:0',
                'defender_troops' => 'required|integer|min:0',
                'result' => 'required|string|in:victory,defeat,draw',
            ]);

            $battleData = [
                'attacker' => $validated['attacker'],
                'defender' => $validated['defender'],
                'attacker_troops' => $validated['attacker_troops'],
                'defender_troops' => $validated['defender_troops'],
                'result' => $validated['result'],
            ];

            $report = $this->aiService->generateBattleReport($battleData);

            LoggingUtil::info('Battle report generated', [
                'user_id' => auth()->id(),
                'attacker' => $validated['attacker'],
                'defender' => $validated['defender'],
                'result' => $validated['result'],
                'report_length' => strlen($report),
            ], 'ai_service');

            return $this->successResponse([
                'report' => $report,
                'battle_data' => $battleData,
            ], 'Battle report generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating battle report', [
                'error' => $e->getMessage(),
                'attacker' => $request->get('attacker'),
                'defender' => $request->get('defender'),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate battle report.', 500);
        }
    }

    /**
     * Generate player message
     */
    public function generatePlayerMessage(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_player_message_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 10, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'message_type' => 'required|string|max:255',
                'context' => 'array',
                'context.*' => 'string|max:255',
            ]);

            $message = $this->aiService->generatePlayerMessage(
                $validated['message_type'],
                $validated['context'] ?? []
            );

            LoggingUtil::info('Player message generated', [
                'user_id' => auth()->id(),
                'message_type' => $validated['message_type'],
                'context' => $validated['context'] ?? [],
                'message_length' => strlen($message),
            ], 'ai_service');

            return $this->successResponse([
                'message' => $message,
                'message_type' => $validated['message_type'],
                'context' => $validated['context'] ?? [],
            ], 'Player message generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating player message', [
                'error' => $e->getMessage(),
                'message_type' => $request->get('message_type'),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate player message.', 500);
        }
    }

    /**
     * Generate world event
     */
    public function generateWorldEvent(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_world_event_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 5, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'event_type' => 'required|string|max:255',
                'world_data' => 'array',
                'world_data.*' => 'string|max:255',
            ]);

            $event = $this->aiService->generateWorldEvent(
                $validated['event_type'],
                $validated['world_data'] ?? []
            );

            LoggingUtil::info('World event generated', [
                'user_id' => auth()->id(),
                'event_type' => $validated['event_type'],
                'world_data' => $validated['world_data'] ?? [],
                'event_length' => strlen($event),
            ], 'ai_service');

            return $this->successResponse([
                'event' => $event,
                'event_type' => $validated['event_type'],
                'world_data' => $validated['world_data'] ?? [],
            ], 'World event generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating world event', [
                'error' => $e->getMessage(),
                'event_type' => $request->get('event_type'),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate world event.', 500);
        }
    }

    /**
     * Generate strategy suggestion
     */
    public function generateStrategySuggestion(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_strategy_suggestion_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 3, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'game_state' => 'required|array',
                'game_state.*' => 'string|max:255',
            ]);

            $suggestion = $this->aiService->generateStrategySuggestion($validated['game_state']);

            LoggingUtil::info('Strategy suggestion generated', [
                'user_id' => auth()->id(),
                'game_state_keys' => array_keys($validated['game_state']),
                'suggestion_length' => strlen($suggestion),
            ], 'ai_service');

            return $this->successResponse([
                'suggestion' => $suggestion,
                'game_state' => $validated['game_state'],
            ], 'Strategy suggestion generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating strategy suggestion', [
                'error' => $e->getMessage(),
                'game_state_keys' => array_keys($request->get('game_state', [])),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate strategy suggestion.', 500);
        }
    }

    /**
     * Generate custom content
     */
    public function generateCustomContent(Request $request)
    {
        // Rate limiting
        $rateLimitKey = 'ai_custom_content_' . ($request->ip() ?? 'unknown');
        if (!$this->rateLimiter->attempt($rateLimitKey, 5, 1)) {
            return $this->errorResponse('Too many requests. Please try again later.', 429);
        }

        try {
            $validated = $request->validate([
                'prompt' => 'required|string|max:1000',
                'provider' => 'string|in:openai,gemini',
                'model' => 'string|max:255',
                'temperature' => 'numeric|min:0|max:2',
                'max_tokens' => 'integer|min:1|max:2000',
                'json_mode' => 'boolean',
            ]);

            $options = [
                'model' => $validated['model'] ?? null,
                'temperature' => $validated['temperature'] ?? null,
                'max_tokens' => $validated['max_tokens'] ?? null,
                'json_mode' => $validated['json_mode'] ?? false,
            ];

            if (isset($validated['provider'])) {
                $content = $this->aiService->generateWithProvider(
                    $validated['provider'],
                    $validated['prompt'],
                    $options
                );
            } else {
                $content = $this->aiService->generateGameContent($validated['prompt'], $options);
            }

            LoggingUtil::info('Custom content generated', [
                'user_id' => auth()->id(),
                'provider' => $validated['provider'] ?? 'default',
                'model' => $options['model'] ?? 'default',
                'prompt_length' => strlen($validated['prompt']),
                'content_length' => strlen($content),
            ], 'ai_service');

            return $this->successResponse([
                'content' => $content,
                'prompt' => $validated['prompt'],
                'options' => $options,
            ], 'Custom content generated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error generating custom content', [
                'error' => $e->getMessage(),
                'prompt_length' => strlen($request->get('prompt', '')),
            ], 'ai_service');

            return $this->errorResponse('Failed to generate custom content.', 500);
        }
    }

    /**
     * Switch AI provider
     */
    public function switchProvider(Request $request)
    {
        try {
            $validated = $request->validate([
                'provider' => 'required|string|in:openai,gemini',
            ]);

            $this->aiService->setProvider($validated['provider']);

            LoggingUtil::info('AI provider switched', [
                'user_id' => auth()->id(),
                'new_provider' => $validated['provider'],
            ], 'ai_service');

            return $this->successResponse([
                'provider' => $validated['provider'],
                'status' => $this->aiService->getStatus(),
            ], 'AI provider switched successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error switching AI provider', [
                'error' => $e->getMessage(),
                'provider' => $request->get('provider'),
            ], 'ai_service');

            return $this->errorResponse('Failed to switch AI provider.', 500);
        }
    }
}
