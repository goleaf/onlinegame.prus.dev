<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Bulk Operations - Travian Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Phone Bulk Operations</h1>
                <div class="flex space-x-4">
                    <a href="{{ route('phone-stats') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Statistics
                    </a>
                    <a href="{{ route('phone-search') }}" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                        Phone Search
                    </a>
                    <a href="{{ route('profile') }}" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600">
                        Profile
                    </a>
                    @auth
                        <a href="{{ route('game.dashboard') }}" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                            Dashboard
                        </a>
                    @endauth
                </div>
            </div>
            
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <h2 class="text-lg font-semibold text-yellow-800 mb-2">Bulk Operations</h2>
                <p class="text-yellow-700">
                    Perform bulk operations on phone numbers including export, validation, formatting, 
                    CSV updates, and deletion. Select users and choose an operation to execute.
                </p>
            </div>
            
            @livewire('phone-bulk-operations')
        </div>
    </div>
    
    @livewireScripts
</body>
</html>
