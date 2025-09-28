<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Larautilx Integration Dashboard</h2>
            <div class="flex space-x-2">
                <button wire:click="refreshDashboard" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh
                </button>
                <button wire:click="testComponents" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Test Components
                </button>
            </div>
        </div>

        <!-- Package Info -->
        @if(!empty($integrationSummary))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $integrationSummary['package_info']['version'] ?? 'N/A' }}</div>
                    <div class="text-sm text-blue-800">Package Version</div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ $integrationSummary['package_info']['installed'] ? 'Yes' : 'No' }}</div>
                    <div class="text-sm text-green-800">Installed</div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ count(array_filter($integrationSummary['integrated_components']['utilities'] ?? [])) }}</div>
                    <div class="text-sm text-yellow-800">Utilities</div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ count(array_filter($integrationSummary['game_integration']['controllers_created'] ?? [])) }}</div>
                    <div class="text-sm text-purple-800">Controllers</div>
                </div>
            </div>
        @endif

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('overview')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Overview
                </button>
                <button wire:click="setActiveTab('components')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'components' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Components
                </button>
                <button wire:click="setActiveTab('integration')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'integration' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Integration
                </button>
                <button wire:click="setActiveTab('health')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'health' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    System Health
                </button>
                <button wire:click="setActiveTab('testing')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'testing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Testing
                </button>
            </nav>
        </div>
    </div>

    <!-- Overview Tab -->
    @if($activeTab === 'overview')
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Integration Status -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Integration Status</h3>
                
                @if(!empty($dashboardData['integration_status']))
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Status:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ ucfirst($dashboardData['integration_status']['status'] ?? 'Unknown') }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Version:</span>
                            <span class="font-medium">{{ $dashboardData['integration_status']['version'] ?? 'N/A' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Components:</span>
                            <span class="font-medium">{{ count($dashboardData['integration_status']['components'] ?? []) }}</span>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No integration status data available.
                    </div>
                @endif
            </div>

            <!-- AI Service Status -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">AI Service Status</h3>
                
                @if(!empty($dashboardData['ai_service_status']))
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Available:</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $dashboardData['ai_service_status']['available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $dashboardData['ai_service_status']['available'] ? 'Yes' : 'No' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Current Provider:</span>
                            <span class="font-medium">{{ ucfirst($dashboardData['ai_service_status']['current_provider'] ?? 'None') }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Available Providers:</span>
                            <span class="font-medium">{{ count($dashboardData['ai_service_status']['available_providers'] ?? []) }}</span>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No AI service status data available.
                    </div>
                @endif
            </div>

            <!-- Feature Toggles -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Feature Toggles</h3>
                
                @if(!empty($dashboardData['feature_toggles']))
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Enabled Features:</span>
                            <span class="font-medium">{{ $dashboardData['feature_toggles']['enabled_count'] ?? 0 }} / {{ $dashboardData['feature_toggles']['total_count'] ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $dashboardData['feature_toggles']['enabled_percentage'] ?? 0 }}%"></div>
                        </div>
                        <div class="text-sm text-gray-500">
                            {{ $dashboardData['feature_toggles']['enabled_percentage'] ?? 0 }}% enabled
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No feature toggle data available.
                    </div>
                @endif
            </div>

            <!-- Usage Statistics -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Usage Statistics</h3>
                
                @if(!empty($dashboardData['usage_statistics']))
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Users:</span>
                            <span class="font-medium">{{ $dashboardData['usage_statistics']['game_entities']['users'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Players:</span>
                            <span class="font-medium">{{ $dashboardData['usage_statistics']['game_entities']['players'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Villages:</span>
                            <span class="font-medium">{{ $dashboardData['usage_statistics']['game_entities']['villages'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Online Players:</span>
                            <span class="font-medium">{{ $dashboardData['usage_statistics']['active_sessions']['online_players'] ?? 0 }}</span>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        No usage statistics available.
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Components Tab -->
    @if($activeTab === 'components')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Larautilx Components</h3>
            
            @if(!empty($integrationSummary['integrated_components']))
                <div class="space-y-6">
                    <!-- Traits -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Traits</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($integrationSummary['integrated_components']['traits'] as $trait => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $trait }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Available' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Utilities -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Utilities</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($integrationSummary['integrated_components']['utilities'] as $utility => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $utility }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Available' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Controllers -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Controllers</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($integrationSummary['integrated_components']['controllers'] as $controller => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $controller }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Available' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Middleware -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Middleware</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($integrationSummary['integrated_components']['middleware'] as $middleware => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $middleware }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Available' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- LLM Providers -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">LLM Providers</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach($integrationSummary['integrated_components']['llm_providers'] as $provider => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $provider }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Available' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No component data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Integration Tab -->
    @if($activeTab === 'integration')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Game Integration</h3>
            
            @if(!empty($integrationSummary['game_integration']))
                <div class="space-y-6">
                    <!-- Controllers Created -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Controllers Created</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($integrationSummary['game_integration']['controllers_created'] as $controller => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $controller }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Created' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Livewire Components -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Livewire Components</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($integrationSummary['game_integration']['livewire_components'] as $component => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $component }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Created' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Services Created -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Services Created</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($integrationSummary['game_integration']['services_created'] as $service => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $service }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Created' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Models Enhanced -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Models Enhanced</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($integrationSummary['game_integration']['models_enhanced'] as $model => $status)
                                <div class="flex items-center justify-between p-3 border rounded-lg">
                                    <span class="text-sm font-medium">{{ $model }}</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $status ? 'Enhanced' : 'Missing' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No integration data available.
                </div>
            @endif
        </div>
    @endif

    <!-- System Health Tab -->
    @if($activeTab === 'health')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">System Health</h3>
            
            @if(!empty($dashboardData['system_health']))
                <div class="mb-6">
                    <div class="flex items-center space-x-2">
                        <span class="text-lg font-medium">Overall Status:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $dashboardData['system_health']['overall_status'] === 'healthy' ? 'bg-green-100 text-green-800' : ($dashboardData['system_health']['overall_status'] === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                            {{ ucfirst($dashboardData['system_health']['overall_status']) }}
                        </span>
                    </div>
                    <div class="mt-2 text-sm text-gray-600">
                        {{ $dashboardData['system_health']['healthy_count'] }} / {{ $dashboardData['system_health']['total_count'] }} checks passing
                        ({{ $dashboardData['system_health']['health_percentage'] }}%)
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($dashboardData['system_health']['checks'] as $checkName => $status)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $checkName)) }}</h4>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $status === 'healthy' ? 'bg-green-100 text-green-800' : ($status === 'degraded' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No system health data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Testing Tab -->
    @if($activeTab === 'testing')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Component Testing</h3>
            
            <div class="space-y-6">
                <!-- Test Selection -->
                <div>
                    <h4 class="font-medium text-gray-900 mb-3">Select Components to Test</h4>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        @foreach($availableTestComponents as $key => $name)
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="selectedTestComponents" value="{{ $key }}" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">{{ $name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Test Button -->
                <div>
                    <button wire:click="testComponents" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Run Tests
                    </button>
                </div>

                <!-- Test Results -->
                @if(!empty($testResults))
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Test Results</h4>
                        <div class="space-y-4">
                            @foreach($testResults as $component => $result)
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h5 class="font-medium text-gray-900">{{ $availableTestComponents[$component] ?? $component }}</h5>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $result['status'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ ucfirst($result['status']) }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <div><strong>Test:</strong> {{ $result['test'] ?? 'N/A' }}</div>
                                        <div><strong>Result:</strong> {{ $result['result'] ? 'Passed' : 'Failed' }}</div>
                                        <div><strong>Message:</strong> {{ $result['message'] ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    @if($isLoading || $isTesting)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-700">{{ $isTesting ? 'Testing components...' : 'Loading data...' }}</span>
            </div>
        </div>
    @endif
</div>



