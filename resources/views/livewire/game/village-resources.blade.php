<div class="bg-white rounded-lg shadow p-6">
    <h3 class="text-lg font-semibold mb-4">Village Resources - {{ $village->name }}</h3>
    
    <!-- Resource Overview -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 p-4 rounded">
            <h4 class="font-medium text-green-800">Wood</h4>
            <p class="text-2xl font-bold text-green-600">{{ number_format($resources->amounts->wood) }}</p>
            <p class="text-sm text-green-600">+{{ $resources->production->wood }}/h</p>
            <p class="text-xs text-gray-500">Level {{ $resources->getLevel('wood') }}</p>
        </div>
        
        <div class="bg-orange-50 p-4 rounded">
            <h4 class="font-medium text-orange-800">Clay</h4>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($resources->amounts->clay) }}</p>
            <p class="text-sm text-orange-600">+{{ $resources->production->clay }}/h</p>
            <p class="text-xs text-gray-500">Level {{ $resources->getLevel('clay') }}</p>
        </div>
        
        <div class="bg-gray-50 p-4 rounded">
            <h4 class="font-medium text-gray-800">Iron</h4>
            <p class="text-2xl font-bold text-gray-600">{{ number_format($resources->amounts->iron) }}</p>
            <p class="text-sm text-gray-600">+{{ $resources->production->iron }}/h</p>
            <p class="text-xs text-gray-500">Level {{ $resources->getLevel('iron') }}</p>
        </div>
        
        <div class="bg-yellow-50 p-4 rounded">
            <h4 class="font-medium text-yellow-800">Crop</h4>
            <p class="text-2xl font-bold text-yellow-600">{{ number_format($resources->amounts->crop) }}</p>
            <p class="text-sm text-yellow-600">+{{ $resources->production->crop }}/h</p>
            <p class="text-xs text-gray-500">Level {{ $resources->getLevel('crop') }}</p>
        </div>
    </div>

    <!-- Storage Information -->
    <div class="bg-blue-50 p-4 rounded mb-4">
        <h4 class="font-medium text-blue-800 mb-2">Storage Status</h4>
        <div class="flex items-center justify-between">
            <span class="text-sm text-blue-600">
                {{ number_format($resources->getTotalAmount()) }} / {{ number_format($resources->getTotalCapacity()) }}
            </span>
            <div class="flex-1 mx-4">
                <div class="bg-blue-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" 
                         style="width: {{ $resources->getUtilizationPercentage() }}%"></div>
                </div>
            </div>
            <span class="text-sm text-blue-600">{{ round($resources->getUtilizationPercentage(), 1) }}%</span>
        </div>
        
        @if($resources->isStorageNearlyFull())
            <div class="mt-2 text-sm text-orange-600 font-medium">
                ⚠️ Storage nearly full!
            </div>
        @endif
    </div>

    <!-- Resource Analysis -->
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 p-4 rounded">
            <h4 class="font-medium text-gray-800 mb-2">Resource Balance</h4>
            <p class="text-sm text-gray-600">
                Balance Ratio: {{ round($resources->getResourceBalance(), 2) }}:1
            </p>
            <p class="text-sm text-gray-600">
                Most: {{ ucfirst($resources->getMostAbundantResource()) }} 
                ({{ number_format($resources->amounts->{$resources->getMostAbundantResource()}) }})
            </p>
            <p class="text-sm text-gray-600">
                Least: {{ ucfirst($resources->getLeastAbundantResource()) }} 
                ({{ number_format($resources->amounts->{$resources->getLeastAbundantResource()}) }})
            </p>
            
            @if($resources->isBalanced())
                <span class="inline-block mt-2 px-2 py-1 bg-green-100 text-green-800 text-xs rounded">
                    ✓ Balanced
                </span>
            @else
                <span class="inline-block mt-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                    ⚠️ Imbalanced
                </span>
            @endif
        </div>

        <div class="bg-gray-50 p-4 rounded">
            <h4 class="font-medium text-gray-800 mb-2">Production Analysis</h4>
            <p class="text-sm text-gray-600">
                Total Production: {{ $resources->getTotalProduction() }}/h
            </p>
            <p class="text-sm text-gray-600">
                Efficiency: {{ round($resources->getEfficiency(), 4) }}
            </p>
            <p class="text-sm text-gray-600">
                Time to fill: {{ round($resources->getTimeToFillStorage(), 1) }}h
            </p>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-6 flex gap-2">
        <button wire:click="loadResources" 
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            Refresh
        </button>
        
        @if($resources->isStorageNearlyFull())
            <button class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                Upgrade Storage
            </button>
        @endif
        
        @if(!$resources->isBalanced())
            <button class="px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                Balance Resources
            </button>
        @endif
    </div>
</div>

