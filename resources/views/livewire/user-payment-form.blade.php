<div class="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <h3 class="text-lg font-semibold mb-4">Payment Information</h3>
    
    <form wire:submit.prevent="save">
        <!-- Payment Method Selection -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">Payment Method</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input 
                        type="radio" 
                        wire:model="payment_method" 
                        value="credit_card"
                        class="mr-2"
                    >
                    <span>Credit Card</span>
                </label>
                <label class="flex items-center">
                    <input 
                        type="radio" 
                        wire:model="payment_method" 
                        value="bank_transfer"
                        class="mr-2"
                    >
                    <span>Bank Transfer</span>
                </label>
            </div>
            @error('payment_method')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Credit Card Fields -->
        @if($payment_method === 'credit_card')
            <div class="space-y-4">
                <h4 class="text-md font-medium text-gray-800">Credit Card Information</h4>
                
                <!-- Credit Card Number -->
                <div>
                    <label for="credit_card_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Credit Card Number
                    </label>
                    <input 
                        type="text" 
                        id="credit_card_number"
                        wire:model="credit_card_number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('credit_card_number') border-red-500 @enderror"
                        placeholder="1234 5678 9012 3456"
                    >
                    @error('credit_card_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Expiry Date and CVV -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="credit_card_expiry" class="block text-sm font-medium text-gray-700 mb-1">
                            Expiry Date
                        </label>
                        <input 
                            type="text" 
                            id="credit_card_expiry"
                            wire:model="credit_card_expiry"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('credit_card_expiry') border-red-500 @enderror"
                            placeholder="MM/YY"
                        >
                        @error('credit_card_expiry')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="credit_card_cvv" class="block text-sm font-medium text-gray-700 mb-1">
                            CVV
                        </label>
                        <input 
                            type="text" 
                            id="credit_card_cvv"
                            wire:model="credit_card_cvv"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('credit_card_cvv') border-red-500 @enderror"
                            placeholder="123"
                        >
                        @error('credit_card_cvv')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        @endif

        <!-- Bank Transfer Fields -->
        @if($payment_method === 'bank_transfer')
            <div class="space-y-4">
                <h4 class="text-md font-medium text-gray-800">Bank Information</h4>
                
                <!-- Bank Name -->
                <div>
                    <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">
                        Bank Name
                    </label>
                    <input 
                        type="text" 
                        id="bank_name"
                        wire:model="bank_name"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('bank_name') border-red-500 @enderror"
                        placeholder="Bank name"
                    >
                    @error('bank_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- IBAN -->
                <div>
                    <label for="bank_account_iban" class="block text-sm font-medium text-gray-700 mb-1">
                        IBAN
                    </label>
                    <input 
                        type="text" 
                        id="bank_account_iban"
                        wire:model="bank_account_iban"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('bank_account_iban') border-red-500 @enderror"
                        placeholder="GB82 WEST 1234 5698 7654 32"
                    >
                    @error('bank_account_iban')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- BIC -->
                <div>
                    <label for="bank_bic" class="block text-sm font-medium text-gray-700 mb-1">
                        BIC/SWIFT Code
                    </label>
                    <input 
                        type="text" 
                        id="bank_bic"
                        wire:model="bank_bic"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('bank_bic') border-red-500 @enderror"
                        placeholder="DEUTDEFF"
                    >
                    @error('bank_bic')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @endif

        <!-- Submit Button -->
        <div class="mt-6">
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out"
            >
                Save Payment Information
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
