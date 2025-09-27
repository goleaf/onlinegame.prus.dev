<div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-semibold">Phone Number Search</h3>
        <div class="text-sm text-gray-600">
            Total users with phone numbers: {{ $users->total() }}
        </div>
    </div>

    <!-- Search Filters -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        <div>
            <label for="searchTerm" class="block text-sm font-medium text-gray-700 mb-2">
                Search Term
            </label>
            <input 
                wire:model.debounce.300ms="searchTerm" 
                type="text" 
                id="searchTerm"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Enter phone number..."
            >
        </div>

        <div>
            <label for="searchType" class="block text-sm font-medium text-gray-700 mb-2">
                Search Type
            </label>
            <select wire:model="searchType" id="searchType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="all">All Formats</option>
                <option value="phone">Raw Phone</option>
                <option value="normalized">Normalized</option>
                <option value="e164">E164 Format</option>
            </select>
        </div>

        <div>
            <label for="countryFilter" class="block text-sm font-medium text-gray-700 mb-2">
                Country Filter
            </label>
            <select wire:model="countryFilter" id="countryFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">All Countries</option>
                @foreach($countries as $country)
                    <option value="{{ $country }}">{{ $country }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end">
            <button 
                wire:click="clearSearch"
                class="w-full bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500"
            >
                Clear Search
            </button>
        </div>
    </div>

    <!-- Results Table -->
    @if($users->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Formats</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Game Player</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->phone }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $user->phone_country }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="space-y-1">
                                    @if($user->phone_e164)
                                        <div><strong>E164:</strong> {{ $user->phone_e164 }}</div>
                                    @endif
                                    @if($user->phone_normalized)
                                        <div><strong>Normalized:</strong> {{ $user->phone_normalized }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->player)
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ $user->player->name }}
                                    </span>
                                @else
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        No Player
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->created_at->format('M j, Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $users->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <div class="text-gray-500 text-lg">
                @if($searchTerm || $countryFilter)
                    No users found matching your search criteria.
                @else
                    No users with phone numbers found.
                @endif
            </div>
            @if($searchTerm || $countryFilter)
                <button 
                    wire:click="clearSearch"
                    class="mt-4 bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                >
                    Clear Search
                </button>
            @endif
        </div>
    @endif
</div>