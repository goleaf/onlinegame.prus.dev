<?php

use App\Http\Controllers\Game\AIController;
use App\Http\Controllers\Game\APIDocumentationController;
use App\Http\Controllers\Game\ChatController;
use App\Http\Controllers\Game\GameController;
use App\Http\Controllers\Game\LarautilxController;
use App\Http\Controllers\Game\LarautilxDashboardController;
use App\Http\Controllers\Game\PlayerController;
use App\Http\Controllers\Game\SecureGameController;
use App\Http\Controllers\Game\SystemController;
use App\Http\Controllers\Game\TaskController;
use App\Http\Controllers\Game\UserController;
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
    Route::get('/game/quests/{quest}', function ($quest) {
        return view('livewire.game.quest-detail', compact('quest'));
    })->name('game.quests.detail');

    // Technology management
    Route::get('/game/technology', [GameController::class, 'technology'])->name('game.technology');

    // Reports
    Route::get('/game/reports', [GameController::class, 'reports'])->name('game.reports');
    Route::get('/game/reports/{report}', function ($report) {
        return view('livewire.game.report-detail', compact('report'));
    })->name('game.reports.detail');

    // Map
    Route::get('/game/map', [GameController::class, 'map'])->name('game.map');
    
    // Advanced Map with Geographic Features
    Route::get('/game/advanced-map', function () {
        return view('game.advanced-map');
    })->name('game.advanced-map');

    // User Management
    Route::get('/game/users', function () {
        return view('game.user-management');
    })->name('game.users');

    // System Management
    Route::get('/game/system', function () {
        return view('game.system-management');
    })->name('game.system');

    // AI Management
    Route::get('/game/ai', function () {
        return view('game.ai-management');
    })->name('game.ai');

    // Larautilx Dashboard
    Route::get('/game/larautilx', function () {
        return view('game.larautilx-dashboard');
    })->name('game.larautilx');

    // API Documentation
    Route::get('/game/api-docs', function () {
        return view('game.api-documentation');
    })->name('game.api-docs');

    // Statistics
    Route::get('/game/statistics', [GameController::class, 'statistics'])->name('game.statistics');

    // Real-time updates
    Route::get('/game/real-time', [GameController::class, 'realTime'])->name('game.real-time');

    // Battle management
    Route::get('/game/battles', [GameController::class, 'battles'])->name('game.battles');

    // Market management
    Route::get('/game/market', [GameController::class, 'market'])->name('game.market');

    // Message management
    Route::get('/game/messages', function () {
        return view('game.messages');
    })->name('game.messages');

    // Chat management
    Route::get('/game/chat', function () {
        return view('game.chat');
    })->name('game.chat');
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
    
    // Task detail view
    Route::get('/game/tasks/{task}', function ($task) {
        return view('livewire.game.task-detail', compact('task'));
    })->name('game.tasks.detail');

    // Larautilx integration management
    Route::get('/larautilx/status', [LarautilxController::class, 'getStatus'])->name('game.api.larautilx.status');
    Route::get('/larautilx/cache/stats', [LarautilxController::class, 'getCacheStats'])->name('game.api.larautilx.cache.stats');
    Route::post('/larautilx/cache/clear', [LarautilxController::class, 'clearCache'])->name('game.api.larautilx.cache.clear');
    Route::post('/larautilx/cache/player/clear', [LarautilxController::class, 'clearPlayerCache'])->name('game.api.larautilx.cache.player.clear');
    Route::post('/larautilx/cache/world/clear', [LarautilxController::class, 'clearWorldCache'])->name('game.api.larautilx.cache.world.clear');
    Route::post('/larautilx/cache/village/clear', [LarautilxController::class, 'clearVillageCache'])->name('game.api.larautilx.cache.village.clear');
    Route::post('/larautilx/test/filtering', [LarautilxController::class, 'testFiltering'])->name('game.api.larautilx.test.filtering');
    Route::post('/larautilx/test/pagination', [LarautilxController::class, 'testPagination'])->name('game.api.larautilx.test.pagination');
    Route::post('/larautilx/test/caching', [LarautilxController::class, 'testCaching'])->name('game.api.larautilx.test.caching');
    Route::get('/larautilx/docs', [LarautilxController::class, 'getApiDocumentation'])->name('game.api.larautilx.docs');

    // User management
    Route::get('/users', [UserController::class, 'getAllRecords'])->name('game.api.users.index');
    Route::get('/users/{id}', [UserController::class, 'getRecordById'])->name('game.api.users.show');
    Route::post('/users', [UserController::class, 'storeRecord'])->name('game.api.users.store');
    Route::put('/users/{id}', [UserController::class, 'updateRecord'])->name('game.api.users.update');
    Route::delete('/users/{id}', [UserController::class, 'deleteRecord'])->name('game.api.users.destroy');
    
    // User advanced routes
    Route::get('/users/with-game-stats', [UserController::class, 'getUsersWithGameStats'])->name('game.api.users.with-game-stats');
    Route::get('/users/online', [UserController::class, 'getOnlineUsers'])->name('game.api.users.online');
    Route::get('/users/activity-stats', [UserController::class, 'getUserActivityStats'])->name('game.api.users.activity-stats');
    Route::get('/users/{userId}/details', [UserController::class, 'getUserDetails'])->name('game.api.users.details');
    Route::put('/users/{userId}/status', [UserController::class, 'updateUserStatus'])->name('game.api.users.status');
    Route::get('/users/{userId}/game-history', [UserController::class, 'getUserGameHistory'])->name('game.api.users.game-history');
    Route::get('/users/search', [UserController::class, 'searchUsers'])->name('game.api.users.search');
    Route::get('/users/{userId}/feature-toggles', [UserController::class, 'getUserFeatureToggles'])->name('game.api.users.feature-toggles');
    Route::post('/users/bulk-update-status', [UserController::class, 'bulkUpdateUserStatus'])->name('game.api.users.bulk-update-status');

    // System management
    Route::get('/system/config', [SystemController::class, 'getSystemConfig'])->name('game.api.system.config');
    Route::put('/system/config', [SystemController::class, 'updateSystemConfig'])->name('game.api.system.config.update');
    Route::get('/system/scheduled-tasks', [SystemController::class, 'getScheduledTasks'])->name('game.api.system.scheduled-tasks');
    Route::get('/system/health', [SystemController::class, 'getSystemHealth'])->name('game.api.system.health');
    Route::get('/system/metrics', [SystemController::class, 'getSystemMetrics'])->name('game.api.system.metrics');
    Route::post('/system/clear-caches', [SystemController::class, 'clearSystemCaches'])->name('game.api.system.clear-caches');
    Route::get('/system/logs', [SystemController::class, 'getSystemLogs'])->name('game.api.system.logs');

    // AI management
    Route::get('/ai/status', [AIController::class, 'getStatus'])->name('game.api.ai.status');
    Route::post('/ai/village-names', [AIController::class, 'generateVillageNames'])->name('game.api.ai.village-names');
    Route::post('/ai/alliance-names', [AIController::class, 'generateAllianceNames'])->name('game.api.ai.alliance-names');
    Route::post('/ai/quest-description', [AIController::class, 'generateQuestDescription'])->name('game.api.ai.quest-description');
    Route::post('/ai/battle-report', [AIController::class, 'generateBattleReport'])->name('game.api.ai.battle-report');
    Route::post('/ai/player-message', [AIController::class, 'generatePlayerMessage'])->name('game.api.ai.player-message');
    Route::post('/ai/world-event', [AIController::class, 'generateWorldEvent'])->name('game.api.ai.world-event');
    Route::post('/ai/strategy-suggestion', [AIController::class, 'generateStrategySuggestion'])->name('game.api.ai.strategy-suggestion');
    Route::post('/ai/custom-content', [AIController::class, 'generateCustomContent'])->name('game.api.ai.custom-content');
    Route::post('/ai/switch-provider', [AIController::class, 'switchProvider'])->name('game.api.ai.switch-provider');

    // Larautilx dashboard
    Route::get('/larautilx/dashboard', [LarautilxDashboardController::class, 'getDashboardData'])->name('game.api.larautilx.dashboard');
    Route::get('/larautilx/integration-summary', [LarautilxDashboardController::class, 'getIntegrationSummary'])->name('game.api.larautilx.integration-summary');
    Route::post('/larautilx/test-components', [LarautilxDashboardController::class, 'testComponents'])->name('game.api.larautilx.test-components');

    // API Documentation
    Route::get('/docs/larautilx', [APIDocumentationController::class, 'getLarautilxAPIDocumentation'])->name('game.api.docs.larautilx');

    // Message management API
    Route::get('/messages/inbox', [App\Http\Controllers\Game\MessageController::class, 'getInbox'])->name('game.api.messages.inbox');
    Route::get('/messages/sent', [App\Http\Controllers\Game\MessageController::class, 'getSent'])->name('game.api.messages.sent');
    Route::get('/messages/conversation/{otherPlayerId}', [App\Http\Controllers\Game\MessageController::class, 'getConversation'])->name('game.api.messages.conversation');
    Route::get('/messages/alliance', [App\Http\Controllers\Game\MessageController::class, 'getAllianceMessages'])->name('game.api.messages.alliance');
    Route::post('/messages/send', [App\Http\Controllers\Game\MessageController::class, 'sendMessage'])->name('game.api.messages.send');
    Route::post('/messages/alliance/send', [App\Http\Controllers\Game\MessageController::class, 'sendAllianceMessage'])->name('game.api.messages.alliance.send');
    Route::put('/messages/{messageId}/read', [App\Http\Controllers\Game\MessageController::class, 'markAsRead'])->name('game.api.messages.read');
    Route::delete('/messages/{messageId}', [App\Http\Controllers\Game\MessageController::class, 'deleteMessage'])->name('game.api.messages.delete');
    Route::get('/messages/stats', [App\Http\Controllers\Game\MessageController::class, 'getStats'])->name('game.api.messages.stats');
    Route::post('/messages/bulk-read', [App\Http\Controllers\Game\MessageController::class, 'bulkMarkAsRead'])->name('game.api.messages.bulk-read');
    Route::post('/messages/bulk-delete', [App\Http\Controllers\Game\MessageController::class, 'bulkDelete'])->name('game.api.messages.bulk-delete');
    Route::get('/messages/players', [App\Http\Controllers\Game\MessageController::class, 'getPlayers'])->name('game.api.messages.players');
    Route::get('/messages/{messageId}', [App\Http\Controllers\Game\MessageController::class, 'getMessage'])->name('game.api.messages.show');

    // Chat management API
    Route::get('/chat/channels', [App\Http\Controllers\Game\ChatController::class, 'getAvailableChannels'])->name('game.api.chat.channels');
    Route::get('/chat/global', [App\Http\Controllers\Game\ChatController::class, 'getGlobalMessages'])->name('game.api.chat.global');
    Route::get('/chat/alliance', [App\Http\Controllers\Game\ChatController::class, 'getAllianceMessages'])->name('game.api.chat.alliance');
    Route::get('/chat/channel/{channelId}', [App\Http\Controllers\Game\ChatController::class, 'getChannelMessages'])->name('game.api.chat.channel');
    Route::post('/chat/send', [App\Http\Controllers\Game\ChatController::class, 'sendMessage'])->name('game.api.chat.send');
    Route::post('/chat/global/send', [App\Http\Controllers\Game\ChatController::class, 'sendGlobalMessage'])->name('game.api.chat.global.send');
    Route::post('/chat/alliance/send', [App\Http\Controllers\Game\ChatController::class, 'sendAllianceMessage'])->name('game.api.chat.alliance.send');
    Route::delete('/chat/messages/{messageId}', [App\Http\Controllers\Game\ChatController::class, 'deleteMessage'])->name('game.api.chat.delete');
    Route::post('/chat/channels/create', [App\Http\Controllers\Game\ChatController::class, 'createChannel'])->name('game.api.chat.channels.create');
    Route::get('/chat/stats', [App\Http\Controllers\Game\ChatController::class, 'getChatStats'])->name('game.api.chat.stats');
});

// Error pages
Route::get('/game/no-player', function () {
    return view('game.no-player');
})->name('game.no-player');

Route::get('/game/suspended', function () {
    return view('game.suspended');
})->name('game.suspended');
