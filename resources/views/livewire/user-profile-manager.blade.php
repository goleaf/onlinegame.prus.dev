<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
    <h3 class="text-2xl font-semibold mb-6">User Profile Management</h3>
    
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile Information -->
        <div>
            <h4 class="text-lg font-semibold mb-4">Profile Information</h4>
            
            <form wire:submit.prevent="updateProfile">
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Full Name
                    </label>
                    <input 
                        wire:model="name" 
                        type="text" 
                        id="name" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email Address
                    </label>
                    <input 
                        wire:model="email" 
                        type="email" 
                        id="email" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <button 
                    type="submit" 
                    class="w-full bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    Update Profile
                </button>
            </form>
        </div>

        <!-- Phone Information -->
        <div>
            <h4 class="text-lg font-semibold mb-4">Phone Information</h4>
            
            @if($user->phone)
                <div class="mb-4 p-4 bg-gray-50 rounded-md">
                    <p><strong>Phone:</strong> {{ $user->phone }}</p>
                    <p><strong>Country:</strong> {{ $user->phone_country }}</p>
                    @if($user->phone_e164)
                        <p><strong>E164:</strong> {{ $user->phone_e164 }}</p>
                    @endif
                    @if($user->phone_normalized)
                        <p><strong>Normalized:</strong> {{ $user->phone_normalized }}</p>
                    @endif
                </div>
            @endif

            <button 
                wire:click="togglePhoneForm"
                class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 mb-4"
            >
                {{ $showPhoneForm ? 'Cancel' : 'Update Phone Number' }}
            </button>

            @if($showPhoneForm)
                <form wire:submit.prevent="updatePhone">
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
                                wire:click="formatPhone"
                                class="px-4 py-2 bg-blue-500 text-white rounded-r-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Format
                            </button>
                        </div>
                        @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                    >
                        Update Phone Number
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- User Statistics -->
    @if($user->exists)
        <div class="mt-8 p-6 bg-gray-50 rounded-md">
            <h4 class="text-lg font-semibold mb-4">User Statistics</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $user->created_at->format('M Y') }}</p>
                    <p class="text-sm text-gray-600">Member Since</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-green-600">{{ $user->players->count() }}</p>
                    <p class="text-sm text-gray-600">Game Players</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ $user->isOnline() ? 'Online' : 'Offline' }}</p>
                    <p class="text-sm text-gray-600">Status</p>
                </div>
            </div>
        </div>
    @endif
</div>