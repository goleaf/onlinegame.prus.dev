<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Travian Online Game')</title>

    <!-- SEO Metadata -->
    @metadata

    <!-- Travian CSS -->
    <link rel="stylesheet" href="{{ asset('css/travian_basics.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/travianGeneral.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/travianLayout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/travianBuildings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/travianVillage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/layout/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/general/general.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/general/resources.css') }}">
    <link rel="stylesheet" href="{{ asset('css/travian/general/button.css') }}">

    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Formello Styles -->
    @formelloStyles

    <!-- Additional Styles -->
    <style>
        .game-container {
            background: #f0f0f0;
            min-height: 100vh;
        }

        .resource-bar {
            background: #8B4513;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .resource-item {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }

        .resource-icon {
            width: 20px;
            height: 20px;
            margin-right: 5px;
        }

        .main-content {
            display: flex;
            min-height: calc(100vh - 60px);
        }

        .sidebar {
            width: 200px;
            background: #2c3e50;
            color: white;
            padding: 20px;
        }

        .content-area {
            flex: 1;
            padding: 20px;
            background: white;
        }

        .village-info {
            background: #34495e;
            color: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .building-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .building-slot {
            width: 100px;
            height: 100px;
            border: 2px solid #ddd;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .building-slot:hover {
            border-color: #3498db;
            background: #e8f4f8;
        }

        .building-slot.occupied {
            background: #e8f5e8;
            border-color: #27ae60;
        }

        .building-icon {
            width: 60px;
            height: 60px;
        }

        .nav-menu {
            list-style: none;
            padding: 0;
        }

        .nav-menu li {
            margin: 10px 0;
        }

        .nav-menu a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
            border-radius: 3px;
            transition: background 0.3s ease;
        }

        .nav-menu a:hover {
            background: #34495e;
        }

        .nav-menu a.active {
            background: #3498db;
        }

        .status-bar {
            background: #34495e;
            color: white;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .player-info {
            display: flex;
            align-items: center;
        }

        .player-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .game-stats {
            display: flex;
            gap: 20px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 12px;
            color: #bdc3c7;
        }

        .real-time-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #27ae60;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .loading {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }

        .error {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }

        .success {
            background: #27ae60;
            color: white;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }

        .info {
            background: #3498db;
            color: white;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
    </style>

    @stack('styles')
</head>

<body>
    <div class="game-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <div class="player-info">
                <img src="{{ asset('travian-img/hero/male/head/31x40/face/face0.png') }}" alt="Player"
                     class="player-avatar">
                <div>
                    <div class="player-name">{{ auth()->user()->name ?? 'Player' }}</div>
                    <div class="player-level">Level 1</div>
                </div>
            </div>

            <div class="game-stats">
                <div class="stat-item">
                    <div class="stat-value">1,234</div>
                    <div class="stat-label">Points</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">3</div>
                    <div class="stat-label">Villages</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">Roman</div>
                    <div class="stat-label">Tribe</div>
                </div>
            </div>

            <div class="real-time-indicator"></div>
            <span>Live Updates</span>
        </div>

        <!-- Resource Bar -->
        <div class="resource-bar">
            <div class="resource-item">
                <img src="{{ asset('travian-img/r/1.gif') }}" alt="Wood" class="resource-icon">
                <span id="wood-amount">1,000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('travian-img/r/2.gif') }}" alt="Clay" class="resource-icon">
                <span id="clay-amount">1,000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('travian-img/r/3.gif') }}" alt="Iron" class="resource-icon">
                <span id="iron-amount">1,000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('travian-img/r/4.gif') }}" alt="Crop" class="resource-icon">
                <span id="crop-amount">1,000</span>
            </div>
            <div class="resource-item">
                <img src="{{ asset('travian-img/r/5.gif') }}" alt="Population" class="resource-icon">
                <span id="population-amount">100</span>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Sidebar Navigation -->
            <div class="sidebar">
                <ul class="nav-menu">
                    <li><a href="{{ route('game.dashboard') }}"
                           class="{{ request()->routeIs('game.dashboard') ? 'active' : '' }}">🏠 Dashboard</a></li>
                    <li><a href="{{ route('game.village', 1) }}"
                           class="{{ request()->routeIs('game.village*') ? 'active' : '' }}">🏘️ Village</a></li>
                    <li><a href="{{ route('game.troops') }}"
                           class="{{ request()->routeIs('game.troops') ? 'active' : '' }}">⚔️ Troops</a></li>
                    <li><a href="{{ route('game.movements') }}"
                           class="{{ request()->routeIs('game.movements') ? 'active' : '' }}">🚶 Movements</a></li>
                    <li><a href="{{ route('game.alliance') }}"
                           class="{{ request()->routeIs('game.alliance') ? 'active' : '' }}">🤝 Alliance</a></li>
                    <li><a href="{{ route('game.quests') }}"
                           class="{{ request()->routeIs('game.quests') ? 'active' : '' }}">📜 Quests</a></li>
                    <li><a href="{{ route('game.technology') }}"
                           class="{{ request()->routeIs('game.technology') ? 'active' : '' }}">🔬 Technology</a></li>
                    <li><a href="{{ route('game.reports') }}"
                           class="{{ request()->routeIs('game.reports') ? 'active' : '' }}">📊 Reports</a></li>
                    <li><a href="{{ route('game.map') }}"
                           class="{{ request()->routeIs('game.map') ? 'active' : '' }}">🗺️ Map</a></li>
                    <li><a href="{{ route('admin.dashboard') }}"
                           class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">⚙️ Admin</a></li>
                    <li><a href="{{ route('admin.updater') }}"
                           class="{{ request()->routeIs('admin.updater') ? 'active' : '' }}">🔄 Updater</a></li>
                    <li><a href="{{ route('game.statistics') }}"
                           class="{{ request()->routeIs('game.statistics') ? 'active' : '' }}">📈 Statistics</a></li>
                    <li><a href="{{ route('game.battles') }}"
                           class="{{ request()->routeIs('game.battles') ? 'active' : '' }}">⚔️ Battles</a></li>
                    <li><a href="{{ route('game.market') }}"
                           class="{{ request()->routeIs('game.market') ? 'active' : '' }}">🏪 Market</a></li>
                </ul>
            </div>

            <!-- Content Area -->
            <div class="content-area">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- Travian JavaScript -->
    <script src="{{ asset('js/default/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('js/default/jquery.md5.min.js') }}"></script>
    <script src="{{ asset('js/default/jquery.scrollbar.min.js') }}"></script>
    <script src="{{ asset('js/default/gsap/minified/TweenMax.min.js') }}"></script>
    <script src="{{ asset('js/Game/General/General.js') }}"></script>

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Formello Scripts -->
    @formelloScripts

    <!-- Game JavaScript -->
    <script>
        // Real-time resource updates
        function updateResources() {
            // This will be called by Livewire components
            console.log('Updating resources...');
        }

        // Initialize game
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Travian Game Initialized');

            // Set up real-time updates
            setInterval(updateResources, 5000); // Update every 5 seconds
        });

        // Livewire event listeners
        document.addEventListener('livewire:load', function() {
            console.log('Livewire loaded');
        });

        document.addEventListener('livewire:update', function() {
            console.log('Livewire updated');
        });
    </script>

    @stack('scripts')
</body>

</html>
