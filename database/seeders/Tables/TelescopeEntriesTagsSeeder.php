<?php

namespace Database\Seeders\Tables;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TelescopeEntriesTagsSeeder extends Seeder
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
         */
        $dataTables = [
            [
                'entry_uuid' => '9ff96bfe-ae20-4965-8b0e-452aa7877ff0',
                'tag' => 'App\\Models\\Game\\Hero:1',
            ],
            [
                'entry_uuid' => '9ff96bfe-c081-45ab-aafa-5ce923858687',
                'tag' => 'App\\Models\\Game\\Hero:10',
            ],
            [
                'entry_uuid' => '9ff96bfe-c18b-42c9-af1b-caae177c71b2',
                'tag' => 'App\\Models\\Game\\Hero:11',
            ],
            [
                'entry_uuid' => '9ff96bfe-c291-4dd9-8508-6583e85bd6aa',
                'tag' => 'App\\Models\\Game\\Hero:12',
            ],
            [
                'entry_uuid' => '9ff96bfe-c391-41b4-beac-0f6fa5faa7eb',
                'tag' => 'App\\Models\\Game\\Hero:13',
            ],
            [
                'entry_uuid' => '9ff96bfe-c4a0-41a2-86ef-fa49cc9de2a3',
                'tag' => 'App\\Models\\Game\\Hero:14',
            ],
            [
                'entry_uuid' => '9ff96bfe-c5ad-4c5f-b9bf-ff9a48ff17c9',
                'tag' => 'App\\Models\\Game\\Hero:15',
            ],
            [
                'entry_uuid' => '9ff96bfe-c6b2-4c62-a235-356ba376b339',
                'tag' => 'App\\Models\\Game\\Hero:16',
            ],
            [
                'entry_uuid' => '9ff96bfe-c7bd-4b23-8065-3d1803ff2b40',
                'tag' => 'App\\Models\\Game\\Hero:17',
            ],
            [
                'entry_uuid' => '9ff96bfe-c8cf-44ca-ad96-9f22441526f1',
                'tag' => 'App\\Models\\Game\\Hero:18',
            ],
            [
                'entry_uuid' => '9ff96bfe-c9d9-4ec1-a55c-d0c8b21aef55',
                'tag' => 'App\\Models\\Game\\Hero:19',
            ],
            [
                'entry_uuid' => '9ff96bfe-b829-4730-940f-a235a8e42419',
                'tag' => 'App\\Models\\Game\\Hero:2',
            ],
            [
                'entry_uuid' => '9ff96bfe-cad8-4ccf-80d2-7bb18944aafb',
                'tag' => 'App\\Models\\Game\\Hero:20',
            ],
            [
                'entry_uuid' => '9ff96bfe-cbe9-4b42-b82b-7603fe98741a',
                'tag' => 'App\\Models\\Game\\Hero:21',
            ],
            [
                'entry_uuid' => '9ff96bfe-ccf8-4655-b0e6-e21fe7dddf31',
                'tag' => 'App\\Models\\Game\\Hero:22',
            ],
            [
                'entry_uuid' => '9ff96bfe-ce01-46b4-960b-c8a6216c1551',
                'tag' => 'App\\Models\\Game\\Hero:23',
            ],
            [
                'entry_uuid' => '9ff96bfe-cf03-49ec-87bd-8a67dc885e15',
                'tag' => 'App\\Models\\Game\\Hero:24',
            ],
            [
                'entry_uuid' => '9ff96bfe-d01e-4a30-921a-b7a542baf14e',
                'tag' => 'App\\Models\\Game\\Hero:25',
            ],
            [
                'entry_uuid' => '9ff96bfe-d126-46a0-a9ed-75b1ad926fcc',
                'tag' => 'App\\Models\\Game\\Hero:26',
            ],
            [
                'entry_uuid' => '9ff96bfe-d225-4fe3-9c6f-c633b0042939',
                'tag' => 'App\\Models\\Game\\Hero:27',
            ],
            [
                'entry_uuid' => '9ff96bfe-d31f-4a99-ad5b-abac2a01f27b',
                'tag' => 'App\\Models\\Game\\Hero:28',
            ],
            [
                'entry_uuid' => '9ff96bfe-d427-4b0f-b546-c132e0b07901',
                'tag' => 'App\\Models\\Game\\Hero:29',
            ],
            [
                'entry_uuid' => '9ff96bfe-b93b-485c-884e-002d1ea72e17',
                'tag' => 'App\\Models\\Game\\Hero:3',
            ],
            [
                'entry_uuid' => '9ff96bfe-d7ff-46d6-b80b-70bb3c986a31',
                'tag' => 'App\\Models\\Game\\Hero:30',
            ],
            [
                'entry_uuid' => '9ff96bfe-d902-4af6-a70b-e02325e11c86',
                'tag' => 'App\\Models\\Game\\Hero:31',
            ],
            [
                'entry_uuid' => '9ff96bfe-da08-449c-b2b6-5be0f9582cbe',
                'tag' => 'App\\Models\\Game\\Hero:32',
            ],
            [
                'entry_uuid' => '9ff96bfe-db0a-430f-8ed7-961d505e986d',
                'tag' => 'App\\Models\\Game\\Hero:33',
            ],
            [
                'entry_uuid' => '9ff96bfe-dc1e-4df8-a4d6-d4dc740a4fae',
                'tag' => 'App\\Models\\Game\\Hero:34',
            ],
            [
                'entry_uuid' => '9ff96bfe-dd25-41e9-8736-1abf356e23a9',
                'tag' => 'App\\Models\\Game\\Hero:35',
            ],
            [
                'entry_uuid' => '9ff96bfe-de34-4001-a7a2-c3db7b54de99',
                'tag' => 'App\\Models\\Game\\Hero:36',
            ],
            [
                'entry_uuid' => '9ff96bfe-df40-4630-a1cb-b1f717faa03c',
                'tag' => 'App\\Models\\Game\\Hero:37',
            ],
            [
                'entry_uuid' => '9ff96bfe-e041-4fe9-99cb-b61d13be63c2',
                'tag' => 'App\\Models\\Game\\Hero:38',
            ],
            [
                'entry_uuid' => '9ff96bfe-e14d-4ad1-81af-bc736b3875b0',
                'tag' => 'App\\Models\\Game\\Hero:39',
            ],
            [
                'entry_uuid' => '9ff96bfe-ba50-4ac0-8ec3-bea8af9966da',
                'tag' => 'App\\Models\\Game\\Hero:4',
            ],
            [
                'entry_uuid' => '9ff96bfe-e251-4004-bbd3-dbc0347f361d',
                'tag' => 'App\\Models\\Game\\Hero:40',
            ],
            [
                'entry_uuid' => '9ff96bfe-e355-4f88-9969-663f17051a2b',
                'tag' => 'App\\Models\\Game\\Hero:41',
            ],
            [
                'entry_uuid' => '9ff96bfe-e463-4c2b-9dc6-6da17f9c0184',
                'tag' => 'App\\Models\\Game\\Hero:42',
            ],
            [
                'entry_uuid' => '9ff96bfe-e568-4733-a587-cf76e5cc4d4c',
                'tag' => 'App\\Models\\Game\\Hero:43',
            ],
            [
                'entry_uuid' => '9ff96bfe-e674-4060-bd12-98e73a36e9a8',
                'tag' => 'App\\Models\\Game\\Hero:44',
            ],
            [
                'entry_uuid' => '9ff96bfe-e787-4919-9b04-6ac037aa749a',
                'tag' => 'App\\Models\\Game\\Hero:45',
            ],
            [
                'entry_uuid' => '9ff96bfe-e893-4454-b3ef-c6ab745e3926',
                'tag' => 'App\\Models\\Game\\Hero:46',
            ],
            [
                'entry_uuid' => '9ff96bfe-e994-4fb1-8381-bf930f9e7fe6',
                'tag' => 'App\\Models\\Game\\Hero:47',
            ],
            [
                'entry_uuid' => '9ff96bfe-ea9e-4503-910d-b8ef2024bf19',
                'tag' => 'App\\Models\\Game\\Hero:48',
            ],
            [
                'entry_uuid' => '9ff96bfe-eb9d-46a4-972f-9aa47516cda9',
                'tag' => 'App\\Models\\Game\\Hero:49',
            ],
            [
                'entry_uuid' => '9ff96bfe-bb5e-462c-b944-2326fa7ad4df',
                'tag' => 'App\\Models\\Game\\Hero:5',
            ],
            [
                'entry_uuid' => '9ff96bfe-eccf-4494-b9a2-c883a5679286',
                'tag' => 'App\\Models\\Game\\Hero:50',
            ],
            [
                'entry_uuid' => '9ff96bfe-ee9b-4fb3-addf-63c30fd3a6fb',
                'tag' => 'App\\Models\\Game\\Hero:51',
            ],
            [
                'entry_uuid' => '9ff96bfe-ef89-413e-9a9d-aac9e60d18cf',
                'tag' => 'App\\Models\\Game\\Hero:52',
            ],
            [
                'entry_uuid' => '9ff96bfe-f081-416f-94f5-6e71ff37c458',
                'tag' => 'App\\Models\\Game\\Hero:53',
            ],
            [
                'entry_uuid' => '9ff96bfe-f192-4a0d-b2f2-8c7d69a602d7',
                'tag' => 'App\\Models\\Game\\Hero:54',
            ],
            [
                'entry_uuid' => '9ff96bfe-f2a4-414a-a544-2bbbcb838c7b',
                'tag' => 'App\\Models\\Game\\Hero:55',
            ],
            [
                'entry_uuid' => '9ff96bfe-f3bb-493e-b20f-a4eb8d063703',
                'tag' => 'App\\Models\\Game\\Hero:56',
            ],
            [
                'entry_uuid' => '9ff96bfe-f4d2-4c30-b5bf-e3d34aa06b5a',
                'tag' => 'App\\Models\\Game\\Hero:57',
            ],
            [
                'entry_uuid' => '9ff96bfe-f5d4-445b-a139-5533a7bc0076',
                'tag' => 'App\\Models\\Game\\Hero:58',
            ],
            [
                'entry_uuid' => '9ff96bfe-f6dd-41d3-81a8-0adc58097509',
                'tag' => 'App\\Models\\Game\\Hero:59',
            ],
            [
                'entry_uuid' => '9ff96bfe-bc66-46b6-8d1c-9788da19066d',
                'tag' => 'App\\Models\\Game\\Hero:6',
            ],
            [
                'entry_uuid' => '9ff96bfe-f7e4-49ca-b284-ac0423221d0a',
                'tag' => 'App\\Models\\Game\\Hero:60',
            ],
            [
                'entry_uuid' => '9ff96bfe-f8ef-48e6-b37e-2a35c935348e',
                'tag' => 'App\\Models\\Game\\Hero:61',
            ],
            [
                'entry_uuid' => '9ff96bfe-bd75-4340-9306-cb4824955ae8',
                'tag' => 'App\\Models\\Game\\Hero:7',
            ],
            [
                'entry_uuid' => '9ff96bfe-be79-41af-8b8d-0a40f9f2fe6f',
                'tag' => 'App\\Models\\Game\\Hero:8',
            ],
            [
                'entry_uuid' => '9ff96bfe-bf74-4d41-8fc1-6b2c537078fa',
                'tag' => 'App\\Models\\Game\\Hero:9',
            ],
            [
                'entry_uuid' => '9ff96b57-7ee7-42df-a91d-22e320bdc493',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96bfe-8d79-4eb6-9aae-ee09066882e5',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96c32-23d3-4241-b86b-248431732e03',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96d19-a725-4c84-88ca-b2fbeb008500',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96d37-48e5-4bc7-b789-7dd4640f3ae2',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96d4a-0678-4c4a-bbb1-e754cc2a0c90',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96db5-cb71-4a55-b8cb-c6b287d42261',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96dd4-0045-4591-88c6-748a670eaada',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff970f0-8ed8-4e65-a622-55c1117699aa',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff97104-f19d-408c-b33e-f67f0ca67c06',
                'tag' => 'App\\Models\\Game\\Player',
            ],
            [
                'entry_uuid' => '9ff96b57-a11c-470d-9a5d-76e46f480acf',
                'tag' => 'App\\Models\\Game\\Village',
            ],
            [
                'entry_uuid' => '9ff96bfe-9cd0-43bd-a248-1da7c32d4bf9',
                'tag' => 'App\\Models\\Game\\Village',
            ],
            [
                'entry_uuid' => '9ff96c32-14b1-42b5-ac3f-5bc74740adf5',
                'tag' => 'App\\Models\\Game\\Village',
            ],
            [
                'entry_uuid' => '9ff96d19-995f-4d5a-b095-14012e64d24d',
                'tag' => 'App\\Models\\Game\\Village',
            ],
            [
                'entry_uuid' => '9ff96d49-f91e-4a7f-9c46-54e3f6404a1e',
                'tag' => 'App\\Models\\Game\\Village',
            ],
            [
                'entry_uuid' => '9ff96e80-1a58-4da6-81f8-cfd61ce87dfb',
                'tag' => 'App\\Models\\Game\\World',
            ],
            [
                'entry_uuid' => '9ff96ed0-24eb-408d-94f5-50680e9248ae',
                'tag' => 'App\\Models\\Game\\World',
            ],
            [
                'entry_uuid' => '9ff96f42-f72f-4a17-ab7f-de0921a72a3a',
                'tag' => 'App\\Models\\Game\\World',
            ],
            [
                'entry_uuid' => '9ff96fcd-c284-4011-8e5a-9e188d160be5',
                'tag' => 'App\\Models\\Game\\World',
            ],
            [
                'entry_uuid' => '9ff9705c-9f62-4b56-8991-0ff4bccffb82',
                'tag' => 'App\\Models\\Game\\World',
            ],
            [
                'entry_uuid' => '9ff96b61-da12-45db-bcf7-9dec47b0fe79',
                'tag' => 'LaraUtilX\\Models\\AccessLog:1',
            ],
        ];

        foreach ($dataTables as $data) {
            DB::table('telescope_entries_tags')->updateOrInsert(['entry_uuid' => $data['entry_uuid'], 'tag' => $data['tag']], $data);
        }
    }
}
