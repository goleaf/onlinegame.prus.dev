<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - Travian Online</title>

    <!-- Travian CSS -->
    <link rel="stylesheet" href="{{ asset('css/travian_basics.css') }}">
    <link rel="stylesheet" href="{{ asset('css/map.css') }}">
    <link rel="stylesheet" href="{{ asset('css/infobox.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/acp.css') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body {
            background: url('{{ asset('img/background.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Arial', sans-serif;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #8B4513;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .login-header p {
            color: #666;
            font-size: 1.1em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #8B4513;
        }

        .login-button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #8B4513, #A0522D);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .login-button:hover {
            background: linear-gradient(45deg, #A0522D, #8B4513);
        }

        .admin-credentials {
            background: #f0f8ff;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .admin-credentials h3 {
            color: #4CAF50;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .admin-credentials p {
            margin: 5px 0;
            color: #333;
        }

        .error-message {
            background: #ffebee;
            border: 2px solid #f44336;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            color: #d32f2f;
        }

        .success-message {
            background: #e8f5e8;
            border: 2px solid #4CAF50;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 20px;
            color: #2e7d32;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🎮 Travian Online</h1>
                <p>Welcome to the Ancient World</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    <strong>Login Failed!</strong>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('success'))
                <div class="success-message">
                    {{ session('success') }}
                </div>
            @endif

            <div class="admin-credentials">
                <h3>🔑 Admin Credentials</h3>
                <p><strong>Email:</strong> admin@example.com</p>
                <p><strong>Password:</strong> password123</p>
                <p><em>These credentials are pre-filled below for easy access!</em></p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="admin@example.com" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" value="password123" required>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" checked>
                        Remember Me
                    </label>
                </div>

                <button type="submit" class="login-button">
                    🚀 Enter the Game
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p style="color: #666; font-size: 0.9em;">
                    Ready to build your empire?<br>
                    <strong>Click "Enter the Game" to start playing!</strong>
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>

</html>
