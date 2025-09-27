<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Travian Game')</title>

    <!-- Travian CSS -->
    <link href="{{ asset('css/travian_basics.css') }}" rel="stylesheet">

    <!-- Livewire Styles -->
    @livewireStyles
    
    <!-- Formello Styles -->
    @formelloStyles

    <!-- Custom Game Styles -->
    <style>
        body {
            background: #f0f0f0;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .game-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
        }

        .game-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .game-content {
            padding: 1rem;
        }

        .resource-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .resource-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .resource-icon {
            width: 20px;
            height: 20px;
        }

        .village-info {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }

        .building-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .building-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 1rem;
            text-align: center;
            transition: transform 0.2s;
        }

        .building-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .auto-refresh-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            padding: 0.5rem;
            border-radius: 5px;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="game-container">
        <!-- Game Navigation -->
        @livewire('game.game-navigation')

        <!-- Resource Bar -->
        <div class="resource-bar">
            @if ($currentVillage)
                @foreach ($currentVillage->resources as $resource)
                    <div class="resource-item">
                        <img src="{{ asset('img/resources/' . $resource->type . '.gif') }}"
                             alt="{{ ucfirst($resource->type) }}" class="resource-icon">
                        <span><strong>{{ number_format($resource->amount) }}</strong></span>
                        <small>(+{{ $resource->production_rate }}/sec)</small>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Game Content -->
        <div class="game-content">
            @yield('content')
        </div>
    </div>

    <!-- Auto-refresh indicator -->
    @if ($autoRefresh ?? false)
        <div class="auto-refresh-indicator">
            Auto-refresh: {{ $refreshInterval ?? 5 }}s
        </div>
    @endif

    <!-- Fathom Analytics -->
    @if(app(\App\Services\FathomAnalytics::class)->isConfigured())
        {!! app(\App\Services\FathomAnalytics::class)->getCompleteSetup() !!}
    @endif

    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Formello Scripts -->
    @formelloScripts

    <!-- Travian JavaScript -->
    <script src="{{ asset('js/default/jquery-3.2.1.min.js') }}"></script>

    <!-- Auto-refresh script -->
    @if ($autoRefresh ?? false)
        <script>
            setInterval(function() {
                @this.call('refreshGameData');
            }, {{ ($refreshInterval ?? 5) * 1000 }});
        </script>
    @endif

    <!-- Game tick processing script -->
    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('gameTickProcessed', function() {
                // Show success notification
                console.log('Game tick processed successfully');
                // You can add a toast notification here
            });

            Livewire.on('gameTickError', function(data) {
                // Show error notification
                console.error('Game tick error:', data.message);
                // You can add an error toast here
            });

            // Fathom Analytics event tracking
            Livewire.on('fathom-track', function(data) {
                if (typeof fathom !== 'undefined') {
                    if (data.value !== undefined) {
                        fathom.trackGoal(data.name, data.value);
                    } else {
                        fathom.trackGoal(data.name);
                    }
                }
            });
        });
    </script>
</body>

</html>
