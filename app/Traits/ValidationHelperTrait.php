<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidationHelperTrait
{
    /**
     * Validate request data and return structured response
     */
    public function validateRequestData(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
                'message' => 'Validation failed',
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'message' => 'Validation successful',
            'data' => $validator->validated(),
        ];
    }

    /**
     * Validate request data and return only validated data (throws exception on failure)
     *
     * @throws ValidationException
     */
    public function validateRequestDataOrFail(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validate array data and return structured response
     */
    public function validateData(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            return [
                'success' => false,
                'errors' => $validator->errors()->toArray(),
                'message' => 'Validation failed',
            ];
        }

        return [
            'success' => true,
            'errors' => [],
            'message' => 'Validation successful',
            'data' => $validator->validated(),
        ];
    }

    /**
     * Validate array data and return only validated data (throws exception on failure)
     *
     * @throws ValidationException
     */
    public function validateDataOrFail(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Check if validation passes without returning data
     */
    public function isValid(Request $request, array $rules, array $messages = [], array $attributes = []): bool
    {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        return ! $validator->fails();
    }

    /**
     * Check if array data is valid without returning data
     */
    public function isDataValid(array $data, array $rules, array $messages = [], array $attributes = []): bool
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        return ! $validator->fails();
    }

    /**
     * Get validation errors without throwing exception
     */
    public function getValidationErrors(Request $request, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);

        return $validator->errors()->toArray();
    }

    /**
     * Get validation errors for array data without throwing exception
     */
    public function getDataValidationErrors(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        return $validator->errors()->toArray();
    }
}
