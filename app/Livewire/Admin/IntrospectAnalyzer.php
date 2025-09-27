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
        $startTime = microtime(true);

        ds('IntrospectAnalyzer mounted', [
            'component' => 'IntrospectAnalyzer',
            'mount_time' => now(),
            'user_id' => auth()->id(),
            'admin_panel' => true,
            'selected_tab' => $this->selectedTab
        ]);

        $this->loadAnalysis();

        $mountTime = round((microtime(true) - $startTime) * 1000, 2);
        ds('IntrospectAnalyzer mount completed', [
            'mount_time_ms' => $mountTime,
            'analysis_results_count' => count($this->analysisResults),
            'is_loading' => $this->isLoading
        ]);
    }

    public function loadAnalysis()
    {
        $startTime = microtime(true);
        $this->isLoading = true;

        ds('Analysis loading started', [
            'include_models' => $this->includeModels,
            'include_routes' => $this->includeRoutes,
            'include_views' => $this->includeViews,
            'include_classes' => $this->includeClasses,
            'include_schemas' => $this->includeSchemas,
            'include_dependencies' => $this->includeDependencies,
            'include_performance' => $this->includePerformance
        ]);

        try {
            $introspectService = app(IntrospectService::class);

            $this->analysisResults = [];

            if ($this->includeModels) {
                $modelStart = microtime(true);
                $this->analysisResults['models'] = $introspectService->getModelAnalysis();
                $modelTime = round((microtime(true) - $modelStart) * 1000, 2);
                ds('Models analysis completed', [
                    'models_count' => count($this->analysisResults['models']),
                    'analysis_time_ms' => $modelTime
                ]);
            }

            if ($this->includeRoutes) {
                $routeStart = microtime(true);
                $this->analysisResults['routes'] = $introspectService->getRouteAnalysis();
                $routeTime = round((microtime(true) - $routeStart) * 1000, 2);
                ds('Routes analysis completed', [
                    'routes_count' => count($this->analysisResults['routes']),
                    'analysis_time_ms' => $routeTime
                ]);
            }

            if ($this->includeViews) {
                $viewStart = microtime(true);
                $this->analysisResults['views'] = $introspectService->getViewAnalysis();
                $viewTime = round((microtime(true) - $viewStart) * 1000, 2);
                ds('Views analysis completed', [
                    'views_count' => count($this->analysisResults['views']),
                    'analysis_time_ms' => $viewTime
                ]);
            }

            if ($this->includeClasses) {
                $classStart = microtime(true);
                $this->analysisResults['classes'] = $introspectService->getClassAnalysis();
                $classTime = round((microtime(true) - $classStart) * 1000, 2);
                ds('Classes analysis completed', [
                    'classes_count' => count($this->analysisResults['classes']),
                    'analysis_time_ms' => $classTime
                ]);
            }

            if ($this->includeSchemas) {
                $this->analysisResults['schemas'] = $introspectService->getModelSchemas();
                ds('Schemas analysis completed', [
                    'schemas_count' => count($this->analysisResults['schemas'])
                ]);
            }

            if ($this->includeDependencies) {
                $this->analysisResults['dependencies'] = $introspectService->getModelDependencies();
                ds('Dependencies analysis completed', [
                    'dependencies_count' => count($this->analysisResults['dependencies'])
                ]);
            }

            if ($this->includePerformance) {
                $this->analysisResults['performance'] = $introspectService->getModelPerformanceMetrics();
                $this->performanceMetrics = $this->analysisResults['performance'];
                ds('Performance analysis completed', [
                    'performance_metrics_count' => count($this->analysisResults['performance'])
                ]);
            }

            $this->lastUpdate = now();
            $this->isLoading = false;

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            ds('Analysis completed successfully', [
                'total_analysis_time_ms' => $totalTime,
                'total_results_count' => count($this->analysisResults),
                'last_update' => $this->lastUpdate
            ]);

            session()->flash('message', 'Analysis completed successfully!');
        } catch (\Exception $e) {
            $this->isLoading = false;
            session()->flash('error', 'Analysis failed: ' . $e->getMessage());

            ds('Analysis failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
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
                return $this->filterByType === 'game'
                    ? str_contains($model['class'], 'Game')
                    : !str_contains($model['class'], 'Game');
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
        if ($complexity <= 20)
            return 'green';
        if ($complexity <= 50)
            return 'yellow';
        if ($complexity <= 100)
            return 'orange';
        return 'red';
    }

    public function getComplexityLabel($complexity)
    {
        if ($complexity <= 20)
            return 'Low';
        if ($complexity <= 50)
            return 'Medium';
        if ($complexity <= 100)
            return 'High';
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
