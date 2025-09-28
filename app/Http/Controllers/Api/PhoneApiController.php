<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\User;
use App\Traits\ValidationHelperTrait;
use App\Utilities\LoggingUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

class PhoneApiController extends CrudController
{
    use ApiResponseTrait;
    use ValidationHelperTrait;

    public function __construct()
    {
        parent::__construct(new User());
    }

    /**
     * Validate and update user phone number
     */
    public function updatePhone(Request $request): JsonResponse
    {
        $validated = $this->validateRequestData($request, [
            'phone' => 'nullable|string|max:20',
            'phone_country' => 'nullable|string|size:2',
        ]);

        $user = auth()->user();
        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        $user->update([
            'phone' => $validated['phone'],
            'phone_country' => $validated['phone_country'],
        ]);

        LoggingUtil::info('Phone number updated', [
            'user_id' => $user->id,
            'phone' => $validated['phone'],
            'phone_country' => $validated['phone_country'],
        ], 'user_management');

        return $this->successResponse([
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
        ], 'Phone number updated successfully');
    }

    /**
     * Get user phone information
     */
    public function getPhone(): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        LoggingUtil::info('Phone information retrieved', [
            'user_id' => $user->id,
        ], 'user_management');

        return $this->successResponse([
            'phone' => $user->phone,
            'phone_country' => $user->phone_country,
        ], 'Phone information retrieved successfully');
    }
}
