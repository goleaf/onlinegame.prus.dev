<?php

namespace Tests\Browser;

use App\Models\Game\Player;
use App\Models\Game\Quest;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class QuestManagerTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected $user;
    protected $player;
    protected $world;
    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create([
            'name' => 'Test Player',
            'email' => 'test@example.com',
        ]);

        $this->world = World::create([
            'name' => 'Test World',
            'description' => 'Test World Description',
            'is_active' => true,
        ]);

        $this->player = Player::create([
            'user_id' => $this->user->id,
            'world_id' => $this->world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
            'points' => 1000,
            'is_online' => true,
            'last_active_at' => now(),
        ]);

        $this->village = Village::create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 100,
            'culture_points' => 1000,
            'is_capital' => true,
        ]);

        // Create quests
        Quest::create([
            'name' => 'First Steps',
            'key' => 'first_steps',
            'description' => 'Build your first building',
            'type' => 'tutorial',
            'requirements' => json_encode(['buildings' => 1]),
            'rewards' => json_encode(['experience' => 100, 'wood' => 500]),
        ]);
    }

    public function test_quest_manager_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->assertSee('Test Player')
                ->assertSee('Roman')
                ->assertSee('1,000');  // Points
        });
    }

    public function test_quest_progress_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->assertSee('Quest Progress')
                ->assertSee('Total Quests')
                ->assertSee('Completed')
                ->assertSee('Active')
                ->assertSee('Available');
        });
    }

    public function test_available_quests_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->assertSee('Available Quests')
                ->assertSee('First Steps')
                ->assertSee('Build your first building')
                ->assertSee('Tutorial')
                ->assertSee('Rewards: 100 XP');
        });
    }

    public function test_active_quests_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->assertSee('Active Quests')
                ->assertSee('No active quests');
        });
    }

    public function test_completed_quests_display()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->assertSee('Completed Quests')
                ->assertSee('No completed quests');
        });
    }

    public function test_start_quest_button()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->assertSee('Start Quest');
        });
    }

    public function test_quest_details_modal()
    {
        $this->browse(function (Browser $browser) {
            $browser
                ->loginAs($this->user)
                ->visit('/game/quests')
                ->click('button:contains("Start Quest")')
                ->waitFor('.modal', 5)
                ->assertSee('First Steps')
                ->assertSee('Build your first building')
                ->assertSee('Requirements')
                ->assertSee('Rewards');
        });
    }
}
