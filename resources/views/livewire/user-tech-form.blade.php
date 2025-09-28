<div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <h3 class="text-lg font-semibold mb-4">Technical Information</h3>
    
    <form wire:submit.prevent="save">
        <div class="space-y-6">
            <!-- API Token -->
            <div>
                <label for="api_token" class="block text-sm font-medium text-gray-700 mb-1">
                    API Token
                </label>
                <input 
                    type="text" 
                    id="api_token"
                    wire:model="api_token"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('api_token') border-red-500 @enderror"
                    placeholder="Enter API token"
                >
                @error('api_token')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Webhook URL -->
            <div>
                <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-1">
                    Webhook URL
                </label>
                <input 
                    type="url" 
                    id="webhook_url"
                    wire:model="webhook_url"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('webhook_url') border-red-500 @enderror"
                    placeholder="https://example.com/webhook"
                >
                @error('webhook_url')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Integration Key -->
            <div>
                <label for="integration_key" class="block text-sm font-medium text-gray-700 mb-1">
                    Integration Key
                </label>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        id="integration_key"
                        wire:model="integration_key"
                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('integration_key') border-red-500 @enderror"
                        placeholder="Integration key (ULID format)"
                        readonly
                    >
                    <button 
                        type="button"
                        wire:click="generateApiToken"
                        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        Generate
                    </button>
                </div>
                @error('integration_key')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- JWT Secret -->
            <div>
                <label for="jwt_secret" class="block text-sm font-medium text-gray-700 mb-1">
                    JWT Secret
                </label>
                <textarea 
                    id="jwt_secret"
                    wire:model="jwt_secret"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('jwt_secret') border-red-500 @enderror"
                    placeholder="Enter JWT secret"
                ></textarea>
                @error('jwt_secret')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Base64 Encoded Data -->
            <div>
                <label for="base64_encoded_data" class="block text-sm font-medium text-gray-700 mb-1">
                    Base64 Encoded Data
                </label>
                <textarea 
                    id="base64_encoded_data"
                    wire:model="base64_encoded_data"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('base64_encoded_data') border-red-500 @enderror"
                    placeholder="Enter Base64 encoded data"
                ></textarea>
                @error('base64_encoded_data')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Data URI -->
            <div>
                <label for="data_uri" class="block text-sm font-medium text-gray-700 mb-1">
                    Data URI
                </label>
                <textarea 
                    id="data_uri"
                    wire:model="data_uri"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('data_uri') border-red-500 @enderror"
                    placeholder="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=="
                ></textarea>
                @error('data_uri')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tech Description -->
            <div>
                <label for="tech_description" class="block text-sm font-medium text-gray-700 mb-1">
                    Technical Description
                </label>
                <textarea 
                    id="tech_description"
                    wire:model="tech_description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('tech_description') border-red-500 @enderror"
                    placeholder="Describe technical requirements or preferences"
                ></textarea>
                @error('tech_description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Preferences -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Preferred Language -->
                <div>
                    <label for="preferred_language" class="block text-sm font-medium text-gray-700 mb-1">
                        Preferred Language *
                    </label>
                    <select 
                        id="preferred_language"
                        wire:model="preferred_language"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('preferred_language') border-red-500 @enderror"
                    >
                        <option value="en">English</option>
                        <option value="es">Spanish</option>
                        <option value="fr">French</option>
                        <option value="de">German</option>
                        <option value="it">Italian</option>
                        <option value="pt">Portuguese</option>
                        <option value="ru">Russian</option>
                        <option value="ja">Japanese</option>
                        <option value="ko">Korean</option>
                        <option value="zh">Chinese</option>
                    </select>
                    @error('preferred_language')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Timezone -->
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-1">
                        Timezone *
                    </label>
                    <select 
                        id="timezone"
                        wire:model="timezone"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('timezone') border-red-500 @enderror"
                    >
                        <option value="UTC">UTC</option>
                        <option value="America/New_York">Eastern Time (ET)</option>
                        <option value="America/Chicago">Central Time (CT)</option>
                        <option value="America/Denver">Mountain Time (MT)</option>
                        <option value="America/Los_Angeles">Pacific Time (PT)</option>
                        <option value="Europe/London">London (GMT)</option>
                        <option value="Europe/Paris">Paris (CET)</option>
                        <option value="Europe/Berlin">Berlin (CET)</option>
                        <option value="Asia/Tokyo">Tokyo (JST)</option>
                        <option value="Asia/Shanghai">Shanghai (CST)</option>
                    </select>
                    @error('timezone')
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
                Save Technical Information
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

