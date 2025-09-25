<div>
    <!-- Navigation Menu -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <!-- Game Logo -->
            <a class="navbar-brand" href="{{ route('game.dashboard') }}">
                <img src="{{ asset('img/logo.png') }}" alt="Travian" height="30" class="me-2">
                Travian Online
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Items -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.dashboard') ? 'active' : '' }}"
                           href="{{ route('game.dashboard') }}">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.village*') ? 'active' : '' }}"
                           href="{{ route('game.village', 1) }}">
                            <i class="fas fa-building"></i> Village
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.troops') ? 'active' : '' }}"
                           href="{{ route('game.troops') }}">
                            <i class="fas fa-shield-alt"></i> Troops
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.movements') ? 'active' : '' }}"
                           href="{{ route('game.movements') }}">
                            <i class="fas fa-route"></i> Movements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.alliance') ? 'active' : '' }}"
                           href="{{ route('game.alliance') }}">
                            <i class="fas fa-users"></i> Alliance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.quests') ? 'active' : '' }}"
                           href="{{ route('game.quests') }}">
                            <i class="fas fa-tasks"></i> Quests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.technology') ? 'active' : '' }}"
                           href="{{ route('game.technology') }}">
                            <i class="fas fa-flask"></i> Technology
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.reports') ? 'active' : '' }}"
                           href="{{ route('game.reports') }}">
                            <i class="fas fa-file-alt"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.map') ? 'active' : '' }}"
                           href="{{ route('game.map') }}">
                            <i class="fas fa-map"></i> Map
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.statistics') ? 'active' : '' }}"
                           href="{{ route('game.statistics') }}">
                            <i class="fas fa-chart-bar"></i> Statistics
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.battles') ? 'active' : '' }}"
                           href="{{ route('game.battles') }}">
                            <i class="fas fa-sword"></i> Battles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('game.market') ? 'active' : '' }}"
                           href="{{ route('game.market') }}">
                            <i class="fas fa-store"></i> Market
                        </a>
                    </li>
                </ul>

                <!-- Player Info -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="playerDropdown" role="button"
                           data-bs-toggle="dropdown">
                            <img src="{{ asset('img/hero/male/head/31x40/face/face0.png') }}" alt="Player"
                                 class="player-avatar me-2">
                            {{ $player->name ?? 'Player' }}
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog"></i> Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="{{ route('logout') }}"><i
                                       class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Real-time Status Bar -->
    <div class="status-bar">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <div class="real-time-indicator"></div>
                    <span>Live Updates Active</span>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Last Update: {{ $lastUpdate ?? 'Never' }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .navbar-brand img {
            max-height: 30px;
        }

        .player-avatar {
            width: 25px;
            height: 25px;
            border-radius: 50%;
        }

        .status-bar {
            background: #2c3e50;
            color: white;
            padding: 5px 0;
            font-size: 12px;
        }

        .real-time-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
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

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }

        .navbar-nav .nav-link {
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-menu {
            background-color: #2c3e50;
            border: 1px solid #34495e;
        }

        .dropdown-item {
            color: white;
            transition: all 0.3s ease;
        }

        .dropdown-item:hover {
            background-color: #34495e;
            color: white;
        }

        .dropdown-divider {
            border-color: #34495e;
        }
    </style>
@endpush
