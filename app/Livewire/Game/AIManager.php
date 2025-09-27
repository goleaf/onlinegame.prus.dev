<?php

namespace App\Livewire\Game;

use App\Services\AIService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;
use Livewire\Component;

class AIManager extends Component
{
    use ApiResponseTrait;

    public $aiStatus = [];
    public $isLoading = false;
    public $activeTab = 'status';
    public $generatedContent = [];
    public $customPrompt = '';
    public $selectedProvider = 'openai';
    public $selectedModel = 'gpt-3.5-turbo';
    public $temperature = 0.7;
    public $maxTokens = 500;
    public $jsonMode = false;
    public $villageCount = 5;
    public $selectedTribe = 'roman';
    public $allianceCount = 5;
    public $questType = '';
    public $questContext = '';
    public $messageType = '';
    public $messageContext = '';
    public $eventType = '';
    public $eventContext = '';
    public $strategyGameState = '';

    protected $listeners = [
        'contentGenerated' => 'handleContentGenerated',
        'providerSwitched' => 'handleProviderSwitched',
    ];

    public function mount()
    {
        $this->loadAIStatus();
    }

    public function loadAIStatus()
    {
        $this->isLoading = true;

        try {
            $aiService = app(AIService::class);
            $this->aiStatus = $aiService->getStatus();

        } catch (\Exception $e) {
            LoggingUtil::error('Error loading AI status', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error loading AI status: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateVillageNames()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('POST', '/game/api/ai/village-names', [
                'count' => $this->villageCount,
                'tribe' => $this->selectedTribe,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['village_names'] = $response['data'];
                $this->addNotification('Village names generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating village names', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating village names: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateAllianceNames()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('POST', '/game/api/ai/alliance-names', [
                'count' => $this->allianceCount,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['alliance_names'] = $response['data'];
                $this->addNotification('Alliance names generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating alliance names', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating alliance names: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateQuestDescription()
    {
        if (empty($this->questType)) {
            $this->addNotification('Please enter a quest type', 'error');
            return;
        }

        $this->isLoading = true;

        try {
            $context = !empty($this->questContext) ? explode(',', $this->questContext) : [];
            $context = array_map('trim', $context);

            $response = $this->makeApiRequest('POST', '/game/api/ai/quest-description', [
                'quest_type' => $this->questType,
                'context' => $context,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['quest_description'] = $response['data'];
                $this->addNotification('Quest description generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating quest description', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating quest description: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generatePlayerMessage()
    {
        if (empty($this->messageType)) {
            $this->addNotification('Please enter a message type', 'error');
            return;
        }

        $this->isLoading = true;

        try {
            $context = !empty($this->messageContext) ? explode(',', $this->messageContext) : [];
            $context = array_map('trim', $context);

            $response = $this->makeApiRequest('POST', '/game/api/ai/player-message', [
                'message_type' => $this->messageType,
                'context' => $context,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['player_message'] = $response['data'];
                $this->addNotification('Player message generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating player message', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating player message: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateWorldEvent()
    {
        if (empty($this->eventType)) {
            $this->addNotification('Please enter an event type', 'error');
            return;
        }

        $this->isLoading = true;

        try {
            $context = !empty($this->eventContext) ? explode(',', $this->eventContext) : [];
            $context = array_map('trim', $context);

            $response = $this->makeApiRequest('POST', '/game/api/ai/world-event', [
                'event_type' => $this->eventType,
                'world_data' => $context,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['world_event'] = $response['data'];
                $this->addNotification('World event generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating world event', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating world event: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateStrategySuggestion()
    {
        if (empty($this->strategyGameState)) {
            $this->addNotification('Please enter game state information', 'error');
            return;
        }

        $this->isLoading = true;

        try {
            $gameState = [];
            $lines = explode("\n", $this->strategyGameState);
            foreach ($lines as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $gameState[trim($key)] = trim($value);
                }
            }

            $response = $this->makeApiRequest('POST', '/game/api/ai/strategy-suggestion', [
                'game_state' => $gameState,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['strategy_suggestion'] = $response['data'];
                $this->addNotification('Strategy suggestion generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating strategy suggestion', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating strategy suggestion: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function generateCustomContent()
    {
        if (empty($this->customPrompt)) {
            $this->addNotification('Please enter a prompt', 'error');
            return;
        }

        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('POST', '/game/api/ai/custom-content', [
                'prompt' => $this->customPrompt,
                'provider' => $this->selectedProvider,
                'model' => $this->selectedModel,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
                'json_mode' => $this->jsonMode,
            ]);

            if ($response && isset($response['data'])) {
                $this->generatedContent['custom_content'] = $response['data'];
                $this->addNotification('Custom content generated successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error generating custom content', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error generating custom content: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function switchProvider()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('POST', '/game/api/ai/switch-provider', [
                'provider' => $this->selectedProvider,
            ]);

            if ($response && isset($response['data'])) {
                $this->aiStatus = $response['data']['status'];
                $this->addNotification('AI provider switched successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error switching AI provider', [
                'error' => $e->getMessage(),
            ], 'ai_service');

            $this->addNotification('Error switching AI provider: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function clearGeneratedContent()
    {
        $this->generatedContent = [];
        $this->addNotification('Generated content cleared', 'info');
    }

    public function handleContentGenerated($data)
    {
        $this->addNotification('Content generated successfully', 'success');
    }

    public function handleProviderSwitched($data)
    {
        $this->addNotification('AI provider switched successfully', 'success');
    }

    public function addNotification($message, $type = 'info')
    {
        $this->dispatch('notification', [
            'message' => $message,
            'type' => $type
        ]);
    }

    private function makeApiRequest($method, $url, $data = [])
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . auth()->user()->createToken('ai-management')->plainTextToken,
                'Accept' => 'application/json',
            ])->$method(url($url), $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('API request failed: ' . $response->body());

        } catch (\Exception $e) {
            LoggingUtil::error('API request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ], 'ai_service');

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.game.ai-manager', [
            'availableProviders' => $this->aiStatus['available_providers'] ?? [],
            'availableModels' => [
                'openai' => ['gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'],
                'gemini' => ['gemini-pro', 'gemini-pro-vision'],
            ],
            'tribes' => ['roman', 'teuton', 'gaul'],
        ]);
    }
}

