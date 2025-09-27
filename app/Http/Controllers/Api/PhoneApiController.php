<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\Rules\Phone;

class PhoneApiController extends Controller
{
    protected SmsNotificationService $smsService;

    public function __construct(SmsNotificationService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Get user phone information
     */
    public function getUserPhone(int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'phone_country' => $user->phone_country,
                'phone_e164' => $user->phone_e164,
                'phone_normalized' => $user->phone_normalized,
                'phone_national' => $user->phone_national,
                'has_phone' => !is_null($user->phone),
                'sms_enabled' => $user->sms_notifications_enabled ?? false,
            ]
        ]);
    }

    /**
     * Update user phone information
     */
    public function updateUserPhone(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'phone' => ['nullable', 'string'],
            'phone_country' => ['nullable', 'string', 'size:2'],
            'sms_notifications_enabled' => ['nullable', 'boolean'],
            'sms_urgent_only' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Add phone validation if phone is provided
        if (!empty($data['phone'])) {
            $phoneValidator = Validator::make($data, [
                'phone' => [(new Phone)->country($data['phone_country'] ?? 'US')],
                'phone_country' => ['required_with:phone'],
            ]);

            if ($phoneValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $phoneValidator->errors()
                ], 422);
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Phone information updated successfully',
            'data' => [
                'user_id' => $user->id,
                'phone' => $user->phone,
                'phone_country' => $user->phone_country,
                'phone_e164' => $user->phone_e164,
                'sms_enabled' => $user->sms_notifications_enabled,
            ]
        ]);
    }

    /**
     * Send SMS notification to user
     */
    public function sendSms(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:160',
            'priority' => 'nullable|in:normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $priority = $data['priority'] ?? 'normal';

        $success = $this->smsService->sendSmsToUser($user, $data['message'], $priority);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'SMS sent successfully' : 'Failed to send SMS',
            'data' => [
                'user_id' => $user->id,
                'message' => $data['message'],
                'priority' => $priority,
                'phone' => $user->phone_e164 ?? $user->phone,
            ]
        ]);
    }

    /**
     * Get phone statistics
     */
    public function getPhoneStatistics(): JsonResponse
    {
        $stats = $this->smsService->getSmsStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Search users by phone
     */
    public function searchUsersByPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'country' => 'nullable|string|size:2',
            'format' => 'nullable|in:raw,normalized,e164',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $phone = $data['phone'];
        $country = $data['country'] ?? null;
        $format = $data['format'] ?? 'raw';

        $query = User::whereNotNull('phone');

        switch ($format) {
            case 'normalized':
                $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
                $query->where('phone_normalized', 'like', "%{$cleanPhone}%");
                break;
            case 'e164':
                $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
                $query->where('phone_e164', 'like', "%{$cleanPhone}%");
                break;
            default:
                $query->where('phone', 'like', "%{$phone}%");
                break;
        }

        if ($country) {
            $query->where('phone_country', $country);
        }

        $users = $query->with(['player.world', 'player.alliance'])
                      ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Bulk SMS to multiple users
     */
    public function sendBulkSms(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1|max:100',
            'user_ids.*' => 'integer|exists:users,id',
            'message' => 'required|string|max:160',
            'priority' => 'nullable|in:normal,high,urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $priority = $data['priority'] ?? 'normal';

        $results = $this->smsService->sendBulkSms($data['user_ids'], $data['message'], $priority);

        return response()->json([
            'success' => true,
            'message' => 'Bulk SMS operation completed',
            'data' => $results
        ]);
    }

    /**
     * Validate phone number
     */
    public function validatePhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'country' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        try {
            $phoneNumber = phone($data['phone'], $data['country']);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'original' => $data['phone'],
                    'country' => $data['country'],
                    'e164' => $phoneNumber->formatE164(),
                    'international' => $phoneNumber->formatInternational(),
                    'national' => $phoneNumber->formatNational(),
                    'normalized' => preg_replace('/[^0-9]/', '', $data['phone']),
                    'is_valid' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number',
                'data' => [
                    'original' => $data['phone'],
                    'country' => $data['country'],
                    'is_valid' => false,
                    'error' => $e->getMessage(),
                ]
            ], 422);
        }
    }

    /**
     * Get users with phone numbers (with pagination)
     */
    public function getUsersWithPhones(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'country' => 'nullable|string|size:2',
            'has_e164' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $perPage = $data['per_page'] ?? 20;

        $query = User::whereNotNull('phone')
                    ->with(['player.world', 'player.alliance']);

        if (isset($data['country'])) {
            $query->where('phone_country', $data['country']);
        }

        if (isset($data['has_e164']) && $data['has_e164']) {
            $query->whereNotNull('phone_e164');
        }

        $users = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}