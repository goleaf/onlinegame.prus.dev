<?php

use App\Http\Controllers\Api\GameApiController;
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

// Game API Routes
Route::middleware('auth:sanctum')->prefix('game')->group(function () {
    Route::get('/villages', [GameApiController::class, 'getVillages']);
    Route::post('/create-village', [GameApiController::class, 'createVillage']);
    Route::get('/village/{id}', [GameApiController::class, 'getVillage']);
    Route::post('/village/{id}/upgrade-building', [GameApiController::class, 'upgradeBuilding']);
    Route::get('/player/stats', [GameApiController::class, 'getPlayerStats']);
});
