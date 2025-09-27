<?php

use App\Http\Controllers\Game\GameController;
use App\Http\Controllers\Game\PlayerController;
use App\Http\Controllers\Game\SecureGameController;
use App\Http\Controllers\Game\TaskController;
use App\Http\Controllers\Game\VillageController;
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

// Larautilx CRUD API routes
Route::middleware(['auth', 'game.auth'])->prefix('game/api')->group(function () {
    // Player management
    Route::get('/players', [PlayerController::class, 'getAllRecords'])->name('game.api.players.index');
    Route::get('/players/{id}', [PlayerController::class, 'getRecordById'])->name('game.api.players.show');
    Route::post('/players', [PlayerController::class, 'storeRecord'])->name('game.api.players.store');
    Route::put('/players/{id}', [PlayerController::class, 'updateRecord'])->name('game.api.players.update');
    Route::delete('/players/{id}', [PlayerController::class, 'deleteRecord'])->name('game.api.players.destroy');
    
    // Player advanced routes
    Route::get('/players/stats/{playerId}', [PlayerController::class, 'getPlayerStats'])->name('game.api.players.stats');
    Route::get('/players/top', [PlayerController::class, 'getTopPlayers'])->name('game.api.players.top');
    Route::get('/players/with-stats', [PlayerController::class, 'getPlayersWithStats'])->name('game.api.players.with-stats');
    Route::put('/players/{playerId}/status', [PlayerController::class, 'updateStatus'])->name('game.api.players.status');

    // Village management
    Route::get('/villages', [VillageController::class, 'getAllRecords'])->name('game.api.villages.index');
    Route::get('/villages/{id}', [VillageController::class, 'getRecordById'])->name('game.api.villages.show');
    Route::post('/villages', [VillageController::class, 'storeRecord'])->name('game.api.villages.store');
    Route::put('/villages/{id}', [VillageController::class, 'updateRecord'])->name('game.api.villages.update');
    Route::delete('/villages/{id}', [VillageController::class, 'deleteRecord'])->name('game.api.villages.destroy');
    
    // Village advanced routes
    Route::get('/villages/with-stats', [VillageController::class, 'getVillagesWithStats'])->name('game.api.villages.with-stats');
    Route::get('/villages/by-coordinates', [VillageController::class, 'getVillagesByCoordinates'])->name('game.api.villages.by-coordinates');
    Route::get('/villages/{villageId}/details', [VillageController::class, 'getVillageDetails'])->name('game.api.villages.details');
    Route::get('/villages/{villageId}/nearby', [VillageController::class, 'getNearbyVillages'])->name('game.api.villages.nearby');
    Route::put('/villages/{villageId}/resources', [VillageController::class, 'updateResources'])->name('game.api.villages.resources');

    // Task management
    Route::get('/tasks', [TaskController::class, 'getAllRecords'])->name('game.api.tasks.index');
    Route::get('/tasks/{id}', [TaskController::class, 'getRecordById'])->name('game.api.tasks.show');
    Route::post('/tasks', [TaskController::class, 'storeRecord'])->name('game.api.tasks.store');
    Route::put('/tasks/{id}', [TaskController::class, 'updateRecord'])->name('game.api.tasks.update');
    Route::delete('/tasks/{id}', [TaskController::class, 'deleteRecord'])->name('game.api.tasks.destroy');
    
    // Task advanced routes
    Route::get('/tasks/with-stats', [TaskController::class, 'getTasksWithStats'])->name('game.api.tasks.with-stats');
    Route::post('/tasks/{taskId}/start', [TaskController::class, 'startTask'])->name('game.api.tasks.start');
    Route::post('/tasks/{taskId}/complete', [TaskController::class, 'completeTask'])->name('game.api.tasks.complete');
    Route::put('/tasks/{taskId}/progress', [TaskController::class, 'updateProgress'])->name('game.api.tasks.progress');
    Route::get('/tasks/player/{playerId}/stats', [TaskController::class, 'getPlayerTaskStats'])->name('game.api.tasks.player-stats');
    Route::get('/tasks/overdue', [TaskController::class, 'getOverdueTasks'])->name('game.api.tasks.overdue');
});

// Error pages
Route::get('/game/no-player', function () {
    return view('game.no-player');
})->name('game.no-player');

Route::get('/game/suspended', function () {
    return view('game.suspended');
})->name('game.suspended');
