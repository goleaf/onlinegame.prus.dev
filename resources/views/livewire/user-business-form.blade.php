<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <h3 class="text-lg font-semibold mb-4">Business Information</h3>
    
    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Business Name -->
            <div class="md:col-span-2">
                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Name *
                </label>
                <input 
                    type="text" 
                    id="business_name"
                    wire:model="business_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_name') border-red-500 @enderror"
                    placeholder="Enter business name"
                >
                @error('business_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Type -->
            <div>
                <label for="business_type" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Type *
                </label>
                <select 
                    id="business_type"
                    wire:model="business_type"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_type') border-red-500 @enderror"
                >
                    <option value="">Select business type</option>
                    <option value="sole_proprietorship">Sole Proprietorship</option>
                    <option value="partnership">Partnership</option>
                    <option value="corporation">Corporation</option>
                    <option value="llc">Limited Liability Company (LLC)</option>
                </select>
                @error('business_type')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tax Number -->
            <div>
                <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">
                    Tax Number
                </label>
                <input 
                    type="text" 
                    id="tax_number"
                    wire:model="tax_number"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tax_number') border-red-500 @enderror"
                    placeholder="Tax identification number"
                >
                @error('tax_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Registration Number -->
            <div>
                <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">
                    Registration Number
                </label>
                <input 
                    type="text" 
                    id="registration_number"
                    wire:model="registration_number"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('registration_number') border-red-500 @enderror"
                    placeholder="Business registration number"
                >
                @error('registration_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Address -->
            <div class="md:col-span-2">
                <label for="business_address" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Address
                </label>
                <textarea 
                    id="business_address"
                    wire:model="business_address"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_address') border-red-500 @enderror"
                    placeholder="Business address"
                ></textarea>
                @error('business_address')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business City -->
            <div>
                <label for="business_city" class="block text-sm font-medium text-gray-700 mb-1">
                    Business City
                </label>
                <input 
                    type="text" 
                    id="business_city"
                    wire:model="business_city"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_city') border-red-500 @enderror"
                    placeholder="City"
                >
                @error('business_city')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Country -->
            <div>
                <label for="business_country" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Country *
                </label>
                <select 
                    id="business_country"
                    wire:model="business_country"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_country') border-red-500 @enderror"
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
                @error('business_country')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Phone -->
            <div>
                <label for="business_phone" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Phone
                </label>
                <input 
                    type="tel" 
                    id="business_phone"
                    wire:model="business_phone"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_phone') border-red-500 @enderror"
                    placeholder="Business phone number"
                >
                @error('business_phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Email -->
            <div>
                <label for="business_email" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Email
                </label>
                <input 
                    type="email" 
                    id="business_email"
                    wire:model="business_email"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_email') border-red-500 @enderror"
                    placeholder="business@example.com"
                >
                @error('business_email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Website -->
            <div>
                <label for="business_website" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Website
                </label>
                <input 
                    type="url" 
                    id="business_website"
                    wire:model="business_website"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_website') border-red-500 @enderror"
                    placeholder="https://www.example.com"
                >
                @error('business_website')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Business Description -->
            <div class="md:col-span-2">
                <label for="business_description" class="block text-sm font-medium text-gray-700 mb-1">
                    Business Description
                </label>
                <textarea 
                    id="business_description"
                    wire:model="business_description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('business_description') border-red-500 @enderror"
                    placeholder="Describe your business"
                ></textarea>
                @error('business_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Banking Information Section -->
        <div class="mt-8 border-t pt-6">
            <h4 class="text-md font-medium text-gray-800 mb-4">Banking Information</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- IBAN -->
                <div>
                    <label for="bank_iban" class="block text-sm font-medium text-gray-700 mb-1">
                        IBAN
                    </label>
                    <input 
                        type="text" 
                        id="bank_iban"
                        wire:model="bank_iban"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('bank_iban') border-red-500 @enderror"
                        placeholder="GB82 WEST 1234 5698 7654 32"
                    >
                    @error('bank_iban')
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
        </div>

        <!-- Product Information Section -->
        <div class="mt-8 border-t pt-6">
            <h4 class="text-md font-medium text-gray-800 mb-4">Product Information</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- ISBN -->
                <div>
                    <label for="product_isbn" class="block text-sm font-medium text-gray-700 mb-1">
                        ISBN (if applicable)
                    </label>
                    <input 
                        type="text" 
                        id="product_isbn"
                        wire:model="product_isbn"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('product_isbn') border-red-500 @enderror"
                        placeholder="978-0-123456-78-9"
                    >
                    @error('product_isbn')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- EAN -->
                <div>
                    <label for="product_ean" class="block text-sm font-medium text-gray-700 mb-1">
                        EAN (if applicable)
                    </label>
                    <input 
                        type="text" 
                        id="product_ean"
                        wire:model="product_ean"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('product_ean') border-red-500 @enderror"
                        placeholder="1234567890123"
                    >
                    @error('product_ean')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-8">
            <button 
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out"
            >
                Save Business Information
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
