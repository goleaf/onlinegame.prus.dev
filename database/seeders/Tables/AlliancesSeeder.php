<?php
namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlliancesSeeder extends Seeder
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
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'EGP',
                'name' => 'Halvorson, Jaskolski and Harvey Alliance',
                'description' => 'Doloribus iusto quo et a. Dolorem autem omnis ut sed non qui. Qui et fugit facilis esse recusandae consectetur eos. Est unde neque et ducimus dolores et.',
                'leader_id' => 49,
                'points' => 2283,
                'villages_count' => 87,
                'members_count' => 42,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:53',
                'updated_at' => '2025-09-25 23:37:53',
            ],
            [
                'id' => 2,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'KEO',
                'name' => 'White, Torphy and Renner Alliance',
                'description' => 'Error nisi alias officia aspernatur. Rerum sequi ullam non libero ullam. Non fugit autem sint nemo in.',
                'leader_id' => 10,
                'points' => 16686,
                'villages_count' => 91,
                'members_count' => 37,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:53',
                'updated_at' => '2025-09-25 23:37:53',
            ],
            [
                'id' => 3,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'MUW',
                'name' => 'McCullough and Sons Alliance',
                'description' => 'Recusandae aliquam voluptates cum asperiores ducimus totam et. Totam asperiores qui aut dignissimos fugit repudiandae ad. Alias perferendis ratione sed.',
                'leader_id' => 28,
                'points' => 79752,
                'villages_count' => 22,
                'members_count' => 39,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:53',
                'updated_at' => '2025-09-25 23:37:53',
            ],
            [
                'id' => 4,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'DWG',
                'name' => 'Quitzon, Wintheiser and Johnston Alliance',
                'description' => 'Quibusdam et consectetur quisquam et rerum. Ea id recusandae dolorum perspiciatis facilis et. Et dignissimos corporis quia beatae tenetur.',
                'leader_id' => 29,
                'points' => 25269,
                'villages_count' => 73,
                'members_count' => 23,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:53',
                'updated_at' => '2025-09-25 23:37:53',
            ],
            [
                'id' => 5,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'JMQ',
                'name' => 'Grant PLC Alliance',
                'description' => 'Dolor ipsum et rerum quam ducimus temporibus. Quia autem adipisci suscipit omnis voluptatem accusantium.',
                'leader_id' => 12,
                'points' => 75747,
                'villages_count' => 15,
                'members_count' => 43,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:53',
                'updated_at' => '2025-09-25 23:37:53',
            ],
            [
                'id' => 6,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'UKS',
                'name' => 'Tromp-Abbott Alliance',
                'description' => 'Deleniti distinctio quas repellendus debitis error rerum. Aperiam temporibus labore praesentium facilis odit velit. Aut quidem ipsa qui modi deserunt. Velit qui nemo quo ullam sit perspiciatis.',
                'leader_id' => 29,
                'points' => 72846,
                'villages_count' => 40,
                'members_count' => 22,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:54',
                'updated_at' => '2025-09-25 23:37:54',
            ],
            [
                'id' => 7,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'WUG',
                'name' => 'Renner PLC Alliance',
                'description' => 'Dignissimos odit autem nam inventore est. Id iste sunt eius ipsum atque.',
                'leader_id' => 2,
                'points' => 21694,
                'villages_count' => 91,
                'members_count' => 36,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:54',
                'updated_at' => '2025-09-25 23:37:54',
            ],
            [
                'id' => 8,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'MNB',
                'name' => 'Mayer, Cronin and Koss Alliance',
                'description' => 'Voluptas suscipit voluptatibus sunt. Enim assumenda et modi molestiae ut animi quo blanditiis. Consequatur aliquam est vero molestiae culpa expedita sint. Voluptate aut excepturi voluptatem eveniet excepturi.',
                'leader_id' => 1,
                'points' => 68955,
                'villages_count' => 64,
                'members_count' => 30,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:54',
                'updated_at' => '2025-09-25 23:37:54',
            ],
            [
                'id' => 9,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'LEP',
                'name' => 'Mertz Group Alliance',
                'description' => 'Neque quisquam accusantium ipsum similique omnis. Praesentium cumque ullam placeat rerum eaque qui suscipit. Quae quaerat aperiam voluptatem optio ut voluptas tempora. Consequatur deserunt soluta omnis laudantium omnis sequi.',
                'leader_id' => 45,
                'points' => 93336,
                'villages_count' => 25,
                'members_count' => 40,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:54',
                'updated_at' => '2025-09-25 23:37:54',
            ],
            [
                'id' => 10,
                'reference_number' => NULL,
                'world_id' => 1,
                'tag' => 'DJF',
                'name' => 'Bernhard Group Alliance',
                'description' => 'Facere nihil id animi nihil voluptas. Voluptatibus sunt et ea et excepturi non nihil. Neque laboriosam quia voluptas.',
                'leader_id' => 48,
                'points' => 61379,
                'villages_count' => 63,
                'members_count' => 47,
                'is_active' => 1,
                'created_at' => '2025-09-25 23:37:54',
                'updated_at' => '2025-09-25 23:37:54',
            ],
            [
                'id' => 11,
                'reference_number' => 'ALL-2025090002',
                'world_id' => 1,
                'tag' => 'TEST',
                'name' => 'Test Alliance',
                'description' => 'Test alliance description',
                'leader_id' => 1,
                'points' => 0,
                'villages_count' => 0,
                'members_count' => 0,
                'is_active' => 1,
                'created_at' => '2025-09-27 01:03:13',
                'updated_at' => '2025-09-27 01:03:13',
            ]
        ];
        
        DB::table("alliances")->insert($dataTables);
    }
}