<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class PhoneApiController extends Controller
{
    /**
     * Validate and update user phone number
     */
    public function updatePhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'nullable|string|max:20',
            'phone_country' => 'nullable|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        $user->update([
            'phone' => $request->phone,
            'phone_country' => $request->phone_country,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone number updated successfully',
            'data' => [
                'phone' => $user->phone,
                'phone_country' => $user->phone_country,
            ]
        ]);
    }

    /**
     * Get user phone information
     */
    public function getPhone(): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'phone' => $user->phone,
                'phone_country' => $user->phone_country,
            ]
        ]);
    }
}
