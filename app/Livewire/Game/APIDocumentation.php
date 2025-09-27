<?php

namespace App\Livewire\Game;

use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;
use Livewire\Component;

class APIDocumentation extends Component
{
    use ApiResponseTrait;

    public $documentation = [];
    public $isLoading = false;
    public $activeSection = 'overview';
    public $searchQuery = '';
    public $filteredEndpoints = [];

    protected $listeners = [
        'documentationLoaded' => 'handleDocumentationLoaded',
    ];

    public function mount()
    {
        $this->loadDocumentation();
    }

    public function loadDocumentation()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/docs/larautilx');

            if ($response && isset($response['data'])) {
                $this->documentation = $response['data'];
                $this->filteredEndpoints = $this->documentation['endpoints'] ?? [];
                $this->dispatch('documentationLoaded', ['data' => $this->documentation]);
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error loading API documentation', [
                'error' => $e->getMessage(),
            ], 'api_documentation');

            $this->addNotification('Error loading API documentation: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function setActiveSection($section)
    {
        $this->activeSection = $section;
    }

    public function updatedSearchQuery()
    {
        $this->filterEndpoints();
    }

    public function filterEndpoints()
    {
        if (empty($this->searchQuery)) {
            $this->filteredEndpoints = $this->documentation['endpoints'] ?? [];
            return;
        }

        $searchQuery = strtolower($this->searchQuery);
        $filtered = [];

        foreach ($this->documentation['endpoints'] ?? [] as $category => $endpoints) {
            $filteredCategory = [];
            foreach ($endpoints as $endpoint => $details) {
                if (str_contains(strtolower($endpoint), $searchQuery) ||
                    str_contains(strtolower($details['description'] ?? ''), $searchQuery)) {
                    $filteredCategory[$endpoint] = $details;
                }
            }
            if (!empty($filteredCategory)) {
                $filtered[$category] = $filteredCategory;
            }
        }

        $this->filteredEndpoints = $filtered;
    }

    public function clearSearch()
    {
        $this->searchQuery = '';
        $this->filteredEndpoints = $this->documentation['endpoints'] ?? [];
    }

    public function copyEndpoint($endpoint)
    {
        $this->dispatch('copyToClipboard', ['text' => $endpoint]);
        $this->addNotification('Endpoint copied to clipboard', 'success');
    }

    public function handleDocumentationLoaded($data)
    {
        $this->addNotification('API documentation loaded successfully', 'success');
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
                'Authorization' => 'Bearer ' . auth()->user()->createToken('api-documentation')->plainTextToken,
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
            ], 'api_documentation');

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.game.api-documentation', [
            'sections' => [
                'overview' => 'Overview',
                'endpoints' => 'Endpoints',
                'schemas' => 'Schemas',
                'examples' => 'Examples',
                'errors' => 'Error Codes',
                'rate_limiting' => 'Rate Limiting',
                'components' => 'Larautilx Components',
            ],
        ]);
    }
}

