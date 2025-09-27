<?php

namespace App\Livewire\Admin;

use App\Services\IntrospectService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class IntrospectAnalyzer extends Component
{
    use WithPagination;

    public $isLoading = false;
    public $analysisResults = [];
    public $selectedTab = 'models';
    public $showDetails = false;
    public $selectedModel = null;
    public $selectedRoute = null;
    public $selectedView = null;
    public $selectedClass = null;
    
    // Analysis options
    public $includeModels = true;
    public $includeRoutes = true;
    public $includeViews = true;
    public $includeClasses = true;
    public $includeSchemas = true;
    public $includeDependencies = true;
    public $includePerformance = true;
    
    // Filter options
    public $searchQuery = '';
    public $filterByType = 'all';
    public $sortBy = 'name';
    public $sortOrder = 'asc';
    
    // Performance metrics
    public $performanceMetrics = [];
    public $complexityThreshold = 50;
    
    // Real-time features
    public $autoRefresh = false;
    public $refreshInterval = 30;
    public $lastUpdate = null;

    protected $listeners = [
        'refreshAnalysis',
        'exportAnalysis',
        'clearAnalysis',
    ];

    public function mount()
    {
        $this->loadAnalysis();
    }

    public function loadAnalysis()
    {
        $this->isLoading = true;
        
        try {
            $introspectService = app(IntrospectService::class);
            
            $this->analysisResults = [];
            
            if ($this->includeModels) {
                $this->analysisResults['models'] = $introspectService->getModelAnalysis();
            }
            
            if ($this->includeRoutes) {
                $this->analysisResults['routes'] = $introspectService->getRouteAnalysis();
            }
            
            if ($this->includeViews) {
                $this->analysisResults['views'] = $introspectService->getViewAnalysis();
            }
            
            if ($this->includeClasses) {
                $this->analysisResults['classes'] = $introspectService->getClassAnalysis();
            }
            
            if ($this->includeSchemas) {
                $this->analysisResults['schemas'] = $introspectService->getModelSchemas();
            }
            
            if ($this->includeDependencies) {
                $this->analysisResults['dependencies'] = $introspectService->getModelDependencies();
            }
            
            if ($this->includePerformance) {
                $this->analysisResults['performance'] = $introspectService->getModelPerformanceMetrics();
                $this->performanceMetrics = $this->analysisResults['performance'];
            }
            
            $this->lastUpdate = now();
            $this->isLoading = false;
            
            session()->flash('message', 'Analysis completed successfully!');
            
        } catch (\Exception $e) {
            $this->isLoading = false;
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());
        }
    }

    public function selectTab($tab)
    {
        $this->selectedTab = $tab;
        $this->showDetails = false;
        $this->selectedModel = null;
        $this->selectedRoute = null;
        $this->selectedView = null;
        $this->selectedClass = null;
    }

    public function showModelDetails($modelName)
    {
        $this->selectedModel = $modelName;
        $this->showDetails = true;
        $this->selectedTab = 'models';
    }

    public function showRouteDetails($routeName)
    {
        $this->selectedRoute = $routeName;
        $this->showDetails = true;
        $this->selectedTab = 'routes';
    }

    public function showViewDetails($viewName)
    {
        $this->selectedView = $viewName;
        $this->showDetails = true;
        $this->selectedTab = 'views';
    }

    public function showClassDetails($className)
    {
        $this->selectedClass = $className;
        $this->showDetails = true;
        $this->selectedTab = 'classes';
    }

    public function exportAnalysis()
    {
        $filename = 'introspect-analysis-' . now()->format('Y-m-d-H-i-s') . '.json';
        $content = json_encode($this->analysisResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        
        return response()->streamDownload(
            function () use ($content) {
                echo $content;
            },
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function clearAnalysis()
    {
        $this->analysisResults = [];
        $this->performanceMetrics = [];
        $this->showDetails = false;
        $this->selectedModel = null;
        $this->selectedRoute = null;
        $this->selectedView = null;
        $this->selectedClass = null;
        $this->lastUpdate = null;
        
        session()->flash('message', 'Analysis cleared!');
    }

    public function refreshAnalysis()
    {
        $this->loadAnalysis();
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-polling', interval: $this->refreshInterval * 1000);
        } else {
            $this->dispatch('stop-polling');
        }
    }

    public function getFilteredModels()
    {
        if (!isset($this->analysisResults['models'])) {
            return [];
        }

        $models = $this->analysisResults['models'];
        
        if ($this->searchQuery) {
            $models = array_filter($models, function ($model, $name) {
                return stripos($name, $this->searchQuery) !== false;
            }, ARRAY_FILTER_USE_BOTH);
        }
        
        if ($this->filterByType !== 'all') {
            $models = array_filter($models, function ($model) {
                return $this->filterByType === 'game' ? 
                    str_contains($model['class'], 'Game') : 
                    !str_contains($model['class'], 'Game');
            });
        }
        
        return $models;
    }

    public function getFilteredRoutes()
    {
        if (!isset($this->analysisResults['routes'])) {
            return [];
        }

        $routes = $this->analysisResults['routes'];
        
        if ($this->searchQuery) {
            foreach (['game_routes', 'api_routes', 'auth_routes'] as $type) {
                if (isset($routes[$type])) {
                    $routes[$type] = array_filter($routes[$type], function ($route) {
                        return stripos($route, $this->searchQuery) !== false;
                    });
                }
            }
        }
        
        return $routes;
    }

    public function getFilteredViews()
    {
        if (!isset($this->analysisResults['views'])) {
            return [];
        }

        $views = $this->analysisResults['views'];
        
        if ($this->searchQuery) {
            foreach (['game_views', 'livewire_views', 'component_views'] as $type) {
                if (isset($views[$type])) {
                    $views[$type] = array_filter($views[$type], function ($view) {
                        return stripos($view, $this->searchQuery) !== false;
                    });
                }
            }
        }
        
        return $views;
    }

    public function getFilteredClasses()
    {
        if (!isset($this->analysisResults['classes'])) {
            return [];
        }

        $classes = $this->analysisResults['classes'];
        
        if ($this->searchQuery) {
            foreach (['game_controllers', 'game_services', 'livewire_components'] as $type) {
                if (isset($classes[$type])) {
                    $classes[$type] = array_filter($classes[$type], function ($class) {
                        return stripos($class, $this->searchQuery) !== false;
                    });
                }
            }
        }
        
        return $classes;
    }

    public function getComplexityColor($complexity)
    {
        if ($complexity <= 20) return 'green';
        if ($complexity <= 50) return 'yellow';
        if ($complexity <= 100) return 'orange';
        return 'red';
    }

    public function getComplexityLabel($complexity)
    {
        if ($complexity <= 20) return 'Low';
        if ($complexity <= 50) return 'Medium';
        if ($complexity <= 100) return 'High';
        return 'Very High';
    }

    public function render()
    {
        return view('livewire.admin.introspect-analyzer', [
            'filteredModels' => $this->getFilteredModels(),
            'filteredRoutes' => $this->getFilteredRoutes(),
            'filteredViews' => $this->getFilteredViews(),
            'filteredClasses' => $this->getFilteredClasses(),
        ]);
    }
}
