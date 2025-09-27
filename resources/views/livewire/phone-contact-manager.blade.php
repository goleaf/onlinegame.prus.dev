<div class="max-w-7xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-2xl font-semibold">Phone Contact Manager</h3>
        <div class="flex space-x-2">
            <button 
                wire:click="toggleAddContact"
                class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600"
            >
                Add Contact
            </button>
            <button 
                wire:click="selectAllContacts"
                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
            >
                Select All
            </button>
            <button 
                wire:click="clearSelection"
                class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600"
            >
                Clear
            </button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <!-- Add Contact Form -->
    @if($showAddContact)
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-lg font-semibold mb-4">Add New Contact</h4>
            <form wire:submit.prevent="addContact">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Player</label>
                        <select wire:model="newContact.player_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select Player</option>
                            @foreach($availablePlayers as $player)
                                <option value="{{ $player->id }}">{{ $player->name }} ({{ $player->world->name ?? 'No World' }})</option>
                            @endforeach
                        </select>
                        @error('newContact.player_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Name</label>
                        <input wire:model="newContact.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('newContact.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Country</label>
                        <select wire:model="newContact.phone_country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="US">United States</option>
                            <option value="CA">Canada</option>
                            <option value="GB">United Kingdom</option>
                            <option value="DE">Germany</option>
                            <option value="FR">France</option>
                            <option value="IT">Italy</option>
                            <option value="ES">Spain</option>
                            <option value="AU">Australia</option>
                            <option value="BE">Belgium</option>
                            <option value="NL">Netherlands</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                        <input wire:model="newContact.phone" type="tel" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter phone number">
                        @error('newContact.phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select wire:model="newContact.category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="friend">Friend</option>
                            <option value="alliance">Alliance</option>
                            <option value="enemy">Enemy</option>
                            <option value="neutral">Neutral</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <textarea wire:model="newContact.notes" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" rows="2"></textarea>
                    </div>
                </div>

                <div class="mt-4 flex space-x-2">
                    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                        Add Contact
                    </button>
                    <button type="button" wire:click="toggleAddContact" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Filters -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h4 class="text-lg font-semibold mb-4">Filters</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                <input 
                    wire:model.live.debounce.300ms="searchTerm" 
                    type="text" 
                    placeholder="Search by name, email, or phone..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Alliance</label>
                <select wire:model.live="filterByAlliance" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Alliances</option>
                    @foreach($availableAlliances as $alliance)
                        <option value="{{ $alliance->id }}">{{ $alliance->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">World</label>
                <select wire:model.live="filterByWorld" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Worlds</option>
                    @foreach($availableWorlds as $world)
                        <option value="{{ $world->id }}">{{ $world->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    @if(!empty($selectedContacts))
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-lg font-semibold mb-2">Bulk Actions ({{ count($selectedContacts) }} selected)</h4>
            <div class="flex space-x-2">
                <button 
                    wire:click="sendBulkSms"
                    class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600"
                >
                    Send SMS
                </button>
                <button 
                    wire:click="exportContacts"
                    class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600"
                >
                    Export CSV
                </button>
            </div>
        </div>
    @endif

    <!-- Contacts Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border border-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input 
                            type="checkbox" 
                            wire:model="selectAll"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alliance</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">World</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($contacts as $contact)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <input 
                                type="checkbox" 
                                wire:model="selectedContacts" 
                                value="{{ $contact->id }}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            >
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $contact->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $contact->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $contact->phone }}</div>
                            @if($contact->phone_e164)
                                <div class="text-xs text-gray-500">E164: {{ $contact->phone_e164 }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($contact->player)
                                <div class="text-sm font-medium text-gray-900">{{ $contact->player->name }}</div>
                                <div class="text-sm text-gray-500">{{ $contact->player->points ?? 0 }} points</div>
                            @else
                                <span class="text-sm text-gray-500">No player</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($contact->player && $contact->player->alliance)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $contact->player->alliance->name }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500">No alliance</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($contact->player && $contact->player->world)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    {{ $contact->player->world->name }}
                                </span>
                            @else
                                <span class="text-sm text-gray-500">No world</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $contacts->links() }}
    </div>
</div>

<script>
document.addEventListener('livewire:init', () => {
    Livewire.on('download-csv', (event) => {
        const { data, filename } = event;
        const blob = new Blob([atob(data)], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
});
</script>