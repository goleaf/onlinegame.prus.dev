<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $notification['title'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .game-title {
            color: #007bff;
            font-size: 24px;
            margin: 0;
        }
        .notification-title {
            color: #333;
            font-size: 20px;
            margin: 10px 0;
        }
        .notification-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .notification-data {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 12px;
        }
        .priority-high {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .priority-urgent {
            border-left-color: #dc3545;
            background-color: #f5c6cb;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="game-title">{{ config('game.name', 'Online Strategy Game') }}</h1>
            <h2 class="notification-title">{{ $notification['title'] }}</h2>
        </div>

        <div class="notification-content {{ $notification['priority'] === 'high' ? 'priority-high' : '' }} {{ $notification['priority'] === 'urgent' ? 'priority-urgent' : '' }}">
            @if(isset($notification['data']['message']))
                <p>{{ $notification['data']['message'] }}</p>
            @endif

            @if(isset($notification['data']['title']))
                <h3>{{ $notification['data']['title'] }}</h3>
            @endif

            @if(isset($notification['data']['system_announcement']) && $notification['data']['system_announcement'])
                <div style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>📢 System Announcement</strong>
                </div>
            @endif

            @if(isset($notification['data']['battle_id']))
                <p><strong>Battle ID:</strong> {{ $notification['data']['battle_id'] }}</p>
            @endif

            @if(isset($notification['data']['village_id']))
                <p><strong>Village ID:</strong> {{ $notification['data']['village_id'] }}</p>
            @endif

            @if(isset($notification['data']['alliance_id']))
                <p><strong>Alliance ID:</strong> {{ $notification['data']['alliance_id'] }}</p>
            @endif

            @if(isset($notification['data']['building_id']))
                <p><strong>Building ID:</strong> {{ $notification['data']['building_id'] }}</p>
            @endif

            @if(isset($notification['data']['movement_id']))
                <p><strong>Movement ID:</strong> {{ $notification['data']['movement_id'] }}</p>
            @endif

            @if(isset($notification['data']['achievement_id']))
                <p><strong>Achievement ID:</strong> {{ $notification['data']['achievement_id'] }}</p>
            @endif

            @if(isset($notification['data']['quest_id']))
                <p><strong>Quest ID:</strong> {{ $notification['data']['quest_id'] }}</p>
            @endif

            @if(isset($notification['data']['resources']))
                <div style="background-color: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Resources:</strong>
                    <ul>
                        @foreach($notification['data']['resources'] as $resource => $amount)
                            <li>{{ ucfirst($resource) }}: {{ number_format($amount) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(isset($notification['data']['units']))
                <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Units:</strong>
                    <ul>
                        @foreach($notification['data']['units'] as $unit => $count)
                            <li>{{ ucfirst($unit) }}: {{ number_format($count) }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(isset($notification['data']['effect']))
                <div style="background-color: #e2e3e5; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Effect:</strong>
                    <ul>
                        @foreach($notification['data']['effect'] as $key => $value)
                            <li>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ is_numeric($value) ? number_format($value) : $value }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if($notification['priority'] === 'urgent')
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ url('/game') }}" class="button">🚨 Go to Game Now</a>
            </div>
        @else
            <div style="text-align: center; margin: 20px 0;">
                <a href="{{ url('/game') }}" class="button">Go to Game</a>
            </div>
        @endif

        <div class="notification-data">
            <strong>Notification Details:</strong><br>
            ID: {{ $notification['id'] }}<br>
            Type: {{ $notification['type'] }}<br>
            Priority: {{ ucfirst($notification['priority']) }}<br>
            Timestamp: {{ \Carbon\Carbon::parse($notification['timestamp'])->format('Y-m-d H:i:s T') }}
        </div>

        <div class="footer">
            <p>This is an automated notification from {{ config('game.name', 'Online Strategy Game') }}.</p>
            <p>If you no longer wish to receive email notifications, you can disable them in your game settings.</p>
            <p>© {{ date('Y') }} {{ config('game.name', 'Online Strategy Game') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
