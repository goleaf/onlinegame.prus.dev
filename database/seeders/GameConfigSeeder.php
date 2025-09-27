<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // Game Settings
            ['key' => 'game_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Game speed multiplier'],
            ['key' => 'unit_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Unit movement speed multiplier'],
            ['key' => 'trade_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Trade speed multiplier'],
            ['key' => 'world_speed', 'value' => '1', 'type' => 'integer', 'description' => 'World speed multiplier'],
            // Resource Settings
            ['key' => 'resource_multiplier', 'value' => '1', 'type' => 'integer', 'description' => 'Resource production multiplier'],
            ['key' => 'storage_multiplier', 'value' => '1', 'type' => 'integer', 'description' => 'Storage capacity multiplier'],
            ['key' => 'crop_consumption', 'value' => '1', 'type' => 'integer', 'description' => 'Crop consumption multiplier'],
            // Building Settings
            ['key' => 'building_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Building construction speed multiplier'],
            ['key' => 'research_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Research speed multiplier'],
            ['key' => 'training_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Unit training speed multiplier'],
            // Combat Settings
            ['key' => 'combat_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Combat speed multiplier'],
            ['key' => 'morale_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Morale speed multiplier'],
            ['key' => 'artefact_speed', 'value' => '1', 'type' => 'integer', 'description' => 'Artifact speed multiplier'],
            // World Settings
            ['key' => 'world_size', 'value' => '401', 'type' => 'integer', 'description' => 'World size (401x401)'],
            ['key' => 'village_distance', 'value' => '7', 'type' => 'integer', 'description' => 'Minimum distance between villages'],
            ['key' => 'oasis_distance', 'value' => '5', 'type' => 'integer', 'description' => 'Minimum distance between oases'],
            // Game Features
            ['key' => 'plus_features', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable Plus features'],
            ['key' => 'alliance_features', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable Alliance features'],
            ['key' => 'hero_features', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable Hero features'],
            ['key' => 'artefact_features', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable Artifact features'],
            // Time Settings
            ['key' => 'tick_time', 'value' => '60', 'type' => 'integer', 'description' => 'Game tick time in seconds'],
            ['key' => 'resource_update_interval', 'value' => '60', 'type' => 'integer', 'description' => 'Resource update interval in seconds'],
            ['key' => 'building_update_interval', 'value' => '60', 'type' => 'integer', 'description' => 'Building update interval in seconds'],
            // Economy Settings
            ['key' => 'market_tax', 'value' => '0.05', 'type' => 'decimal', 'description' => 'Market trade tax (5%)'],
            ['key' => 'alliance_tax', 'value' => '0.01', 'type' => 'decimal', 'description' => 'Alliance tax (1%)'],
            ['key' => 'gold_multiplier', 'value' => '1', 'type' => 'integer', 'description' => 'Gold production multiplier'],
            // Security Settings
            ['key' => 'max_login_attempts', 'value' => '5', 'type' => 'integer', 'description' => 'Maximum login attempts'],
            ['key' => 'session_timeout', 'value' => '3600', 'type' => 'integer', 'description' => 'Session timeout in seconds'],
            ['key' => 'password_min_length', 'value' => '6', 'type' => 'integer', 'description' => 'Minimum password length'],
            // Notification Settings
            ['key' => 'email_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable email notifications'],
            ['key' => 'push_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable push notifications'],
            ['key' => 'battle_notifications', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable battle notifications'],
            // Maintenance Settings
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Maintenance mode'],
            ['key' => 'maintenance_message', 'value' => 'Server is under maintenance', 'type' => 'string', 'description' => 'Maintenance message'],
            ['key' => 'backup_interval', 'value' => '3600', 'type' => 'integer', 'description' => 'Backup interval in seconds'],
        ];

        foreach ($configs as $config) {
            DB::table('game_configs')->updateOrInsert(
                ['key' => $config['key']],
                array_merge($config, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
