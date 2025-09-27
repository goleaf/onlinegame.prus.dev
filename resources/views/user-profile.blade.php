<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Travian Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @livewireStyles
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">User Profile Management</h1>
                <div class="flex space-x-4">
                    <a href="{{ route('game.dashboard') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                        Back to Dashboard
                    </a>
                    <a href="{{ route('phone-test') }}" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                        Phone Test
                    </a>
                </div>
            </div>
            
            @livewire('user-profile-manager')
        </div>
    </div>
    
    @livewireScripts
</body>
</html>
