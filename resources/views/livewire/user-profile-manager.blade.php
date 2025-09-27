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

        <!-- Password Management -->
        <div>
            <h4 class="text-lg font-semibold mb-4">Password Management</h4>
            
            <button 
                wire:click="togglePasswordForm"
                class="w-full bg-orange-500 text-white py-2 px-4 rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 mb-4"
            >
                {{ $showPasswordForm ? 'Cancel' : 'Change Password' }}
            </button>

            @if($showPasswordForm)
                <form wire:submit.prevent="updatePassword">
                    <div class="mb-4">
                        <label for="currentPassword" class="block text-sm font-medium text-gray-700 mb-2">
                            Current Password
                        </label>
                        <input 
                            wire:model="currentPassword" 
                            type="password" 
                            id="currentPassword" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        @error('currentPassword') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="newPassword" class="block text-sm font-medium text-gray-700 mb-2">
                            New Password
                        </label>
                        <input 
                            wire:model="newPassword" 
                            type="password" 
                            id="newPassword" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        <div id="password-strength" class="password-strength mt-2"></div>
                        @error('newPassword') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="newPasswordConfirmation" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password
                        </label>
                        <input 
                            wire:model="newPasswordConfirmation" 
                            type="password" 
                            id="newPasswordConfirmation" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required
                        >
                        @error('newPasswordConfirmation') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-orange-500 text-white py-2 px-4 rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500"
                    >
                        Update Password
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

<style>
.password-strength {
    font-size: 0.875rem;
    font-weight: 500;
    padding: 0.5rem;
    border-radius: 0.375rem;
    text-align: center;
}

.password-strength.weak {
    background-color: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.password-strength.fair {
    background-color: #fffbeb;
    color: #d97706;
    border: 1px solid #fed7aa;
}

.password-strength.good {
    background-color: #f0fdf4;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.password-strength.strong {
    background-color: #f0f9ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('newPassword');
    const strengthDiv = document.getElementById('password-strength');
    
    if (passwordInput && strengthDiv) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                strengthDiv.className = 'password-strength mt-2';
                return;
            }

            let strength = 0;
            let feedback = '';

            // Length check
            if (password.length >= 8) strength++;
            else feedback += 'At least 8 characters. ';

            // Uppercase check
            if (/[A-Z]/.test(password)) strength++;
            else feedback += 'Include uppercase letters. ';

            // Lowercase check
            if (/[a-z]/.test(password)) strength++;
            else feedback += 'Include lowercase letters. ';

            // Number check
            if (/\d/.test(password)) strength++;
            else feedback += 'Include numbers. ';

            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback += 'Include special characters. ';

            // Display strength
            switch (strength) {
                case 0:
                case 1:
                    strengthDiv.textContent = 'Weak: ' + feedback;
                    strengthDiv.className = 'password-strength mt-2 weak';
                    break;
                case 2:
                    strengthDiv.textContent = 'Fair: ' + feedback;
                    strengthDiv.className = 'password-strength mt-2 fair';
                    break;
                case 3:
                    strengthDiv.textContent = 'Good: ' + feedback;
                    strengthDiv.className = 'password-strength mt-2 good';
                    break;
                case 4:
                case 5:
                    strengthDiv.textContent = 'Strong password!';
                    strengthDiv.className = 'password-strength mt-2 strong';
                    break;
            }
        });
    }
});
</script>