<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Statistics Dashboard - Travian Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Phone Statistics Dashboard</h1>
                <div class="flex space-x-4">
                    <a href="{{ route('phone-search') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Phone Search
                    </a>
                    <a href="{{ route('profile') }}" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                        Profile
                    </a>
                    <a href="{{ route('phone-test') }}" class="bg-purple-500 text-white px-4 py-2 rounded-md hover:bg-purple-600">
                        Phone Test
                    </a>
                    @auth
                        <a href="{{ route('game.dashboard') }}" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                            Dashboard
                        </a>
                    @endauth
                </div>
            </div>
            
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h2 class="text-lg font-semibold text-blue-800 mb-2">Phone Statistics Overview</h2>
                <p class="text-blue-700">
                    This dashboard provides comprehensive statistics about phone number usage across the platform, 
                    including coverage percentages, country distribution, and format statistics.
                </p>
            </div>
            
            @livewire('phone-stats-dashboard')
        </div>
    </div>
    
    @livewireScripts
</body>
</html>

