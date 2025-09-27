<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Number Test</title>
    <script src="{{ basset('https://cdn.tailwindcss.com') }}"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold">Laravel Phone Integration Test</h1>
                    <div class="flex space-x-4">
                        <a href="{{ route('profile') }}" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                            Profile Manager
                        </a>
                        <a href="{{ route('phone-search') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                            Phone Search
                        </a>
                        @auth
                            <a href="{{ route('game.dashboard') }}" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600">
                                Dashboard
                            </a>
                        @endauth
                    </div>
                </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div>
                    <h2 class="text-xl font-semibold mb-4">Phone Number Form</h2>
                    @livewire('user-phone-form')
                </div>
                
                <div>
                    <h2 class="text-xl font-semibold mb-4">Features</h2>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <ul class="space-y-2">
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Phone number validation by country
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Automatic formatting (E164, National, International)
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Country-specific validation rules
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Livewire integration
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Model observer for auto-formatting
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Multiple phone number storage formats
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                User registration with phone numbers
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Profile management system
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                Advanced phone number search
                            </li>
                            <li class="flex items-center">
                                <span class="text-green-500 mr-2">✓</span>
                                User management integration
                            </li>
                        </ul>
                        
                        <div class="mt-6">
                            <h3 class="font-semibold mb-2">Usage Examples:</h3>
                            <div class="text-sm text-gray-600 space-y-1">
                                <p><strong>US:</strong> +1 (555) 123-4567</p>
                                <p><strong>GB:</strong> +44 20 7946 0958</p>
                                <p><strong>DE:</strong> +49 30 12345678</p>
                                <p><strong>FR:</strong> +33 1 23 45 67 89</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @livewireScripts
</body>
</html>
