<div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Address Information</h2>
        @if (!$isEditing)
            <button wire:click="toggleEdit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Edit Address
            </button>
        @endif
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Street Address -->
            <div class="md:col-span-2">
                <label for="street" class="block text-sm font-medium text-gray-700 mb-2">
                    Street Address
                </label>
                <input type="text" 
                       id="street" 
                       wire:model="street" 
                       @disabled(!$isEditing)
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @if(!$isEditing) bg-gray-100 @endif">
                @error('street') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- City -->
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                    City
                </label>
                <input type="text" 
                       id="city" 
                       wire:model="city" 
                       @disabled(!$isEditing)
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @if(!$isEditing) bg-gray-100 @endif">
                @error('city') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Postal Code -->
            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-2">
                    Postal Code
                </label>
                <input type="text" 
                       id="postal_code" 
                       wire:model="postal_code" 
                       @disabled(!$isEditing)
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @if(!$isEditing) bg-gray-100 @endif">
                @error('postal_code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- State -->
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                    State/Province
                </label>
                <input type="text" 
                       id="state" 
                       wire:model="state" 
                       @disabled(!$isEditing)
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @if(!$isEditing) bg-gray-100 @endif">
                @error('state') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Country -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-2">
                    Country
                </label>
                <input type="text" 
                       id="country" 
                       wire:model="country" 
                       @disabled(!$isEditing)
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @if(!$isEditing) bg-gray-100 @endif">
                @error('country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <!-- Phone -->
            <div class="md:col-span-2">
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                    Phone Number (Optional)
                </label>
                <input type="tel" 
                       id="phone" 
                       wire:model="phone" 
                       @disabled(!$isEditing)
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @if(!$isEditing) bg-gray-100 @endif">
                @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        @if ($isEditing)
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" 
                        wire:click="cancel"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                    Save Address
                </button>
            </div>
        @endif
    </form>

    <!-- Display current address when not editing -->
    @if (!$isEditing)
        <div class="mt-6 p-4 bg-gray-50 rounded-md">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Current Address</h3>
            <div class="text-gray-600">
                @if ($street || $city || $postal_code || $state || $country)
                    <p>{{ $street }}</p>
                    <p>{{ $city }}, {{ $state }} {{ $postal_code }}</p>
                    <p>{{ $country }}</p>
                    @if ($phone)
                        <p class="mt-2">Phone: {{ $phone }}</p>
                    @endif
                @else
                    <p class="text-gray-500 italic">No address information provided</p>
                @endif
            </div>
        </div>
    @endif
</div>