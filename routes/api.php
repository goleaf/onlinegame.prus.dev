<?php

use App\Http\Controllers\Api\GameApiController;
use App\Http\Controllers\Api\WebSocketController;
use App\Http\Controllers\Game\AIController;
use App\Http\Controllers\Game\APIDocumentationController;
use App\Http\Controllers\Game\ArtifactController;
use App\Http\Controllers\Game\LarautilxController;
use App\Http\Controllers\Game\LarautilxDashboardController;
use App\Http\Controllers\Game\PlayerController;
use App\Http\Controllers\Game\SystemController;
use App\Http\Controllers\Game\TaskController;
use App\Http\Controllers\Game\UserController;
use App\Http\Controllers\Game\VillageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | API Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register API routes for your application. These
 * | routes are loaded by the RouteServiceProvider and all of them will
 * | be assigned to the "api" middleware group. Make something great!
 * |
 */

Route::middleware('auth:sanctum')->get('/user', [GameApiController::class, 'getUser']);

// Game API Routes - Core Game Functionality
Route::middleware('auth:sanctum')->prefix('game')->group(function () {
    // Basic game operations
    Route::get('/villages', [GameApiController::class, 'getVillages']);
    Route::post('/create-village', [GameApiController::class, 'createVillage']);
    Route::get('/village/{id}', [GameApiController::class, 'getVillage']);
    Route::post('/village/{id}/upgrade-building', [GameApiController::class, 'upgradeBuilding']);
    Route::get('/player/stats', [GameApiController::class, 'getPlayerStats']);
    
    // Player Management
    Route::prefix('players')->group(function () {
        Route::get('/', [PlayerController::class, 'index']);
        Route::post('/', [PlayerController::class, 'store']);
        Route::get('/with-stats', [PlayerController::class, 'withStats']);
        Route::get('/top', [PlayerController::class, 'top']);
        Route::get('/stats/{playerId}', [PlayerController::class, 'stats']);
        Route::get('/{id}', [PlayerController::class, 'show']);
        Route::put('/{id}', [PlayerController::class, 'update']);
        Route::delete('/{id}', [PlayerController::class, 'destroy']);
        Route::put('/{playerId}/status', [PlayerController::class, 'updateStatus']);
    });
    
    // Village Management
    Route::prefix('villages')->group(function () {
        Route::get('/', [VillageController::class, 'index']);
        Route::post('/', [VillageController::class, 'store']);
        Route::get('/with-stats', [VillageController::class, 'withStats']);
        Route::get('/by-coordinates', [VillageController::class, 'byCoordinates']);
        Route::get('/{id}', [VillageController::class, 'show']);
        Route::put('/{id}', [VillageController::class, 'update']);
        Route::delete('/{id}', [VillageController::class, 'destroy']);
        Route::get('/{villageId}/details', [VillageController::class, 'details']);
        Route::get('/{villageId}/nearby', [VillageController::class, 'nearby']);
        Route::put('/{villageId}/resources', [VillageController::class, 'updateResources']);
    });
    
    // User Management
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/with-game-stats', [UserController::class, 'withGameStats']);
        Route::get('/activity-stats', [UserController::class, 'activityStats']);
        Route::get('/online', [UserController::class, 'online']);
        Route::get('/search', [UserController::class, 'search']);
        Route::post('/bulk-update-status', [UserController::class, 'bulkUpdateStatus']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::get('/{userId}/details', [UserController::class, 'details']);
        Route::get('/{userId}/feature-toggles', [UserController::class, 'featureToggles']);
        Route::get('/{userId}/game-history', [UserController::class, 'gameHistory']);
        Route::put('/{userId}/status', [UserController::class, 'updateStatus']);
    });
    
    // Task Management
    Route::prefix('tasks')->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store']);
        Route::get('/with-stats', [TaskController::class, 'withStats']);
        Route::get('/overdue', [TaskController::class, 'overdue']);
        Route::get('/player/{playerId}/stats', [TaskController::class, 'playerStats']);
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy']);
        Route::post('/{taskId}/start', [TaskController::class, 'start']);
        Route::post('/{taskId}/complete', [TaskController::class, 'complete']);
        Route::put('/{taskId}/progress', [TaskController::class, 'updateProgress']);
    });
    
    // AI Integration
    Route::prefix('ai')->group(function () {
        Route::get('/status', [AIController::class, 'getStatus']);
        Route::post('/village-names', [AIController::class, 'generateVillageNames']);
        Route::post('/alliance-names', [AIController::class, 'generateAllianceNames']);
        Route::post('/quest-description', [AIController::class, 'generateQuestDescription']);
        Route::post('/player-message', [AIController::class, 'generatePlayerMessage']);
        Route::post('/battle-report', [AIController::class, 'generateBattleReport']);
        Route::post('/world-event', [AIController::class, 'generateWorldEvent']);
        Route::post('/strategy-suggestion', [AIController::class, 'generateStrategySuggestion']);
        Route::post('/custom-content', [AIController::class, 'generateCustomContent']);
        Route::post('/switch-provider', [AIController::class, 'switchProvider']);
    });
    
    // System Management
    Route::prefix('system')->group(function () {
        Route::get('/health', [SystemController::class, 'health']);
        Route::get('/config', [SystemController::class, 'config']);
        Route::put('/config', [SystemController::class, 'updateConfig']);
        Route::get('/logs', [SystemController::class, 'logs']);
        Route::get('/metrics', [SystemController::class, 'metrics']);
        Route::get('/scheduled-tasks', [SystemController::class, 'scheduledTasks']);
        Route::post('/clear-caches', [SystemController::class, 'clearCaches']);
    });
    
    // Larautilx Integration
    Route::prefix('larautilx')->group(function () {
        Route::get('/status', [LarautilxController::class, 'getStatus']);
        Route::get('/docs', [LarautilxController::class, 'getDocs']);
        Route::post('/test/caching', [LarautilxController::class, 'testCaching']);
        Route::post('/test/filtering', [LarautilxController::class, 'testFiltering']);
        Route::post('/test/pagination', [LarautilxController::class, 'testPagination']);
        Route::get('/cache/stats', [LarautilxDashboardController::class, 'getCacheStats']);
        Route::post('/cache/clear', [LarautilxDashboardController::class, 'clearCache']);
        Route::post('/cache/player/clear', [LarautilxDashboardController::class, 'clearPlayerCache']);
        Route::post('/cache/village/clear', [LarautilxDashboardController::class, 'clearVillageCache']);
        Route::post('/cache/world/clear', [LarautilxDashboardController::class, 'clearWorldCache']);
        Route::get('/dashboard', [LarautilxDashboardController::class, 'getDashboardData']);
        Route::get('/integration-summary', [LarautilxDashboardController::class, 'getIntegrationSummary']);
        Route::post('/test-components', [LarautilxDashboardController::class, 'testComponents']);
    });
    
    // Artifact System
    Route::prefix('artifacts')->group(function () {
        Route::get('/', [ArtifactController::class, 'index']);
        Route::post('/', [ArtifactController::class, 'store']);
        Route::get('/server-wide', [ArtifactController::class, 'serverWide']);
        Route::post('/generate-random', [ArtifactController::class, 'generateRandom']);
        Route::get('/{id}', [ArtifactController::class, 'show']);
        Route::put('/{id}', [ArtifactController::class, 'update']);
        Route::delete('/{id}', [ArtifactController::class, 'destroy']);
        Route::post('/{id}/activate', [ArtifactController::class, 'activate']);
        Route::post('/{id}/deactivate', [ArtifactController::class, 'deactivate']);
        Route::get('/{id}/effects', [ArtifactController::class, 'effects']);
    });
});
