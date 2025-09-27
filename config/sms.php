<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for SMS notifications.
    | You can configure different SMS providers and settings here.
    |
    */

    'enabled' => env('SMS_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your SMS provider settings here. Currently supports Twilio.
    | Add more providers as needed.
    |
    */

    'provider' => env('SMS_PROVIDER', 'twilio'),

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    |
    | Twilio SMS provider configuration
    |
    */

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from_number' => env('TWILIO_FROM_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nexmo Configuration
    |--------------------------------------------------------------------------
    |
    | Nexmo SMS provider configuration
    |
    */

    'nexmo' => [
        'api_key' => env('NEXMO_API_KEY'),
        'api_secret' => env('NEXMO_API_SECRET'),
        'from' => env('NEXMO_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Settings
    |--------------------------------------------------------------------------
    |
    | General SMS settings and limits
    |
    */

    'settings' => [
        'max_message_length' => 160,
        'max_bulk_recipients' => 100,
        'rate_limit_per_minute' => 60,
        'retry_attempts' => 3,
        'retry_delay_seconds' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Types
    |--------------------------------------------------------------------------
    |
    | Define which notification types can be sent via SMS
    |
    */

    'allowed_notification_types' => [
        'battle_attack',
        'village_attacked',
        'alliance_message',
        'system_announcement',
        'urgent_alert',
        'emergency_notification',
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    |
    | Define SMS priority levels and their behavior
    |
    */

    'priority_levels' => [
        'normal' => [
            'enabled' => true,
            'rate_limit' => 10, // per hour
        ],
        'high' => [
            'enabled' => true,
            'rate_limit' => 30, // per hour
        ],
        'urgent' => [
            'enabled' => true,
            'rate_limit' => 60, // per hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Preferences
    |--------------------------------------------------------------------------
    |
    | Default SMS notification preferences for new users
    |
    */

    'default_user_preferences' => [
        'sms_notifications_enabled' => false,
        'sms_urgent_only' => true,
        'sms_battle_alerts' => true,
        'sms_alliance_messages' => false,
        'sms_system_announcements' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | SMS message templates for different notification types
    |
    */

    'templates' => [
        'battle_attack' => '🚨 BATTLE: {attacker} attacking {village} with {units} units!',
        'village_attacked' => '🏰 ATTACK: {attacker} attacking {village} - arrives {time}',
        'alliance_message' => '🤝 ALLIANCE: {sender}: {message}',
        'system_announcement' => '📢 SYSTEM: {message}',
        'urgent_alert' => '⚠️ URGENT: {message}',
        'emergency_notification' => '🚨 EMERGENCY: {message}',
    ],
];
