<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@text('game.title', 'Travian Game - Laravel Edition')</title>

    <!-- SEO Metadata -->
    @metadata

    <!-- Travian Game Assets -->
    <link rel="stylesheet" href="{{ asset('game/styles.css') }}">
    <link rel="stylesheet" href="{{ asset('game/travian.css') }}">

    <!-- Bootstrap CSS -->
    <link href="{{ basset('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css') }}" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="{{ basset('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css') }}" rel="stylesheet">

    <!-- Livewire Styles -->
    @livewireStyles

    <!-- Custom Game Styles -->
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a, #2c3e50);
            color: #fff;
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .game-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #2c3e50, #34495e);
        }

        .game-header {
            background: linear-gradient(90deg, #2c3e50, #3498db);
            padding: 15px 20px;
            border-bottom: 3px solid #e74c3c;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .game-content {
            padding: 20px;
            min-height: calc(100vh - 120px);
        }

        .resource-bar {
            background: linear-gradient(90deg, #34495e, #2c3e50);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .resource-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 8px;
            border: 1px solid rgba(52, 152, 219, 0.3);
            transition: all 0.3s ease;
        }

        .resource-item:hover {
            background: rgba(52, 152, 219, 0.2);
            transform: translateY(-2px);
        }

        .resource-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
        }

        .village-card {
            background: linear-gradient(135deg, #34495e, #2c3e50);
            border: 2px solid #3498db;
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .village-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
            border-color: #e74c3c;
        }

        .btn-game {
            background: linear-gradient(45deg, #3498db, #2980b9);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .btn-game:hover {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        .game-title {
            background: linear-gradient(45deg, #e74c3c, #f39c12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, .3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="game-container">
        <!-- Game Header -->
        <div class="game-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="game-title mb-0">
                            <i class="fas fa-crown"></i> @text('game.header.title', 'Travian Game - Laravel Edition')
                        </h1>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="d-flex justify-content-end align-items-center gap-3">
                            <span class="badge bg-success">
                                <i class="fas fa-user"></i> {{ auth()->user()->name ?? text('game.guest', 'Guest') }}
                            </span>
                            <a href="{{ route('logout') }}" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-sign-out-alt"></i> @text('game.logout', 'Logout')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resource Bar -->
        <div class="container-fluid">
            <div class="resource-bar">
                <div class="row">
                    <div class="col-md-3">
                        <div class="resource-item">
                            <div class="resource-icon" style="background: #8B4513;"></div>
                            <div>
                                <strong>@text('game.resources.wood', 'Wood')</strong><br>
                                <span id="wood-amount">1000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="resource-item">
                            <div class="resource-icon" style="background: #CD853F;"></div>
                            <div>
                                <strong>@text('game.resources.clay', 'Clay')</strong><br>
                                <span id="clay-amount">1000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="resource-item">
                            <div class="resource-icon" style="background: #708090;"></div>
                            <div>
                                <strong>@text('game.resources.iron', 'Iron')</strong><br>
                                <span id="iron-amount">1000</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="resource-item">
                            <div class="resource-icon" style="background: #32CD32;"></div>
                            <div>
                                <strong>@text('game.resources.crop', 'Crop')</strong><br>
                                <span id="crop-amount">1000</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game Content -->
        <div class="game-content">
            <div class="container-fluid">
                <!-- Welcome Message -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-dark border-primary">
                            <div class="card-body text-center">
                                <h2 class="card-title">
                                    <i class="fas fa-rocket"></i> @text('game.welcome.title', 'Welcome to Travian!')
                                </h2>
                                <p class="card-text">
                                    @text('game.welcome.description', 'Build your empire, manage resources, and conquer the world in this classic strategy game.')
                                </p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="{{ route('game.dashboard') }}" class="btn btn-game">
                                        <i class="fas fa-home"></i> @text('game.dashboard', 'Dashboard')
                                    </a>
                                    <a href="{{ route('game.map') }}" class="btn btn-game">
                                        <i class="fas fa-map"></i> @text('game.world_map', 'World Map')
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Game Features -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-dark border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-village fa-3x text-success mb-3"></i>
                                <h5>@text('game.features.villages.title', 'Build Villages')</h5>
                                <p>@text('game.features.villages.description', 'Create and manage multiple villages across the world.')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x text-warning mb-3"></i>
                                <h5>@text('game.features.alliances.title', 'Form Alliances')</h5>
                                <p>@text('game.features.alliances.description', 'Join forces with other players to dominate the world.')</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-dark border-danger">
                            <div class="card-body text-center">
                                <i class="fas fa-sword fa-3x text-danger mb-3"></i>
                                <h5>@text('game.features.conquer.title', 'Conquer & Expand')</h5>
                                <p>@text('game.features.conquer.description', 'Attack enemies and expand your territory.')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="{{ basset('https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Game JavaScript -->
    <script>
        // Auto-update resources every 30 seconds
        setInterval(function() {
            updateResources();
        }, 30000);

        function updateResources() {
            // This would typically fetch from the server
            console.log('Updating resources...');
        }

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.transition = 'transform 0.3s ease';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>

</html>
