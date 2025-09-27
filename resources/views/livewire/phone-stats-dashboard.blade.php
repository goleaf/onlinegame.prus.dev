<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="bg-gradient-to-r from-green-600 to-blue-600 text-white p-6 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Phone Statistics Dashboard</h1>
                    <p class="text-green-100 mt-1">Phone number analytics and verification tracking</p>
                </div>
                <div class="flex space-x-2">
                    <button wire:click="refreshStats" 
                            class="bg-white text-green-600 px-4 py-2 rounded-lg hover:bg-green-50 transition-colors">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Phone Statistics -->
        <div class="p-6">
            <h2 class="text-xl font-semibold mb-4">Phone Statistics</h2>
            
            @if($isLoading)
                <div class="flex items-center justify-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600"></div>
                    <span class="ml-2 text-gray-600">Loading phone statistics...</span>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Total Phones -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <i class="fas fa-phone text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Phones</p>
                                <p class="text-2xl font-bold text-blue-600">{{ number_format($phoneStats['total_phones'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Verified Phones -->
                    <div class="bg-green-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <i class="fas fa-check-circle text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Verified Phones</p>
                                <p class="text-2xl font-bold text-green-600">{{ number_format($phoneStats['verified_phones'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Unverified Phones -->
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <i class="fas fa-exclamation-circle text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Unverified Phones</p>
                                <p class="text-2xl font-bold text-yellow-600">{{ number_format($phoneStats['unverified_phones'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Phone Countries -->
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <i class="fas fa-globe text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Phone Countries</p>
                                <p class="text-2xl font-bold text-purple-600">{{ number_format($phoneStats['phone_countries'] ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Verifications -->
                    <div class="bg-orange-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <i class="fas fa-clock text-orange-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Recent Verifications</p>
                                <p class="text-2xl font-bold text-orange-600">{{ number_format($phoneStats['recent_verifications'] ?? 0) }}</p>
                                <p class="text-xs text-gray-500">Last 7 days</p>
                            </div>
                        </div>
                    </div>

                    <!-- Verification Rate -->
                    <div class="bg-indigo-50 rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <i class="fas fa-percentage text-indigo-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Verification Rate</p>
                                <p class="text-2xl font-bold text-indigo-600">{{ $phoneStats['verification_rate'] ?? 0 }}%</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verification Progress -->
                <div class="bg-gray-50 rounded-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold mb-4">Verification Progress</h3>
                    
                    <div class="w-full bg-gray-200 rounded-full h-4 mb-4">
                        <div class="bg-green-600 h-4 rounded-full transition-all duration-300" 
                             style="width: {{ $phoneStats['verification_rate'] ?? 0 }}%"></div>
                    </div>
                    
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>0%</span>
                        <span class="font-medium">{{ $phoneStats['verification_rate'] ?? 0 }}% Verified</span>
                        <span>100%</span>
                    </div>
                </div>

                <!-- Phone Statistics Summary -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Verification Summary -->
                    <div class="bg-white border rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Verification Summary</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Total Phones</span>
                                <span class="font-semibold">{{ number_format($phoneStats['total_phones'] ?? 0) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Verified</span>
                                <span class="font-semibold text-green-600">{{ number_format($phoneStats['verified_phones'] ?? 0) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Unverified</span>
                                <span class="font-semibold text-yellow-600">{{ number_format($phoneStats['unverified_phones'] ?? 0) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Verification Rate</span>
                                <span class="font-semibold text-blue-600">{{ $phoneStats['verification_rate'] ?? 0 }}%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Geographic Distribution -->
                    <div class="bg-white border rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-4">Geographic Distribution</h3>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Countries Represented</span>
                                <span class="font-semibold">{{ number_format($phoneStats['phone_countries'] ?? 0) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Recent Verifications</span>
                                <span class="font-semibold text-green-600">{{ number_format($phoneStats['recent_verifications'] ?? 0) }}</span>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Last 7 Days</span>
                                <span class="font-semibold text-blue-600">Active</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>