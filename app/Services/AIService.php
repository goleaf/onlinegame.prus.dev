<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use LaraUtilX\LLMProviders\Contracts\LLMProviderInterface;
use LaraUtilX\LLMProviders\Gemini\GeminiProvider;
use LaraUtilX\LLMProviders\OpenAI\OpenAIProvider;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;

class AIService
{
    protected ?LLMProviderInterface $provider = null;
    protected string $defaultProvider;
    protected array $providers = [];

    public function __construct()
    {
        $this->defaultProvider = config('ai.default_provider', 'openai');
        $this->initializeProviders();
    }

    /**
     * Initialize AI providers
     */
    protected function initializeProviders()
    {
        // OpenAI Provider
        if (config('ai.openai.api_key')) {
            $this->providers['openai'] = new OpenAIProvider(
                apiKey: config('ai.openai.api_key'),
                maxRetries: config('ai.openai.max_retries', 3),
                retryDelay: config('ai.openai.retry_delay', 2)
            );
        }

        // Gemini Provider
        if (config('ai.gemini.api_key')) {
            $this->providers['gemini'] = new GeminiProvider(
                apiKey: config('ai.gemini.api_key'),
                maxRetries: config('ai.gemini.max_retries', 3),
                retryDelay: config('ai.gemini.retry_delay', 2)
            );
        }

        // Set default provider
        if (isset($this->providers[$this->defaultProvider])) {
            $this->provider = $this->providers[$this->defaultProvider];
        }
    }

    /**
     * Generate AI response for game content
     */
    public function generateGameContent(string $prompt, array $options = [])
    {
        $startTime = microtime(true);
        
        ds('AIService: Game content generation started', [
            'service' => 'AIService',
            'method' => 'generateGameContent',
            'prompt_length' => strlen($prompt),
            'options' => $options,
            'default_provider' => $this->defaultProvider,
            'generation_time' => now()
        ]);
        
        $cacheKey = 'ai_game_content_' . md5($prompt . serialize($options));

        return CachingUtil::get($cacheKey, function () use ($prompt, $options, $startTime) {
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are an AI assistant for a medieval strategy game. Generate creative, engaging content that fits the game world.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ];

            $response = $this->provider->generateResponse(
                modelName: $options['model'] ?? config('ai.default_model', 'gpt-3.5-turbo'),
                messages: $messages,
                temperature: $options['temperature'] ?? 0.7,
                maxTokens: $options['max_tokens'] ?? 500,
                jsonMode: $options['json_mode'] ?? false
            );

            LoggingUtil::info('AI game content generated', [
                'prompt' => $prompt,
                'model' => $options['model'] ?? config('ai.default_model'),
                'provider' => $this->defaultProvider,
                'response_length' => strlen($response->content),
            ], 'ai_service');

            return $response->content;
        }, config('ai.cache_duration', 3600));  // Cache for 1 hour
    }

    /**
     * Generate village names
     */
    public function generateVillageNames(int $count = 5, string $tribe = 'roman')
    {
        $prompt = "Generate {$count} creative village names for a {$tribe} tribe in a medieval strategy game. Return only the names, one per line.";

        $response = $this->generateGameContent($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 200
        ]);

        return array_filter(array_map('trim', explode("\n", $response)));
    }

    /**
     * Generate alliance names
     */
    public function generateAllianceNames(int $count = 5)
    {
        $prompt = "Generate {$count} creative alliance names for a medieval strategy game. Names should sound powerful and medieval. Return only the names, one per line.";

        $response = $this->generateGameContent($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 200
        ]);

        return array_filter(array_map('trim', explode("\n", $response)));
    }

    /**
     * Generate quest descriptions
     */
    public function generateQuestDescription(string $questType, array $context = [])
    {
        $contextStr = !empty($context) ? 'Context: ' . implode(', ', $context) : '';
        $prompt = "Generate a detailed quest description for a '{$questType}' quest in a medieval strategy game. {$contextStr} Make it engaging and immersive.";

        return $this->generateGameContent($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 300
        ]);
    }

    /**
     * Generate battle reports
     */
    public function generateBattleReport(array $battleData)
    {
        $prompt = "Generate a dramatic battle report for a medieval strategy game battle. 
        Attacker: {$battleData['attacker']} with {$battleData['attacker_troops']} troops
        Defender: {$battleData['defender']} with {$battleData['defender_troops']} troops
        Result: {$battleData['result']}
        Make it epic and engaging.";

        return $this->generateGameContent($prompt, [
            'temperature' => 0.8,
            'max_tokens' => 400
        ]);
    }

    /**
     * Generate player messages
     */
    public function generatePlayerMessage(string $messageType, array $context = [])
    {
        $contextStr = !empty($context) ? 'Context: ' . implode(', ', $context) : '';
        $prompt = "Generate a {$messageType} message for a medieval strategy game player. {$contextStr} Make it appropriate for the game world.";

        return $this->generateGameContent($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 200
        ]);
    }

    /**
     * Generate world events
     */
    public function generateWorldEvent(string $eventType, array $worldData = [])
    {
        $worldStr = !empty($worldData) ? 'World info: ' . implode(', ', $worldData) : '';
        $prompt = "Generate a world event description for a '{$eventType}' event in a medieval strategy game. {$worldStr} Make it interesting and impactful.";

        return $this->generateGameContent($prompt, [
            'temperature' => 0.7,
            'max_tokens' => 350
        ]);
    }

    /**
     * Generate AI strategy suggestions
     */
    public function generateStrategySuggestion(array $gameState)
    {
        $stateStr = 'Current game state: ' . implode(', ', array_map(function ($key, $value) {
            return "{$key}: {$value}";
        }, array_keys($gameState), $gameState));

        $prompt = "As an AI strategist for a medieval strategy game, provide strategic advice based on the current game state. {$stateStr} Give practical, actionable suggestions.";

        return $this->generateGameContent($prompt, [
            'temperature' => 0.5,
            'max_tokens' => 400
        ]);
    }

    /**
     * Generate content with specific provider
     */
    public function generateWithProvider(string $provider, string $prompt, array $options = [])
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Provider '{$provider}' not available");
        }

        $messages = [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];

        $response = $this->providers[$provider]->generateResponse(
            modelName: $options['model'] ?? config('ai.default_model'),
            messages: $messages,
            temperature: $options['temperature'] ?? 0.7,
            maxTokens: $options['max_tokens'] ?? 500,
            jsonMode: $options['json_mode'] ?? false
        );

        LoggingUtil::info('AI content generated with specific provider', [
            'provider' => $provider,
            'prompt' => $prompt,
            'model' => $options['model'] ?? config('ai.default_model'),
            'response_length' => strlen($response->content),
        ], 'ai_service');

        return $response->content;
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Get current provider
     */
    public function getCurrentProvider(): string
    {
        return $this->defaultProvider;
    }

    /**
     * Set provider
     */
    public function setProvider(string $provider): void
    {
        if (!isset($this->providers[$provider])) {
            throw new \InvalidArgumentException("Provider '{$provider}' not available");
        }

        $this->defaultProvider = $provider;
        $this->provider = $this->providers[$provider];
    }

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool
    {
        return $this->provider !== null;
    }

    /**
     * Get AI service status
     */
    public function getStatus(): array
    {
        return [
            'available' => $this->isAvailable(),
            'current_provider' => $this->defaultProvider,
            'available_providers' => $this->getAvailableProviders(),
            'providers_count' => count($this->providers),
        ];
    }
}
