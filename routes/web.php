<?php

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |
 */

use App\Http\Controllers\Game\GameController;
use App\Models\User;

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Phone form test route
Route::get('/phone-test', function () {
    return view('phone-test');
});

// User profile management route
Route::get('/profile', function () {
    return view('user-profile');
})->middleware('auth');

// Phone search route
Route::get('/phone-search', function () {
    return view('phone-search');
})->middleware('auth');

// Phone statistics dashboard
Route::get('/phone-stats', function () {
    return view('phone-stats');
})->middleware('auth');

// Phone bulk operations
Route::get('/phone-bulk-operations', function () {
    return view('phone-bulk-operations');
})->middleware('auth');

// Include auth routes
require __DIR__ . '/auth.php';

// Include game routes
require __DIR__ . '/game.php';

// Temporary debug route to test login
Route::get('/debug-login', function () {
    $email = 'goleaf@gmail.com';
    $password = 'goleaf';

    if (\Auth::attempt(['email' => $email, 'password' => $password])) {
        $user = \Auth::user();
        $capital = $user->capital;

        if ($capital) {
            return redirect("/game/city/{$capital->city_id}");
        } else {
            return 'No capital city found for user';
        }
    } else {
        return 'Login failed - invalid credentials';
    }
});

// Debug route to test GameController directly
Route::get('/debug-game', [GameController::class, 'index']);

// Query Enrich Demo Route
Route::get('/query-enrich-demo', function () {
    return view('livewire.game.query-enrich-demo');
})->middleware('auth')->name('query-enrich-demo');

// Debug route to test game dashboard
Route::get('/debug-game-dashboard', function () {
    try {
        $user = \Auth::user();
        if (! $user) {
            return 'No authenticated user';
        }

        $player = \App\Models\Game\Player::where('user_id', $user->id)->first();
        if (! $player) {
            return 'No player found for user: ' . $user->email;
        }

        return 'Player found: ' . $player->name . ' (ID: ' . $player->id . ')';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
});

// Simple game dashboard for testing
Route::get('/game-simple', function () {
    return view('game.simple-dashboard');
});

// Auth::routes();

// Laravel Decomposer route - full functionality
Route::get('decompose', function () {
    try {
        $composerArray = \Lubusin\Decomposer\Decomposer::getComposerArray();
        $packages = \Lubusin\Decomposer\Decomposer::getPackagesAndDependencies(array_reverse($composerArray['require']));
        $version = \Lubusin\Decomposer\Decomposer::getDecomposerVersion($composerArray, $packages);
        $laravelEnv = \Lubusin\Decomposer\Decomposer::getLaravelEnv($version);
        $serverEnv = \Lubusin\Decomposer\Decomposer::getServerEnv();
        $serverExtras = \Lubusin\Decomposer\Decomposer::getServerExtras();
        $laravelExtras = \Lubusin\Decomposer\Decomposer::getLaravelExtras();
        $extraStats = \Lubusin\Decomposer\Decomposer::getExtraStats();
        
        $svgIcons = [
            "composer" => \Lubusin\Decomposer\Decomposer::svg('composer', 'h-5'),
            "statusTrue" => \Lubusin\Decomposer\Decomposer::svg('status_true', 'h-5'),
            "statusFalse" => \Lubusin\Decomposer\Decomposer::svg('status_false', 'h-5'),
            "laravelIcon" => \Lubusin\Decomposer\Decomposer::svg('laravel_icon', 'h-5'),
            "serverIcon" => \Lubusin\Decomposer\Decomposer::svg('server', 'h-5')
        ];
        
        $formattedPackages = collect($packages)->map(function ($pkg) {
            return [
                'name' => $pkg['name'],
                'version' => $pkg['version'],
                'dependencies' => is_array($pkg['dependencies']) ? collect($pkg['dependencies'])->map(function ($v, $k) {
                    return ['name' => $k, 'version' => $v];
                })->values() : [['name' => 'N/A', 'version' => $pkg['dependencies']]]
            ];
        })->values();
        
        return view('Decomposer::app', compact('packages', 'laravelEnv', 'serverEnv', 'extraStats', 'serverExtras', 'laravelExtras', 'formattedPackages', 'svgIcons'));
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Laravel Decomposer Error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('decompose');

// Laravel Decomposer API route - JSON report
Route::get('decompose/json', function () {
    try {
        $report = \Lubusin\Decomposer\Decomposer::getReportAsArray();
        return response()->json([
            'success' => true,
            'data' => $report,
            'generated_at' => now()->toISOString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'generated_at' => now()->toISOString()
        ], 500);
    }
})->name('decompose.json');

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', App\Livewire\Admin\AdminDashboard::class)->name('dashboard');
    Route::get('/updater', App\Livewire\Admin\UpdaterComponent::class)->name('updater');
});
