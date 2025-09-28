<?php

namespace Tests\Unit\Traits;

use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;

class ApiResponseTraitTest extends TestCase
{
    private $traitObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->traitObject = new class () {
            use ApiResponseTrait;
        };
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_defaults()
    {
        $response = $this->traitObject->successResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('Request successful.', $data['message']);
        $this->assertNull($data['data']);
        $this->assertEquals([], $data['meta']);
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_data()
    {
        $testData = ['name' => 'John', 'age' => 30];
        $response = $this->traitObject->successResponse($testData);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals($testData, $data['data']);
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_custom_message()
    {
        $customMessage = 'Operation completed successfully';
        $response = $this->traitObject->successResponse(null, $customMessage);

        $data = $response->getData(true);
        $this->assertEquals($customMessage, $data['message']);
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_custom_status_code()
    {
        $response = $this->traitObject->successResponse(null, 'Created', 201);

        $this->assertEquals(201, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_success_response_with_meta_data()
    {
        $metaData = ['total' => 100, 'page' => 1];
        $response = $this->traitObject->successResponse(null, 'Success', 200, $metaData);

        $data = $response->getData(true);
        $this->assertEquals($metaData, $data['meta']);
    }

    /**
     * @test
     */
    public function it_returns_error_response_with_defaults()
    {
        $response = $this->traitObject->errorResponse();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Something went wrong.', $data['message']);
        $this->assertEquals([], $data['errors']);
    }

    /**
     * @test
     */
    public function it_returns_error_response_with_custom_message()
    {
        $errorMessage = 'Validation failed';
        $response = $this->traitObject->errorResponse($errorMessage);

        $data = $response->getData(true);
        $this->assertEquals($errorMessage, $data['message']);
    }

    /**
     * @test
     */
    public function it_returns_error_response_with_custom_status_code()
    {
        $response = $this->traitObject->errorResponse('Not found', 404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_returns_error_response_with_errors_array()
    {
        $errors = ['email' => ['Email is required'], 'name' => ['Name is too short']];
        $response = $this->traitObject->errorResponse('Validation failed', 422, $errors);

        $data = $response->getData(true);
        $this->assertEquals($errors, $data['errors']);
    }

    /**
     * @test
     */
    public function it_includes_debug_data_when_app_debug_is_true()
    {
        config(['app.debug' => true]);

        $debugData = ['stack_trace' => 'Error at line 123'];
        $response = $this->traitObject->errorResponse('Error occurred', 500, [], $debugData);

        $data = $response->getData(true);
        $this->assertArrayHasKey('debug', $data);
        $this->assertEquals($debugData, $data['debug']);
    }

    /**
     * @test
     */
    public function it_excludes_debug_data_when_app_debug_is_false()
    {
        config(['app.debug' => false]);

        $debugData = ['stack_trace' => 'Error at line 123'];
        $response = $this->traitObject->errorResponse('Error occurred', 500, [], $debugData);

        $data = $response->getData(true);
        $this->assertArrayNotHasKey('debug', $data);
    }

    /**
     * @test
     */
    public function it_handles_different_data_types_in_success_response()
    {
        $testCases = [
            ['data' => 'string data', 'expected' => 'string data'],
            ['data' => 123, 'expected' => 123],
            ['data' => true, 'expected' => true],
            ['data' => ['array' => 'data'], 'expected' => ['array' => 'data']],
            ['data' => null, 'expected' => null],
        ];

        foreach ($testCases as $testCase) {
            $response = $this->traitObject->successResponse($testCase['data']);
            $data = $response->getData(true);

            $this->assertEquals($testCase['expected'], $data['data']);
        }
    }

    /**
     * @test
     */
    public function it_handles_different_error_formats()
    {
        $testCases = [
            ['errors' => [], 'expected' => []],
            ['errors' => ['field' => 'error'], 'expected' => ['field' => 'error']],
            ['errors' => ['field' => ['error1', 'error2']], 'expected' => ['field' => ['error1', 'error2']]],
            ['errors' => 'string error', 'expected' => 'string error'],
        ];

        foreach ($testCases as $testCase) {
            $response = $this->traitObject->errorResponse('Error', 400, $testCase['errors']);
            $data = $response->getData(true);

            $this->assertEquals($testCase['expected'], $data['errors']);
        }
    }

    /**
     * @test
     */
    public function it_handles_various_http_status_codes()
    {
        $statusCodes = [200, 201, 204, 400, 401, 403, 404, 422, 500, 503];

        foreach ($statusCodes as $statusCode) {
            if ($statusCode < 400) {
                $response = $this->traitObject->successResponse(null, 'Success', $statusCode);
            } else {
                $response = $this->traitObject->errorResponse('Error', $statusCode);
            }

            $this->assertEquals($statusCode, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_maintains_json_response_structure()
    {
        $successResponse = $this->traitObject->successResponse(['test' => 'data']);
        $successData = $successResponse->getData(true);

        $this->assertArrayHasKey('success', $successData);
        $this->assertArrayHasKey('message', $successData);
        $this->assertArrayHasKey('data', $successData);
        $this->assertArrayHasKey('meta', $successData);

        $errorResponse = $this->traitObject->errorResponse('Error', 400, ['field' => 'error']);
        $errorData = $errorResponse->getData(true);

        $this->assertArrayHasKey('success', $errorData);
        $this->assertArrayHasKey('message', $errorData);
        $this->assertArrayHasKey('errors', $errorData);
    }

    /**
     * @test
     */
    public function it_handles_empty_and_null_values()
    {
        // Test with empty string
        $response = $this->traitObject->successResponse('');
        $data = $response->getData(true);
        $this->assertEquals('', $data['data']);

        // Test with empty array
        $response = $this->traitObject->successResponse([]);
        $data = $response->getData(true);
        $this->assertEquals([], $data['data']);

        // Test with null
        $response = $this->traitObject->successResponse(null);
        $data = $response->getData(true);
        $this->assertNull($data['data']);
    }

    /**
     * @test
     */
    public function it_handles_complex_nested_data()
    {
        $complexData = [
            'user' => [
                'id' => 1,
                'name' => 'John Doe',
                'profile' => [
                    'bio' => 'Software developer',
                    'skills' => ['PHP', 'Laravel', 'JavaScript'],
                ],
            ],
            'posts' => [
                ['id' => 1, 'title' => 'First Post'],
                ['id' => 2, 'title' => 'Second Post'],
            ],
        ];

        $response = $this->traitObject->successResponse($complexData);
        $data = $response->getData(true);

        $this->assertEquals($complexData, $data['data']);
    }

    /**
     * @test
     */
    public function it_handles_large_data_sets()
    {
        $largeData = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeData[] = ['id' => $i, 'name' => "Item $i"];
        }

        $response = $this->traitObject->successResponse($largeData);
        $data = $response->getData(true);

        $this->assertCount(1000, $data['data']);
        $this->assertEquals($largeData, $data['data']);
    }

    /**
     * @test
     */
    public function it_preserves_response_headers()
    {
        $response = $this->traitObject->successResponse(['test' => 'data']);

        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_messages()
    {
        $specialMessage = 'Message with special chars: àáâãäåæçèéêë & symbols @#$%^&*()';

        $response = $this->traitObject->successResponse(null, $specialMessage);
        $data = $response->getData(true);

        $this->assertEquals($specialMessage, $data['message']);
    }

    /**
     * @test
     */
    public function it_handles_unicode_characters()
    {
        $unicodeData = [
            'chinese' => '你好世界',
            'arabic' => 'مرحبا بالعالم',
            'emoji' => '🚀🎉✨',
        ];

        $response = $this->traitObject->successResponse($unicodeData);
        $data = $response->getData(true);

        $this->assertEquals($unicodeData, $data['data']);
    }
}
