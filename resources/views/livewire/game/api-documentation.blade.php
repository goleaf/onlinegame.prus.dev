<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Larautilx API Documentation</h2>
            <div class="flex space-x-2">
                <button wire:click="loadDocumentation" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh
                </button>
            </div>
        </div>

        <!-- API Info -->
        @if(!empty($documentation))
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $documentation['version'] ?? 'N/A' }}</div>
                    <div class="text-sm text-blue-800">API Version</div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ count($documentation['endpoints'] ?? []) }}</div>
                    <div class="text-sm text-green-800">Endpoint Categories</div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ count($documentation['schemas'] ?? []) }}</div>
                    <div class="text-sm text-yellow-800">Data Schemas</div>
                </div>
            </div>

            <!-- Base URL -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h3 class="font-medium text-gray-900 mb-2">Base URL</h3>
                <code class="text-sm bg-white px-2 py-1 rounded border">{{ $documentation['base_url'] ?? 'N/A' }}</code>
            </div>
        @endif

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                @foreach($sections as $key => $label)
                    <button wire:click="setActiveSection('{{ $key }}')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeSection === $key ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>
    </div>

    <!-- Overview Tab -->
    @if($activeSection === 'overview')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">API Overview</h3>
            
            @if(!empty($documentation))
                <div class="space-y-6">
                    <!-- Description -->
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                        <p class="text-gray-700">{{ $documentation['description'] ?? 'No description available.' }}</p>
                    </div>

                    <!-- Authentication -->
                    @if(!empty($documentation['authentication']))
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Authentication</h4>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="mb-2">
                                    <strong>Type:</strong> {{ $documentation['authentication']['type'] ?? 'N/A' }}
                                </div>
                                <div class="mb-2">
                                    <strong>Description:</strong> {{ $documentation['authentication']['description'] ?? 'N/A' }}
                                </div>
                                <div>
                                    <strong>Example:</strong>
                                    <code class="text-sm bg-white px-2 py-1 rounded border ml-2">{{ $documentation['authentication']['example'] ?? 'N/A' }}</code>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Endpoint Categories -->
                    @if(!empty($documentation['endpoints']))
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Endpoint Categories</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($documentation['endpoints'] as $category => $endpoints)
                                    <div class="border rounded-lg p-4">
                                        <h5 class="font-medium text-gray-900 mb-2">{{ ucfirst(str_replace('_', ' ', $category)) }}</h5>
                                        <p class="text-sm text-gray-600">{{ count($endpoints) }} endpoints</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No documentation data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Endpoints Tab -->
    @if($activeSection === 'endpoints')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">API Endpoints</h3>
                <div class="flex space-x-2">
                    <input wire:model.live.debounce.300ms="searchQuery" type="text" placeholder="Search endpoints..." class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    @if($searchQuery)
                        <button wire:click="clearSearch" class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-md text-sm">
                            Clear
                        </button>
                    @endif
                </div>
            </div>
            
            @if(!empty($filteredEndpoints))
                <div class="space-y-6">
                    @foreach($filteredEndpoints as $category => $endpoints)
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">{{ ucfirst(str_replace('_', ' ', $category)) }}</h4>
                            <div class="space-y-4">
                                @foreach($endpoints as $endpoint => $details)
                                    <div class="border rounded-lg p-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                @php
                                                    $method = strtoupper(explode(' ', $endpoint)[0]);
                                                    $color = match($method) {
                                                        'GET' => 'bg-green-100 text-green-800',
                                                        'POST' => 'bg-blue-100 text-blue-800',
                                                        'PUT' => 'bg-yellow-100 text-yellow-800',
                                                        'DELETE' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $color }}">
                                                    {{ $method }}
                                                </span>
                                                <code class="text-sm font-mono">{{ $endpoint }}</code>
                                            </div>
                                            <button wire:click="copyEndpoint('{{ $endpoint }}')" class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-3">{{ $details['description'] ?? 'No description available.' }}</p>
                                        
                                        @if(!empty($details['parameters']))
                                            <div class="mb-3">
                                                <h5 class="font-medium text-gray-900 mb-2">Parameters</h5>
                                                <div class="space-y-1">
                                                    @foreach($details['parameters'] as $param => $info)
                                                        <div class="text-sm">
                                                            <code class="bg-gray-100 px-1 rounded">{{ $param }}</code>
                                                            @if(is_string($info))
                                                                <span class="text-gray-600">- {{ $info }}</span>
                                                            @elseif(is_array($info))
                                                                <span class="text-gray-600">- {{ $info['description'] ?? 'No description' }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if(!empty($details['response']))
                                            <div>
                                                <h5 class="font-medium text-gray-900 mb-2">Response</h5>
                                                <pre class="bg-gray-50 p-3 rounded text-xs overflow-x-auto"><code>{{ json_encode($details['response'], JSON_PRETTY_PRINT) }}</code></pre>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    @if($searchQuery)
                        No endpoints found matching "{{ $searchQuery }}".
                    @else
                        No endpoints available.
                    @endif
                </div>
            @endif
        </div>
    @endif

    <!-- Schemas Tab -->
    @if($activeSection === 'schemas')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Data Schemas</h3>
            
            @if(!empty($documentation['schemas']))
                <div class="space-y-6">
                    @foreach($documentation['schemas'] as $schemaName => $schema)
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">{{ $schemaName }}</h4>
                            <pre class="bg-gray-50 p-3 rounded text-xs overflow-x-auto"><code>{{ json_encode($schema, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No schemas available.
                </div>
            @endif
        </div>
    @endif

    <!-- Examples Tab -->
    @if($activeSection === 'examples')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">API Examples</h3>
            
            @if(!empty($documentation['examples']))
                <div class="space-y-6">
                    @foreach($documentation['examples'] as $exampleName => $example)
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">{{ ucfirst(str_replace('_', ' ', $exampleName)) }}</h4>
                            
                            @if(!empty($example['request']))
                                <div class="mb-4">
                                    <h5 class="font-medium text-gray-900 mb-2">Request</h5>
                                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-x-auto"><code>{{ json_encode($example['request'], JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                            @endif

                            @if(!empty($example['response']))
                                <div>
                                    <h5 class="font-medium text-gray-900 mb-2">Response</h5>
                                    <pre class="bg-gray-50 p-3 rounded text-xs overflow-x-auto"><code>{{ json_encode($example['response'], JSON_PRETTY_PRINT) }}</code></pre>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No examples available.
                </div>
            @endif
        </div>
    @endif

    <!-- Error Codes Tab -->
    @if($activeSection === 'errors')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Error Codes</h3>
            
            @if(!empty($documentation['error_codes']))
                <div class="space-y-4">
                    @foreach($documentation['error_codes'] as $code => $info)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center space-x-2 mb-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    {{ $code }}
                                </span>
                                <span class="font-medium text-gray-900">{{ $info['description'] ?? 'Unknown Error' }}</span>
                            </div>
                            <p class="text-sm text-gray-600">{{ $info['meaning'] ?? 'No description available.' }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No error codes available.
                </div>
            @endif
        </div>
    @endif

    <!-- Rate Limiting Tab -->
    @if($activeSection === 'rate_limiting')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Rate Limiting</h3>
            
            @if(!empty($documentation['rate_limiting']))
                <div class="space-y-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Description</h4>
                        <p class="text-gray-700">{{ $documentation['rate_limiting']['description'] ?? 'No description available.' }}</p>
                    </div>

                    @if(!empty($documentation['rate_limiting']['limits']))
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Rate Limits</h4>
                            <div class="space-y-2">
                                @foreach($documentation['rate_limiting']['limits'] as $type => $limit)
                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $type)) }}</span>
                                        <span class="text-sm text-gray-600">{{ $limit }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if(!empty($documentation['rate_limiting']['headers']))
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">Response Headers</h4>
                            <div class="space-y-2">
                                @foreach($documentation['rate_limiting']['headers'] as $header => $description)
                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <code class="text-sm font-mono">{{ $header }}</code>
                                        <span class="text-sm text-gray-600">{{ $description }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No rate limiting information available.
                </div>
            @endif
        </div>
    @endif

    <!-- Larautilx Components Tab -->
    @if($activeSection === 'components')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Larautilx Components</h3>
            
            @if(!empty($documentation['larautilx_components']))
                <div class="space-y-6">
                    @foreach($documentation['larautilx_components'] as $category => $components)
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">{{ ucfirst(str_replace('_', ' ', $category)) }}</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach($components as $component => $description)
                                    <div class="border rounded-lg p-4">
                                        <h5 class="font-medium text-gray-900 mb-2">{{ $component }}</h5>
                                        <p class="text-sm text-gray-600">{{ $description }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No Larautilx components information available.
                </div>
            @endif
        </div>
    @endif

    <!-- Loading Overlay -->
    @if($isLoading)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-700">Loading API documentation...</span>
            </div>
        </div>
    @endif
</div>

