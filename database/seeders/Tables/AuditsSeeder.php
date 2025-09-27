<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AuditsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --table-mode --all-tables
         *
         */

        $dataTables = [
            [
                'id' => 1,
                'user_type' => NULL,
                'user_id' => NULL,
                'event' => 'updated',
                'auditable_type' => 'App\\Models\\User',
                'auditable_id' => 1,
                'old_values' => '{"name":"Test Audit Update"}',
                'new_values' => '{"name":"Test Audit Update - 1758933575"}',
                'url' => 'artisan tinker --execute=
$user = App\\Models\\User::first(];
if ($user] {
    echo \'User auditing enabled: \' . (App\\Models\\User::isAuditingEnabled(] ? \'true\' : \'false\'] . \'\\n\';
    $originalName = $user->name;
    $user->name = \'Test Audit Update - \' . time(];
    $user->save(];
    echo \'User updated from "\' . $originalName . \'" to "\' . $user->name . \'"\\n\';
    $audits = $user->audits(]->latest(]->first(];
    if ($audits] {
        echo \'Audit created: Event=\' . $audits->event . \', Old=\' . json_encode($audits->old_values] . \', New=\' . json_encode($audits->new_values] . \'\\n\';
    } else {
        echo \'No audit found\\n\';
    }
} else {
    echo \'No users found\\n\';
}
',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Symfony',
                'tags' => NULL,
                'created_at' => '2025-09-27 00:39:35',
                'updated_at' => '2025-09-27 00:39:35',
            ],
            [
                'id' => 2,
                'user_type' => NULL,
                'user_id' => NULL,
                'event' => 'updated',
                'auditable_type' => 'App\\Models\\Game\\Player',
                'auditable_id' => 1,
                'old_values' => '{"points":0}',
                'new_values' => '{"points":100}',
                'url' => 'artisan tinker --execute=
$player = App\\Models\\Game\\Player::first(];
if ($player] {
    $originalPoints = $player->points;
    $player->points = ($originalPoints ?? 0] + 100;
    $player->save(];
    echo \'Player updated: points from \' . $originalPoints . \' to \' . $player->points . \'\\n\';
    $audits = $player->audits(]->latest(]->first(];
    if ($audits] {
        echo \'Player audit created: Event=\' . $audits->event . \', Old=\' . json_encode($audits->old_values] . \', New=\' . json_encode($audits->new_values] . \'\\n\';
    } else {
        echo \'No player audit found\\n\';
    }
} else {
    echo \'No players found\\n\';
}
',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Symfony',
                'tags' => NULL,
                'created_at' => '2025-09-27 00:39:53',
                'updated_at' => '2025-09-27 00:39:53',
            ]
        ];
        
        foreach ($dataTables as $data) {
            DB::table("audits")->updateOrInsert(['id' => $data['id']], $data);
        }
    }
}