<?php

namespace App\Utilities;

use Illuminate\Log\Logger as IlluminateLogger;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Enums\LogLevel;
use Monolog\Logger;

class LoggingUtil
{
    private static ?IlluminateLogger $customLogger = null;

    /**
     * Initialize a custom logger instance if needed.
     *
     * @param  string|null  $channel  Custom log channel
     */
    private static function getLogger(?string $channel = null): IlluminateLogger
    {
        if ($channel) {
            return Log::channel($channel);
        }

        if (! self::$customLogger) {
            // Use Laravel's default logger for custom logging
            self::$customLogger = Log::channel('single');
        }

        return self::$customLogger;
    }

    /**
     * Log a message with context and formatting.
     *
     * @param  LogLevel  $level  Log level (debug, info, warning, error, critical)
     * @param  string  $message  Log message
     * @param  array  $context  Additional context data
     * @param  string|null  $channel  Log channel (default, single, daily, custom, etc.)
     */
    public static function log(LogLevel $level, string $message, array $context = [], ?string $channel = null): void
    {
        $logger = self::getLogger($channel);
        $context['timestamp'] = now()->toDateTimeString();
        $context['env'] = Config::get('app.env');

        $logger->{$level->value}($message, $context);
    }

    public static function info(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(LogLevel::Info, $message, $context, $channel);
    }

    public static function debug(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(LogLevel::Debug, $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(LogLevel::Warning, $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(LogLevel::Error, $message, $context, $channel);
    }

    public static function critical(string $message, array $context = [], ?string $channel = null): void
    {
        self::log(LogLevel::Critical, $message, $context, $channel);
    }
}
