<?php

namespace Tests\Unit\Utilities;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Enums\LogLevel;
use LaraUtilX\Utilities\LoggingUtil;
use Tests\TestCase;

class LoggingUtilTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_log_info_message()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('Test message', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_can_log_debug_message()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('debug')
            ->once()
            ->with('Debug message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::debug('Debug message', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_can_log_warning_message()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Warning message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::warning('Warning message', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_can_log_error_message()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('error')
            ->once()
            ->with('Error message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::error('Error message', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_can_log_critical_message()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('critical')
            ->once()
            ->with('Critical message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::critical('Critical message', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_can_log_with_custom_channel()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('custom')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('Test message', ['context' => 'data'], 'custom');
    }

    /**
     * @test
     */
    public function it_can_log_with_empty_context()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['timestamp' => '2023-01-01 00:00:00', 'env' => 'testing']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('Test message');
    }

    /**
     * @test
     */
    public function it_can_log_with_log_level_enum()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::log(LogLevel::Info, 'Test message', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_adds_timestamp_to_context()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', function ($context) {
                return isset($context['timestamp']) && isset($context['env']);
            });

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('Test message', ['custom' => 'data']);
    }

    /**
     * @test
     */
    public function it_adds_environment_to_context()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', function ($context) {
                return $context['env'] === 'testing';
            });

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('Test message');
    }

    /**
     * @test
     */
    public function it_handles_null_channel()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with(null)
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('Test message', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('Test message', ['context' => 'data'], null);
    }

    /**
     * @test
     */
    public function it_handles_empty_message()
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('', ['context' => 'data']);

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('', ['context' => 'data']);
    }

    /**
     * @test
     */
    public function it_handles_complex_context_data()
    {
        $complexContext = [
            'user_id' => 123,
            'action' => 'login',
            'metadata' => [
                'ip' => '192.168.1.1',
                'user_agent' => 'Mozilla/5.0',
            ],
        ];

        Log::shouldReceive('channel')
            ->once()
            ->with('default')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('User action', function ($context) {
                return isset($context['user_id']) &&
                    isset($context['action']) &&
                    isset($context['metadata']) &&
                    isset($context['timestamp']) &&
                    isset($context['env']);
            });

        Config::shouldReceive('get')
            ->once()
            ->with('app.env')
            ->andReturn('testing');

        LoggingUtil::info('User action', $complexContext);
    }
}
