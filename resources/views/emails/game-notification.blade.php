<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} - Game Notification</title>
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
            border-bottom: 2px solid #4a90e2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .game-logo {
            font-size: 24px;
            font-weight: bold;
            color: #4a90e2;
            margin-bottom: 10px;
        }
        .notification-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .notification-content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            border-left: 4px solid #4a90e2;
            margin-bottom: 20px;
        }
        .player-info {
            background-color: #e8f4fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            color: #666;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            background-color: #4a90e2;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
        .button:hover {
            background-color: #357abd;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .data-table th,
        .data-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="game-logo">🎮 Online Game</div>
            <h1 class="notification-title">{{ $title }}</h1>
        </div>

        <div class="player-info">
            <strong>Player:</strong> {{ $player->name }}<br>
            <strong>Email:</strong> {{ $player->user->email ?? 'N/A' }}<br>
            <strong>Date:</strong> {{ now()->format('Y-m-d H:i:s') }}
        </div>

        <div class="notification-content">
            <h3>Notification Details:</h3>
            <p>{{ $message }}</p>

            @if(!empty($data) && is_array($data))
                <h4>Additional Information:</h4>
                <table class="data-table">
                    @foreach($data as $key => $value)
                        @if(!is_null($value) && $value !== '')
                            <tr>
                                <th>{{ ucfirst(str_replace('_', ' ', $key)) }}:</th>
                                <td>
                                    @if(is_array($value))
                                        {{ json_encode($value) }}
                                    @else
                                        {{ $value }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </table>
            @endif
        </div>

        <div style="text-align: center;">
            <a href="{{ config('app.url') }}/game" class="button">
                Play Game
            </a>
        </div>

        <div class="footer">
            <p>This is an automated notification from the Online Game system.</p>
            <p>If you no longer wish to receive these notifications, you can disable them in your game settings.</p>
            <p>&copy; {{ date('Y') }} Online Game. All rights reserved.</p>
        </div>
    </div>
</body>
</html>