<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Travian Game')</title>
    
    <!-- SEO Metadata -->
    @metadata
    
    <!-- Travian Game Assets -->
    <link rel="stylesheet" href="{{ asset('game/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('game/travian.css') }}">
    
    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Custom Game Styles -->
    <style>
        body {
            background: #1a1a1a;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        
        .game-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }
        
        .game-header {
            background: #2c3e50;
            padding: 10px 20px;
            border-bottom: 2px solid #3498db;
        }
        
        .game-content {
            padding: 20px;
        }
        
        .village-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .village-card {
            background: #34495e;
            border: 1px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            transition: transform 0.3s ease;
        }
        
        .village-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .resource-bar {
            display: flex;
            justify-content: space-between;
            background: #2c3e50;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .resource-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .resource-icon {
            width: 20px;
            height: 20px;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <!-- Game Header -->
        <div class="game-header">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0">@text('game.title', 'Travian Game')</h1>
                <div class="d-flex gap-3">
                    <span>@text('game.welcome', 'Welcome'), {{ auth()->user()->name }}</span>
                    <a href="{{ route('logout') }}" class="btn btn-outline-light btn-sm">@text('game.logout', 'Logout')</a>
                </div>
            </div>
        </div>
        
        <!-- Resource Bar -->
        <div class="resource-bar">
            <div class="resource-item">
                <img src="{{ asset('game/wood.png') }}" alt="Wood" class="resource-icon">
                <span>@text('game.resources.wood', 'Wood'): 1000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('game/clay.png') }}" alt="Clay" class="resource-icon">
                <span>@text('game.resources.clay', 'Clay'): 1000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('game/iron.png') }}" alt="Iron" class="resource-icon">
                <span>@text('game.resources.iron', 'Iron'): 1000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('game/crop.png') }}" alt="Crop" class="resource-icon">
                <span>@text('game.resources.crop', 'Crop'): 1000</span>
            </div>
        </div>
        
        <!-- Game Content -->
        <div class="game-content">
            @yield('content')
        </div>
    </div>
    
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Game JavaScript -->
    <script src="{{ asset('game/travian.js') }}"></script>
</body>
</html>
