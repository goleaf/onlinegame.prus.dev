<?php

namespace Tests\Unit\Utilities;

use Illuminate\Http\Request;
use LaraUtilX\Utilities\QueryParameterUtil;
use Tests\TestCase;

class QueryParameterUtilTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_parse_existing_query_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
            'age' => 25,
            'city' => 'New York',
        ]);

        $allowedParameters = ['name', 'age', 'city'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John',
            'age' => 25,
            'city' => 'New York',
        ], $result);
    }

    /**
     * @test
     */
    public function it_only_includes_allowed_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
            'age' => 25,
            'city' => 'New York',
            'secret' => 'hidden',
        ]);

        $allowedParameters = ['name', 'age'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John',
            'age' => 25,
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_missing_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
        ]);

        $allowedParameters = ['name', 'age', 'city'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John',
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_empty_request()
    {
        $request = Request::create('/test', 'GET', []);

        $allowedParameters = ['name', 'age', 'city'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function it_handles_empty_allowed_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
            'age' => 25,
        ]);

        $allowedParameters = [];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([], $result);
    }

    /**
     * @test
     */
    public function it_handles_string_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $allowedParameters = ['name', 'email'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_numeric_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'age' => 25,
            'score' => 95.5,
            'count' => 0,
        ]);

        $allowedParameters = ['age', 'score', 'count'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'age' => 25,
            'score' => 95.5,
            'count' => 0,
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_boolean_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'active' => true,
            'enabled' => false,
        ]);

        $allowedParameters = ['active', 'enabled'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'active' => true,
            'enabled' => false,
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_array_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'tags' => ['php', 'laravel', 'testing'],
            'ids' => [1, 2, 3],
        ]);

        $allowedParameters = ['tags', 'ids'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'tags' => ['php', 'laravel', 'testing'],
            'ids' => [1, 2, 3],
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_null_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
            'age' => null,
        ]);

        $allowedParameters = ['name', 'age'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John',
            'age' => null,
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_empty_string_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
            'description' => '',
        ]);

        $allowedParameters = ['name', 'description'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John',
            'description' => '',
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_duplicate_allowed_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John',
            'age' => 25,
        ]);

        $allowedParameters = ['name', 'age', 'name'];  // Duplicate 'name'

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John',
            'age' => 25,
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_special_characters_in_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'John & Jane',
            'email' => 'test+tag@example.com',
            'url' => 'https://example.com/path?param=value',
        ]);

        $allowedParameters = ['name', 'email', 'url'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'John & Jane',
            'email' => 'test+tag@example.com',
            'url' => 'https://example.com/path?param=value',
        ], $result);
    }

    /**
     * @test
     */
    public function it_handles_unicode_parameters()
    {
        $request = Request::create('/test', 'GET', [
            'name' => 'José María',
            'city' => 'São Paulo',
            'emoji' => '🚀',
        ]);

        $allowedParameters = ['name', 'city', 'emoji'];

        $result = QueryParameterUtil::parse($request, $allowedParameters);

        $this->assertEquals([
            'name' => 'José María',
            'city' => 'São Paulo',
            'emoji' => '🚀',
        ], $result);
    }
}
