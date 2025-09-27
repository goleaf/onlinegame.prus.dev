<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <!-- SEO Metadata -->
    @metadata

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="{{ basset('https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap') }}" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- WireUI Styles -->
    @wireUiStyles

    <!-- Formello Styles -->
    @formelloStyles

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Figtree', sans-serif;
        }
        
        .admin-sidebar {
            min-height: calc(100vh - 4rem);
        }
        
        .admin-content {
            min-height: calc(100vh - 4rem);
        }
    </style>
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <a href="{{ route('admin.updater') }}" class="text-xl font-bold text-gray-900 dark:text-white">
                                {{ config('app.name', 'Laravel') }} Admin
                            </a>
                        </div>
                        <div class="hidden md:ml-6 md:flex md:space-x-8">
                            <a href="{{ route('admin.updater') }}" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-900 dark:text-white focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out">
                                Updater
                            </a>
                        </div>
                    </div>
                    
                    <div class="flex items-center">
                        <div class="flex items-center space-x-4">
                            @auth
                                <span class="text-sm text-gray-700 dark:text-gray-300">
                                    Welcome, {{ Auth::user()->name }}
                                </span>
                                <form method="POST" action="{{ route('logout') }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                        Logout
                                    </button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="text-sm text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                    Login
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- WireUI Scripts -->
    @wireUiScripts

    <!-- Formello Scripts -->
    @formelloScripts

    <!-- Livewire Alert Scripts -->
    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('alert', function(data) {
                // Handle Livewire alerts if needed
                console.log('Alert:', data);
            });
        });
    </script>
</body>

</html>
