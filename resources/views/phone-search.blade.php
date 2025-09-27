<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Number Search - Travian Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Phone Number Search & Management</h1>
                <div class="flex space-x-4">
                    <a href="{{ route('game.dashboard') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Dashboard
                    </a>
                    <a href="{{ route('profile') }}" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                        Profile
                    </a>
                    <a href="{{ route('phone-test') }}" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600">
                        Phone Test
                    </a>
                </div>
            </div>
            
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h2 class="text-lg font-semibold text-blue-800 mb-2">Phone Number Search Features</h2>
                <ul class="text-blue-700 space-y-1">
                    <li>• Search by raw phone number, normalized format, or E164 format</li>
                    <li>• Filter by country code</li>
                    <li>• View all phone number formats for each user</li>
                    <li>• Integration with game player information</li>
                    <li>• Real-time search with debouncing</li>
                </ul>
            </div>
            
            @livewire('phone-search-component')
        </div>
    </div>
    
    @livewireScripts
</body>
</html>
