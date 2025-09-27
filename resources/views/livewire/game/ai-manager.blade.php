<div>
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">AI Content Manager</h2>
            <div class="flex space-x-2">
                <button wire:click="loadAIStatus" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Refresh Status
                </button>
                <button wire:click="clearGeneratedContent" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Clear Content
                </button>
            </div>
        </div>

        <!-- AI Status -->
        @if(!empty($aiStatus))
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">{{ $aiStatus['available'] ? 'Active' : 'Inactive' }}</div>
                    <div class="text-sm text-blue-800">AI Service</div>
                </div>
                
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600">{{ ucfirst($aiStatus['current_provider'] ?? 'None') }}</div>
                    <div class="text-sm text-green-800">Current Provider</div>
                </div>
                
                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-yellow-600">{{ $aiStatus['providers_count'] ?? 0 }}</div>
                    <div class="text-sm text-yellow-800">Available Providers</div>
                </div>
                
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600">{{ count($aiStatus['available_providers'] ?? []) }}</div>
                    <div class="text-sm text-purple-800">Configured</div>
                </div>
            </div>
        @endif

        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="setActiveTab('status')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'status' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Status
                </button>
                <button wire:click="setActiveTab('names')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'names' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Name Generation
                </button>
                <button wire:click="setActiveTab('content')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'content' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Content Generation
                </button>
                <button wire:click="setActiveTab('custom')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'custom' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Custom Content
                </button>
                <button wire:click="setActiveTab('settings')" class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'settings' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Settings
                </button>
            </nav>
        </div>
    </div>

    <!-- Status Tab -->
    @if($activeTab === 'status')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">AI Service Status</h3>
            
            @if($isLoading)
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                    <span class="ml-2">Loading AI status...</span>
                </div>
            @elseif(!empty($aiStatus))
                <div class="space-y-6">
                    <!-- Service Status -->
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Service Status</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Available:</span>
                                <span class="font-medium {{ $aiStatus['available'] ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $aiStatus['available'] ? 'Yes' : 'No' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Provider:</span>
                                <span class="font-medium">{{ ucfirst($aiStatus['current_provider'] ?? 'None') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Providers Count:</span>
                                <span class="font-medium">{{ $aiStatus['providers_count'] ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Available Providers -->
                    @if(!empty($aiStatus['available_providers']))
                        <div class="border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Available Providers</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($aiStatus['available_providers'] as $provider)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        {{ ucfirst($provider) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    No AI status data available.
                </div>
            @endif
        </div>
    @endif

    <!-- Name Generation Tab -->
    @if($activeTab === 'names')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Name Generation</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Village Names -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Village Names</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Count</label>
                            <input type="number" wire:model="villageCount" min="1" max="10" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tribe</label>
                            <select wire:model="selectedTribe" class="w-full border border-gray-300 rounded-md px-3 py-2">
                                @foreach($tribes as $tribe)
                                    <option value="{{ $tribe }}">{{ ucfirst($tribe) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button wire:click="generateVillageNames" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Generate Village Names
                        </button>
                    </div>
                    
                    @if(isset($generatedContent['village_names']))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Generated Names:</h5>
                            <ul class="space-y-1 text-sm">
                                @foreach($generatedContent['village_names']['names'] as $name)
                                    <li class="text-gray-700">• {{ $name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Alliance Names -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Alliance Names</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Count</label>
                            <input type="number" wire:model="allianceCount" min="1" max="10" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <button wire:click="generateAllianceNames" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Generate Alliance Names
                        </button>
                    </div>
                    
                    @if(isset($generatedContent['alliance_names']))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Generated Names:</h5>
                            <ul class="space-y-1 text-sm">
                                @foreach($generatedContent['alliance_names']['names'] as $name)
                                    <li class="text-gray-700">• {{ $name }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Content Generation Tab -->
    @if($activeTab === 'content')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Content Generation</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quest Description -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Quest Description</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quest Type</label>
                            <input type="text" wire:model="questType" placeholder="e.g., 'Defend the Village'" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Context (comma-separated)</label>
                            <input type="text" wire:model="questContext" placeholder="e.g., 'enemy attack, 100 troops, urgent'" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <button wire:click="generateQuestDescription" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Generate Quest Description
                        </button>
                    </div>
                    
                    @if(isset($generatedContent['quest_description']))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Generated Description:</h5>
                            <p class="text-sm text-gray-700">{{ $generatedContent['quest_description']['description'] }}</p>
                        </div>
                    @endif
                </div>

                <!-- Player Message -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Player Message</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message Type</label>
                            <input type="text" wire:model="messageType" placeholder="e.g., 'victory announcement'" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Context (comma-separated)</label>
                            <input type="text" wire:model="messageContext" placeholder="e.g., 'battle won, 50 casualties'" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <button wire:click="generatePlayerMessage" class="w-full bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Generate Player Message
                        </button>
                    </div>
                    
                    @if(isset($generatedContent['player_message']))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Generated Message:</h5>
                            <p class="text-sm text-gray-700">{{ $generatedContent['player_message']['message'] }}</p>
                        </div>
                    @endif
                </div>

                <!-- World Event -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">World Event</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                            <input type="text" wire:model="eventType" placeholder="e.g., 'meteor shower'" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Context (comma-separated)</label>
                            <input type="text" wire:model="eventContext" placeholder="e.g., 'rare event, affects all players'" class="w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <button wire:click="generateWorldEvent" class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Generate World Event
                        </button>
                    </div>
                    
                    @if(isset($generatedContent['world_event']))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Generated Event:</h5>
                            <p class="text-sm text-gray-700">{{ $generatedContent['world_event']['event'] }}</p>
                        </div>
                    @endif
                </div>

                <!-- Strategy Suggestion -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Strategy Suggestion</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Game State (key: value, one per line)</label>
                            <textarea wire:model="strategyGameState" rows="4" placeholder="e.g.,&#10;village_count: 3&#10;total_troops: 500&#10;enemy_threat: high" class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                        <button wire:click="generateStrategySuggestion" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Generate Strategy Suggestion
                        </button>
                    </div>
                    
                    @if(isset($generatedContent['strategy_suggestion']))
                        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                            <h5 class="font-medium text-gray-800 mb-2">Generated Suggestion:</h5>
                            <p class="text-sm text-gray-700">{{ $generatedContent['strategy_suggestion']['suggestion'] }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Custom Content Tab -->
    @if($activeTab === 'custom')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">Custom Content Generation</h3>
            
            <div class="space-y-6">
                <!-- Prompt Input -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Custom Prompt</label>
                    <textarea wire:model="customPrompt" rows="4" placeholder="Enter your custom prompt here..." class="w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                </div>

                <!-- Generation Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Provider</label>
                        <select wire:model="selectedProvider" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            @foreach($availableProviders as $provider)
                                <option value="{{ $provider }}">{{ ucfirst($provider) }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Model</label>
                        <select wire:model="selectedModel" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            @if(isset($availableModels[$selectedProvider]))
                                @foreach($availableModels[$selectedProvider] as $model)
                                    <option value="{{ $model }}">{{ $model }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Temperature</label>
                        <input type="number" wire:model="temperature" min="0" max="2" step="0.1" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Tokens</label>
                        <input type="number" wire:model="maxTokens" min="1" max="2000" class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                </div>

                <!-- JSON Mode Toggle -->
                <div class="flex items-center">
                    <input type="checkbox" wire:model="jsonMode" id="jsonMode" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <label for="jsonMode" class="ml-2 text-sm text-gray-700">JSON Mode</label>
                </div>

                <!-- Generate Button -->
                <button wire:click="generateCustomContent" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors">
                    Generate Custom Content
                </button>

                <!-- Generated Content -->
                @if(isset($generatedContent['custom_content']))
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <h5 class="font-medium text-gray-800 mb-2">Generated Content:</h5>
                        <div class="bg-white p-3 rounded border text-sm text-gray-700 whitespace-pre-wrap">{{ $generatedContent['custom_content']['content'] }}</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Settings Tab -->
    @if($activeTab === 'settings')
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-semibold mb-4">AI Settings</h3>
            
            <div class="space-y-6">
                <!-- Provider Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Provider</label>
                    <div class="flex space-x-4">
                        <select wire:model="selectedProvider" class="flex-1 border border-gray-300 rounded-md px-3 py-2">
                            @foreach($availableProviders as $provider)
                                <option value="{{ $provider }}">{{ ucfirst($provider) }}</option>
                            @endforeach
                        </select>
                        <button wire:click="switchProvider" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition-colors">
                            Switch Provider
                        </button>
                    </div>
                </div>

                <!-- Current Status -->
                @if(!empty($aiStatus))
                    <div class="border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">Current Configuration</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Provider:</span>
                                <span class="font-medium">{{ ucfirst($aiStatus['current_provider'] ?? 'None') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service Status:</span>
                                <span class="font-medium {{ $aiStatus['available'] ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $aiStatus['available'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Feature Toggles -->
                <div class="border rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-3">Feature Toggles</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Village Name Generation</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Alliance Name Generation</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Quest Descriptions</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Battle Reports</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Player Messages</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">World Events</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Strategy Suggestions</span>
                            <span class="text-green-600">✓ Enabled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Loading Overlay -->
    @if($isLoading)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-500"></div>
                <span class="text-gray-700">Generating content...</span>
            </div>
        </div>
    @endif
</div>
