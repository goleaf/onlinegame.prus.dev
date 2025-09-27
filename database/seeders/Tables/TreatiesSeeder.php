<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TreatiesSeeder extends Seeder
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
                'name' => 'Test Non-Aggression Pact',
                'description' => 'Test treaty between players',
                'type' => 'non_aggression',
                'status' => 'proposed',
                'proposer_id' => 1,
                'recipient_id' => 2,
                'terms' => '{"no_attacks":true}',
                'benefits' => '{"proposer":{"security":"Protection"}}',
                'penalties' => '{"violation":{"reputation_loss":50}}',
                'proposed_at' => '2025-09-27 04:32:48',
                'accepted_at' => NULL,
                'expires_at' => NULL,
                'duration_hours' => 24,
                'is_public' => 0,
                'created_at' => '2025-09-27 04:32:48',
                'updated_at' => '2025-09-27 04:32:48',
            ],
            [
                'id' => 2,
                'name' => 'Active Alliance',
                'description' => 'Active military alliance',
                'type' => 'alliance',
                'status' => 'active',
                'proposer_id' => 3,
                'recipient_id' => 4,
                'terms' => '{"mutual_defense":true}',
                'benefits' => '{"proposer":{"military_support":"Allied forces"}}',
                'penalties' => '{"violation":{"reputation_loss":100}}',
                'proposed_at' => '2025-09-27 04:33:22',
                'accepted_at' => '2025-09-27 04:33:22',
                'expires_at' => NULL,
                'duration_hours' => 48,
                'is_public' => 1,
                'created_at' => '2025-09-27 04:33:22',
                'updated_at' => '2025-09-27 04:33:22',
            ],
            [
                'id' => 3,
                'name' => 'Trade Agreement',
                'description' => 'Favorable trade terms',
                'type' => 'trade',
                'status' => 'active',
                'proposer_id' => 5,
                'recipient_id' => 6,
                'terms' => '{"favorable_rates":true}',
                'benefits' => '{"proposer":{"trade_bonus":"10% better rates"}}',
                'penalties' => '{"violation":{"reputation_loss":30}}',
                'proposed_at' => '2025-09-27 04:34:27',
                'accepted_at' => '2025-09-27 04:34:27',
                'expires_at' => NULL,
                'duration_hours' => 72,
                'is_public' => 0,
                'created_at' => '2025-09-27 04:34:27',
                'updated_at' => '2025-09-27 04:34:27',
            ],
            [
                'id' => 4,
                'name' => 'Peace Treaty',
                'description' => 'End of hostilities',
                'type' => 'peace',
                'status' => 'active',
                'proposer_id' => 7,
                'recipient_id' => 8,
                'terms' => '{"cease_hostilities":true}',
                'benefits' => '{"proposer":{"peace":"End of conflict"}}',
                'penalties' => '{"violation":{"reputation_loss":150}}',
                'proposed_at' => '2025-09-27 04:34:27',
                'accepted_at' => '2025-09-27 04:34:27',
                'expires_at' => NULL,
                'duration_hours' => 168,
                'is_public' => 1,
                'created_at' => '2025-09-27 04:34:27',
                'updated_at' => '2025-09-27 04:34:27',
            ]
        ];
        
        DB::table("treaties")->insert($dataTables);
    }
}