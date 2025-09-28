<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user first
        $this->call(SimpleAdminSeeder::class);

        // Create game data
        $this->call(GameDataSeeder::class);

        // Create additional test users
        User::factory(5)->create();
        $this->call(\Database\Seeders\Tables\WorldsSeeder::class);
        $this->call(\Database\Seeders\Tables\PlayersSeeder::class);
        $this->call(\Database\Seeders\Tables\VillagesSeeder::class);
        $this->call(\Database\Seeders\Tables\AchievementsSeeder::class);
        $this->call(\Database\Seeders\Tables\BuildingTypesSeeder::class);
        $this->call(\Database\Seeders\Tables\BuildingsSeeder::class);
        $this->call(\Database\Seeders\Tables\QuestsSeeder::class);
        $this->call(\Database\Seeders\Tables\UnitTypesSeeder::class);
        // $this->call(\Database\Seeders\Tables\UsersTestSeeder::class); // Commented out - seeder may not exist
        // $this->call(\Database\Seeders\Tables\ActivePlayersSeeder::class); // Commented out - seeder may not exist
        $this->call(\Database\Seeders\Tables\GameConfigsSeeder::class);
        // $this->call(\Database\Seeders\Tables\GameEventsSeeder::class); // Commented out - seeder may not exist
        $this->call(\Database\Seeders\Tables\GameNotificationsSeeder::class);
        $this->call(\Database\Seeders\Tables\GameTasksSeeder::class);
        // $this->call(\Database\Seeders\Tables\PlayerQuestsSeeder::class); // Commented out - has unique constraint issues
        $this->call(\Database\Seeders\Tables\AccessLogsSeeder::class);
        $this->call(\Database\Seeders\Tables\ActivityLogSeeder::class);
        $this->call(\Database\Seeders\Tables\AlliancesSeeder::class);
        $this->call(\Database\Seeders\Tables\AllianceMembersSeeder::class);
        $this->call(\Database\Seeders\Tables\AllianceWarsSeeder::class);
        $this->call(\Database\Seeders\Tables\AttacksSeeder::class);
        $this->call(\Database\Seeders\Tables\AuditsSeeder::class);
        $this->call(\Database\Seeders\Tables\BattlesSeeder::class);
        $this->call(\Database\Seeders\Tables\BuildingQueueSeeder::class);
        $this->call(\Database\Seeders\Tables\BuildingQueuesSeeder::class);
        $this->call(\Database\Seeders\Tables\CacheLocksSeeder::class);
        $this->call(\Database\Seeders\Tables\CommentsSeeder::class);
        $this->call(\Database\Seeders\Tables\GameEventsSeeder::class);
        $this->call(\Database\Seeders\Tables\HeroesSeeder::class);
        $this->call(\Database\Seeders\Tables\JobBatchesSeeder::class);
        $this->call(\Database\Seeders\Tables\MarketTradesSeeder::class);
        $this->call(\Database\Seeders\Tables\MediaSeeder::class);
        $this->call(\Database\Seeders\Tables\ModelAuditsSeeder::class);
        $this->call(\Database\Seeders\Tables\ModelHasPermissionsSeeder::class);
        $this->call(\Database\Seeders\Tables\ModelHasRolesSeeder::class);
        $this->call(\Database\Seeders\Tables\ModelReferenceCountersSeeder::class);
        $this->call(\Database\Seeders\Tables\MovementsSeeder::class);
        $this->call(\Database\Seeders\Tables\NotablesSeeder::class);
        $this->call(\Database\Seeders\Tables\PasswordResetTokensSeeder::class);
        $this->call(\Database\Seeders\Tables\PermissionsSeeder::class);
        $this->call(\Database\Seeders\Tables\PersonalAccessTokensSeeder::class);
        $this->call(\Database\Seeders\Tables\PlayerAchievementsSeeder::class);
        $this->call(\Database\Seeders\Tables\PlayerNotesSeeder::class);
        $this->call(\Database\Seeders\Tables\PlayerStatisticsSeeder::class);
        $this->call(\Database\Seeders\Tables\PlayerTasksSeeder::class);
        $this->call(\Database\Seeders\Tables\PlayerTechnologiesSeeder::class);
        $this->call(\Database\Seeders\Tables\ReportsSeeder::class);
        $this->call(\Database\Seeders\Tables\ResourceProductionLogsSeeder::class);
        $this->call(\Database\Seeders\Tables\ResourcesSeeder::class);
        $this->call(\Database\Seeders\Tables\RoleHasPermissionsSeeder::class);
        $this->call(\Database\Seeders\Tables\RolesSeeder::class);
        $this->call(\Database\Seeders\Tables\SiegeWeaponsSeeder::class);
        $this->call(\Database\Seeders\Tables\SubscriptionItemsSeeder::class);
        $this->call(\Database\Seeders\Tables\SubscriptionsSeeder::class);
        $this->call(\Database\Seeders\Tables\TaggablesSeeder::class);
        $this->call(\Database\Seeders\Tables\TagsSeeder::class);
        $this->call(\Database\Seeders\Tables\TaxonomablesSeeder::class);
        $this->call(\Database\Seeders\Tables\TaxonomiesSeeder::class);
        $this->call(\Database\Seeders\Tables\TechnologiesSeeder::class);
        $this->call(\Database\Seeders\Tables\TelescopeEntriesSeeder::class);
        $this->call(\Database\Seeders\Tables\TelescopeEntriesTagsSeeder::class);
        $this->call(\Database\Seeders\Tables\TelescopeMonitoringSeeder::class);
        $this->call(\Database\Seeders\Tables\TradeOffersSeeder::class);
        $this->call(\Database\Seeders\Tables\TrainingQueuesSeeder::class);
        $this->call(\Database\Seeders\Tables\TreatiesSeeder::class);
        $this->call(\Database\Seeders\Tables\TroopsSeeder::class);
        $this->call(\Database\Seeders\Tables\UsersSeeder::class);
    }
}
