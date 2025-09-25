<?php

use App\Http\Controllers\Game\GameController;
use App\Http\Controllers\Game\SecureGameController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'game.auth'])->group(function () {
    // Main game dashboard
    Route::get('/game', [GameController::class, 'dashboard'])->name('game.dashboard');

    // Village management
    Route::get('/game/village/{village}', [GameController::class, 'village'])->name('game.village');

    // Troop management
    Route::get('/game/troops', [GameController::class, 'troops'])->name('game.troops');

    // Movement management
    Route::get('/game/movements', [GameController::class, 'movements'])->name('game.movements');

    // Alliance management
    Route::get('/game/alliance', [GameController::class, 'alliance'])->name('game.alliance');

    // Quest management
    Route::get('/game/quests', [GameController::class, 'quests'])->name('game.quests');

    // Technology management
    Route::get('/game/technology', [GameController::class, 'technology'])->name('game.technology');

    // Reports
    Route::get('/game/reports', [GameController::class, 'reports'])->name('game.reports');

    // Map
    Route::get('/game/map', [GameController::class, 'map'])->name('game.map');

    // Statistics
    Route::get('/game/statistics', [GameController::class, 'statistics'])->name('game.statistics');

    // Real-time updates
    Route::get('/game/real-time', [GameController::class, 'realTime'])->name('game.real-time');

    // Battle management
    Route::get('/game/battles', [GameController::class, 'battles'])->name('game.battles');

    // Market management
    Route::get('/game/market', [GameController::class, 'market'])->name('game.market');
});

// Secure API routes with rate limiting
Route::middleware(['auth', 'game.auth', 'game.rate_limit'])->group(function () {
    // Building management
    Route::post('/game/api/building/upgrade', [SecureGameController::class, 'upgradeBuilding'])->name('game.api.building.upgrade');

    // Troop management
    Route::post('/game/api/troops/train', [SecureGameController::class, 'trainTroops'])->name('game.api.troops.train');

    // Resource management
    Route::post('/game/api/resources/spend', [SecureGameController::class, 'spendResources'])->name('game.api.resources.spend');

    // Village data
    Route::get('/game/api/village/{villageId}', [SecureGameController::class, 'getVillageData'])->name('game.api.village.data');
});

// Error pages
Route::get('/game/no-player', function () {
    return view('game.no-player');
})->name('game.no-player');

Route::get('/game/suspended', function () {
    return view('game.suspended');
})->name('game.suspended');
