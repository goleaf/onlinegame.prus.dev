<div class="p-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">
            🔍 Laravel Introspect Analyzer
        </h2>
        
        @if(session('message'))
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ session('message') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ session('error') }}
            </div>
        @endif

        <!-- Analysis Controls -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-center justify-between">
                <div class="flex flex-wrap gap-4">
                    <button 
                        wire:click="loadAnalysis" 
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="loadAnalysis">🔄 Run Analysis</span>
                        <span wire:loading wire:target="loadAnalysis">⏳ Analyzing...</span>
                    </button>
                    
                    <button 
                        wire:click="exportAnalysis" 
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        📥 Export JSON
                    </button>
                    
                    <button 
                        wire:click="clearAnalysis" 
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                    >
                        🗑️ Clear
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <label class="flex items-center">
                        <input type="checkbox" wire:model="autoRefresh" wire:change="toggleAutoRefresh" class="mr-2">
                        <span class="text-sm">Auto Refresh</span>
                    </label>
                    
                    @if($lastUpdate)
                        <span class="text-sm text-gray-500">
                            Last updated: {{ $lastUpdate->diffForHumans() }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Analysis Options -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <h3 class="text-lg font-semibold mb-3">Analysis Options</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includeModels" class="mr-2">
                    <span class="text-sm">Models</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includeRoutes" class="mr-2">
                    <span class="text-sm">Routes</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includeViews" class="mr-2">
                    <span class="text-sm">Views</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includeClasses" class="mr-2">
                    <span class="text-sm">Classes</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includeSchemas" class="mr-2">
                    <span class="text-sm">Schemas</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includeDependencies" class="mr-2">
                    <span class="text-sm">Dependencies</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="includePerformance" class="mr-2">
                    <span class="text-sm">Performance</span>
                </label>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4 items-center">
                <div class="flex-1 min-w-64">
                    <input 
                        type="text" 
                        wire:model.live="searchQuery" 
                        placeholder="Search..." 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                </div>
                
                <select wire:model.live="filterByType" class="px-3 py-2 border border-gray-300 rounded-md">
                    <option value="all">All Types</option>
                    <option value="game">Game Models</option>
                    <option value="system">System Models</option>
                </select>
                
                <select wire:model.live="sortBy" class="px-3 py-2 border border-gray-300 rounded-md">
                    <option value="name">Name</option>
                    <option value="complexity">Complexity</option>
                    <option value="properties">Properties</option>
                </select>
                
                <select wire:model.live="sortOrder" class="px-3 py-2 border border-gray-300 rounded-md">
                    <option value="asc">Ascending</option>
                    <option value="desc">Descending</option>
                </select>
            </div>
        </div>
    </div>

    @if($isLoading)
        <div class="flex justify-center items-center py-12">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-lg">Analyzing codebase...</span>
        </div>
    @elseif(empty($analysisResults))
        <div class="text-center py-12">
            <div class="text-6xl mb-4">🔍</div>
            <h3 class="text-xl font-semibold mb-2">No Analysis Results</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Click "Run Analysis" to start analyzing your codebase with Laravel Introspect.
            </p>
        </div>
    @else
        <!-- Tabs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="-mb-px flex space-x-8 px-6">
                    @if(isset($analysisResults['models']))
                        <button 
                            wire:click="selectTab('models')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'models' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            📊 Models ({{ count($analysisResults['models']) }})
                        </button>
                    @endif
                    
                    @if(isset($analysisResults['routes']))
                        <button 
                            wire:click="selectTab('routes')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'routes' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            🛣️ Routes ({{ $analysisResults['routes']['total_routes'] ?? 0 }})
                        </button>
                    @endif
                    
                    @if(isset($analysisResults['views']))
                        <button 
                            wire:click="selectTab('views')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'views' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            👁️ Views ({{ $analysisResults['views']['total_views'] ?? 0 }})
                        </button>
                    @endif
                    
                    @if(isset($analysisResults['classes']))
                        <button 
                            wire:click="selectTab('classes')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'classes' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            🏗️ Classes ({{ $analysisResults['classes']['total_classes'] ?? 0 }})
                        </button>
                    @endif
                    
                    @if(isset($analysisResults['performance']))
                        <button 
                            wire:click="selectTab('performance')"
                            class="py-4 px-1 border-b-2 font-medium text-sm {{ $selectedTab === 'performance' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            ⚡ Performance ({{ count($analysisResults['performance']) }})
                        </button>
                    @endif
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                @if($selectedTab === 'models' && isset($analysisResults['models']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($filteredModels as $name => $model)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer"
                                 wire:click="showModelDetails('{{ $name }}')">
                                <h4 class="font-semibold text-lg mb-2">{{ $name }}</h4>
                                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                    <div>Properties: {{ count($model['properties'] ?? []) }}</div>
                                    <div>Fillable: {{ count($model['fillable'] ?? []) }}</div>
                                    <div>Casts: {{ count($model['casts'] ?? []) }}</div>
                                    <div>Relationships: {{ count($model['relationships'] ?? []) }}</div>
                                </div>
                                @if(isset($performanceMetrics[$name]))
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            bg-{{ $this->getComplexityColor($performanceMetrics[$name]['complexity_score']) }}-100 
                                            text-{{ $this->getComplexityColor($performanceMetrics[$name]['complexity_score']) }}-800">
                                            {{ $this->getComplexityLabel($performanceMetrics[$name]['complexity_score']) }} Complexity
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($selectedTab === 'routes' && isset($analysisResults['routes']))
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-lg mb-3">Game Routes ({{ count($analysisResults['routes']['game_routes'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['routes']['game_routes'] ?? [] as $route)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $route }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-lg mb-3">API Routes ({{ count($analysisResults['routes']['api_routes'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['routes']['api_routes'] ?? [] as $route)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $route }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($selectedTab === 'views' && isset($analysisResults['views']))
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-lg mb-3">Game Views ({{ count($analysisResults['views']['game_views'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['views']['game_views'] ?? [] as $view)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $view }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-lg mb-3">Livewire Views ({{ count($analysisResults['views']['livewire_views'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['views']['livewire_views'] ?? [] as $view)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $view }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($selectedTab === 'classes' && isset($analysisResults['classes']))
                    <div class="space-y-6">
                        <div>
                            <h4 class="font-semibold text-lg mb-3">Game Controllers ({{ count($analysisResults['classes']['game_controllers'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['classes']['game_controllers'] ?? [] as $controller)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $controller }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-lg mb-3">Game Services ({{ count($analysisResults['classes']['game_services'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['classes']['game_services'] ?? [] as $service)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $service }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-lg mb-3">Livewire Components ({{ count($analysisResults['classes']['livewire_components'] ?? []) }})</h4>
                            <div class="grid grid-cols-1 gap-2">
                                @foreach($analysisResults['classes']['livewire_components'] ?? [] as $component)
                                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded border">
                                        <code class="text-sm">{{ $component }}</code>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if($selectedTab === 'performance' && isset($analysisResults['performance']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($analysisResults['performance'] as $name => $metrics)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <h4 class="font-semibold text-lg mb-3">{{ $name }}</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span>Properties:</span>
                                        <span class="font-medium">{{ $metrics['property_count'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Fillable:</span>
                                        <span class="font-medium">{{ $metrics['fillable_count'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Casts:</span>
                                        <span class="font-medium">{{ $metrics['cast_count'] }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Relationships:</span>
                                        <span class="font-medium">{{ $metrics['relationship_count'] }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span>Complexity:</span>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            bg-{{ $this->getComplexityColor($metrics['complexity_score']) }}-100 
                                            text-{{ $this->getComplexityColor($metrics['complexity_score']) }}-800">
                                            {{ $metrics['complexity_score'] }} - {{ $this->getComplexityLabel($metrics['complexity_score']) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Details Modal -->
        @if($showDetails && $selectedModel)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            Model Details: {{ $selectedModel }}
                        </h3>
                        
                        @if(isset($analysisResults['models'][$selectedModel]))
                            @php $model = $analysisResults['models'][$selectedModel] @endphp
                            
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-semibold mb-2">Class</h4>
                                    <code class="text-sm bg-gray-100 dark:bg-gray-700 p-2 rounded block">{{ $model['class'] }}</code>
                                </div>
                                
                                @if(!empty($model['fillable']))
                                    <div>
                                        <h4 class="font-semibold mb-2">Fillable Attributes</h4>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($model['fillable'] as $attr)
                                                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">{{ $attr }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                @if(!empty($model['casts']))
                                    <div>
                                        <h4 class="font-semibold mb-2">Casts</h4>
                                        <div class="space-y-1">
                                            @foreach($model['casts'] as $attr => $cast)
                                                <div class="text-sm">
                                                    <span class="font-medium">{{ $attr }}:</span>
                                                    <span class="text-gray-600 dark:text-gray-400">{{ $cast }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                                
                                @if(!empty($model['relationships']))
                                    <div>
                                        <h4 class="font-semibold mb-2">Relationships</h4>
                                        <div class="space-y-1">
                                            @foreach($model['relationships'] as $rel)
                                                <div class="text-sm">
                                                    <span class="font-medium">{{ $rel['model'] }}:</span>
                                                    <span class="text-gray-600 dark:text-gray-400">{{ $rel['type'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        <div class="mt-6 flex justify-end">
                            <button 
                                wire:click="$set('showDetails', false)"
                                class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
                            >
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@script
<script>
    // Auto-refresh functionality
    let pollingInterval;
    
    $wire.on('start-polling', (event) => {
        const interval = event.interval || 30000;
        pollingInterval = setInterval(() => {
            $wire.call('refreshAnalysis');
        }, interval);
    });
    
    $wire.on('stop-polling', () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    });
    
    // Cleanup on component destroy
    document.addEventListener('livewire:navigated', () => {
        if (pollingInterval) {
            clearInterval(pollingInterval);
            pollingInterval = null;
        }
    });
</script>
@endscript
