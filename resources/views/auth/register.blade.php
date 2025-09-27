<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Register - Travian Online</title>

    <!-- Travian CSS -->
    <link rel="stylesheet" href="{{ asset('css/travian_basics.css') }}">
    <link rel="stylesheet" href="{{ asset('css/map.css') }}">
    <link rel="stylesheet" href="{{ asset('css/infobox.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/acp.css') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="{{ basset('https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap') }}" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        body {
            background: url('{{ asset('img/background.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Arial', sans-serif;
        }

        .register-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .register-box {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            max-width: 450px;
            width: 100%;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: #8B4513;
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .register-header p {
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

        .password-strength {
            margin-top: 5px;
            font-size: 0.9em;
        }

        .password-strength.weak {
            color: #f44336;
        }

        .password-strength.fair {
            color: #ff9800;
        }

        .password-strength.good {
            color: #4caf50;
        }

        .password-strength.strong {
            color: #2196f3;
        }

        .register-button {
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

        .register-button:hover {
            background: linear-gradient(45deg, #A0522D, #8B4513);
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #8B4513;
            text-decoration: none;
            font-weight: bold;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-box">
            <div class="register-header">
                <h1>🎮 Travian Online</h1>
                <p>Join the Ancient World</p>
            </div>

            @if ($errors->any())
                <div class="error-message">
                    <strong>Registration Failed!</strong>
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

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                    <div id="password-strength" class="password-strength"></div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>

                <button type="submit" class="register-button">
                    🚀 Create Account
                </button>
            </form>

            <div class="login-link">
                <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthDiv.textContent = '';
                return;
            }

            let strength = 0;
            let feedback = '';

            // Length check
            if (password.length >= 8) strength++;
            else feedback += 'At least 8 characters. ';

            // Uppercase check
            if (/[A-Z]/.test(password)) strength++;
            else feedback += 'Include uppercase letters. ';

            // Lowercase check
            if (/[a-z]/.test(password)) strength++;
            else feedback += 'Include lowercase letters. ';

            // Number check
            if (/\d/.test(password)) strength++;
            else feedback += 'Include numbers. ';

            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            else feedback += 'Include special characters. ';

            // Display strength
            switch (strength) {
                case 0:
                case 1:
                    strengthDiv.textContent = 'Weak: ' + feedback;
                    strengthDiv.className = 'password-strength weak';
                    break;
                case 2:
                    strengthDiv.textContent = 'Fair: ' + feedback;
                    strengthDiv.className = 'password-strength fair';
                    break;
                case 3:
                    strengthDiv.textContent = 'Good: ' + feedback;
                    strengthDiv.className = 'password-strength good';
                    break;
                case 4:
                case 5:
                    strengthDiv.textContent = 'Strong password!';
                    strengthDiv.className = 'password-strength strong';
                    break;
            }
        });
    </script>

    @livewireScripts
</body>

</html>
