<div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <h3 class="text-lg font-semibold mb-4">Address Information</h3>
    
    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Address Line 1 -->
            <div class="md:col-span-2">
                <label for="address_line_1" class="block text-sm font-medium text-gray-700 mb-1">
                    Address Line 1
                </label>
                <input 
                    type="text" 
                    id="address_line_1"
                    wire:model="address_line_1"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address_line_1') border-red-500 @enderror"
                    placeholder="Street address"
                >
                @error('address_line_1')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Address Line 2 -->
            <div class="md:col-span-2">
                <label for="address_line_2" class="block text-sm font-medium text-gray-700 mb-1">
                    Address Line 2
                </label>
                <input 
                    type="text" 
                    id="address_line_2"
                    wire:model="address_line_2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('address_line_2') border-red-500 @enderror"
                    placeholder="Apartment, suite, unit, building, floor, etc."
                >
                @error('address_line_2')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- City -->
            <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">
                    City
                </label>
                <input 
                    type="text" 
                    id="city"
                    wire:model="city"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('city') border-red-500 @enderror"
                    placeholder="City"
                >
                @error('city')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- State -->
            <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-1">
                    State/Province
                </label>
                <input 
                    type="text" 
                    id="state"
                    wire:model="state"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('state') border-red-500 @enderror"
                    placeholder="State or Province"
                >
                @error('state')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Postal Code -->
            <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">
                    Postal Code
                </label>
                <input 
                    type="text" 
                    id="postal_code"
                    wire:model="postal_code"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('postal_code') border-red-500 @enderror"
                    placeholder="Postal code"
                >
                @error('postal_code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Country -->
            <div>
                <label for="country" class="block text-sm font-medium text-gray-700 mb-1">
                    Country
                </label>
                <select 
                    id="country"
                    wire:model="country"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('country') border-red-500 @enderror"
                >
                    <option value="US">United States</option>
                    <option value="CA">Canada</option>
                    <option value="GB">United Kingdom</option>
                    <option value="DE">Germany</option>
                    <option value="FR">France</option>
                    <option value="ES">Spain</option>
                    <option value="IT">Italy</option>
                    <option value="NL">Netherlands</option>
                    <option value="AU">Australia</option>
                    <option value="JP">Japan</option>
                </select>
                @error('country')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out"
            >
                Save Address
            </button>
        </div>

        <!-- Success Message -->
        @if (session()->has('message'))
            <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                {{ session('message') }}
            </div>
        @endif
    </form>
</div>
