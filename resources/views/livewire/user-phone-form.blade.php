<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-lg font-semibold mb-4">Phone Number Form</h3>
    
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

    <form wire:submit.prevent="save">
        <div class="mb-4">
            <label for="phone_country" class="block text-sm font-medium text-gray-700 mb-2">
                Country
            </label>
            <select wire:model="phone_country" id="phone_country" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            @error('phone_country') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                Phone Number
            </label>
            <div class="flex">
                <input 
                    wire:model="phone" 
                    type="tel" 
                    id="phone" 
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter phone number"
                >
                <button 
                    type="button" 
                    wire:click.prevent="formatPhone"
                    class="px-4 py-2 bg-blue-500 text-white rounded-r-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Format
                </button>
            </div>
            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="flex space-x-2">
            <button 
                type="submit" 
                class="flex-1 bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
            >
                Save Phone Number
            </button>
        </div>
    </form>

    @if($user->exists && $user->phone)
        <div class="mt-6 p-4 bg-gray-50 rounded-md">
            <h4 class="font-medium mb-2">Phone Number Information:</h4>
            <p><strong>Raw:</strong> {{ $user->phone }}</p>
            <p><strong>Country:</strong> {{ $user->phone_country }}</p>
            <p><strong>Normalized:</strong> {{ $user->phone_normalized }}</p>
            <p><strong>National:</strong> {{ $user->phone_national }}</p>
            <p><strong>E164:</strong> {{ $user->phone_e164 }}</p>
            
            @if($user->phone)
                <div class="mt-2">
                    <p><strong>Formatted International:</strong> {{ phone($user->phone, $user->phone_country)->formatInternational() }}</p>
                    <p><strong>Formatted National:</strong> {{ phone($user->phone, $user->phone_country)->formatNational() }}</p>
                </div>
            @endif
        </div>
    @endif
</div>
