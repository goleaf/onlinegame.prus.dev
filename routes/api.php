<?php

use App\Models\Game\Player;
use App\Models\Game\Village;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Game API Routes
Route::middleware('auth:sanctum')->prefix('game')->group(function () {
    Route::get('/villages', function (Request $request) {
        $user = $request->user();
        $player = Player::where('user_id', $user->id)->first();

        if (! $player) {
            return response()->json(['villages' => []]);
        }

        $villages = Village::where('player_id', $player->id)->get();

        return response()->json(['villages' => $villages]);
    });

    Route::post('/create-village', function (Request $request) {
        $user = $request->user();
        $player = Player::where('user_id', $user->id)->first();

        if (! $player) {
            return response()->json(['success' => false, 'message' => 'Player not found']);
        }

        $village = Village::create([
            'player_id' => $player->id,
            'world_id' => $player->world_id,
            'name' => $request->input('name', 'New Village'),
            'x_coordinate' => $request->input('x', 50),
            'y_coordinate' => $request->input('y', 50),
            'population' => 2,
            'is_capital' => false,
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
            'wood_capacity' => 1000,
            'clay_capacity' => 1000,
            'iron_capacity' => 1000,
            'crop_capacity' => 1000,
        ]);

        // Update player statistics
        $player->increment('villages_count');
        $player->increment('population', $village->population);

        return response()->json(['success' => true, 'village' => $village]);
    });

    Route::post('/village/{id}/upgrade-building', function (Request $request, $id) {
        $user = $request->user();
        $village = Village::findOrFail($id);

        // Check if player owns this village
        $player = Player::where('user_id', $user->id)->first();
        if (! $player || $village->player_id !== $player->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized']);
        }

        $buildingType = $request->input('building_type');

        // Simple building upgrade logic
        $resourceType = $buildingType;
        if (in_array($resourceType, ['wood', 'clay', 'iron', 'crop'])) {
            $village->increment($resourceType . '_capacity', 100);
            $village->increment('population', 1);

            // Update player population
            $player->increment('population', 1);
        }

        return response()->json(['success' => true, 'village' => $village]);
    });
});
