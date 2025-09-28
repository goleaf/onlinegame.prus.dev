<?php

namespace Tests\Feature\Livewire\Game;

use App\Livewire\Game\AllianceDetail;
use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AllianceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Player $player;

    private Alliance $alliance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->alliance = Alliance::factory()->create(['name' => 'Test Alliance']);
    }

    /**
     * @test
     */
    public function it_can_render_alliance_detail()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertStatus(200)
            ->assertSee('Test Alliance');
    }

    /**
     * @test
     */
    public function it_can_display_alliance_information()
    {
        $alliance = Alliance::factory()->create([
            'name' => 'Powerful Alliance',
            'tag' => 'PWR',
            'description' => 'A powerful alliance',
            'level' => 10,
            'experience' => 50000,
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $alliance->id])
            ->assertSee('Powerful Alliance')
            ->assertSee('PWR')
            ->assertSee('A powerful alliance')
            ->assertSee('Level 10')
            ->assertSee('50,000 XP');
    }

    /**
     * @test
     */
    public function it_can_display_alliance_members()
    {
        $member1 = Player::factory()->create();
        $member2 = Player::factory()->create();

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member1->id,
            'rank' => 'leader',
            'contribution_points' => 1000,
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member2->id,
            'rank' => 'member',
            'contribution_points' => 500,
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee($member1->name)
            ->assertSee($member2->name)
            ->assertSee('Leader')
            ->assertSee('Member');
    }

    /**
     * @test
     */
    public function it_can_display_alliance_statistics()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Total Members')
            ->assertSee('Total Villages')
            ->assertSee('Total Points')
            ->assertSee('Average Points per Member');
    }

    /**
     * @test
     */
    public function it_can_display_alliance_rankings()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Alliance Ranking')
            ->assertSee('World Ranking')
            ->assertSee('Continent Ranking');
    }

    /**
     * @test
     */
    public function it_can_apply_to_alliance()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->set('applicationMessage', 'I would like to join your alliance')
            ->call('applyToAlliance')
            ->assertSee('Application submitted successfully')
            ->assertEmitted('allianceApplicationSubmitted');
    }

    /**
     * @test
     */
    public function it_validates_application_message()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->set('applicationMessage', '')
            ->call('applyToAlliance')
            ->assertHasErrors(['applicationMessage']);
    }

    /**
     * @test
     */
    public function it_cannot_apply_to_alliance_if_already_member()
    {
        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $this->player->id,
            'rank' => 'member',
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->call('applyToAlliance')
            ->assertSee('You are already a member of this alliance');
    }

    /**
     * @test
     */
    public function it_can_view_alliance_wars()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Alliance Wars')
            ->assertSee('Active Wars')
            ->assertSee('War History');
    }

    /**
     * @test
     */
    public function it_can_view_alliance_diplomacy()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Diplomacy')
            ->assertSee('Allied Alliances')
            ->assertSee('Enemy Alliances')
            ->assertSee('Neutral Alliances');
    }

    /**
     * @test
     */
    public function it_can_view_alliance_achievements()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Alliance Achievements')
            ->assertSee('Recent Achievements')
            ->assertSee('Achievement Progress');
    }

    /**
     * @test
     */
    public function it_can_view_alliance_news()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Alliance News')
            ->assertSee('Recent Announcements')
            ->assertSee('Member Updates');
    }

    /**
     * @test
     */
    public function it_can_sort_members_by_rank()
    {
        $member1 = Player::factory()->create();
        $member2 = Player::factory()->create();
        $member3 = Player::factory()->create();

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member1->id,
            'rank' => 'member',
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member2->id,
            'rank' => 'leader',
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member3->id,
            'rank' => 'officer',
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->set('sortBy', 'rank')
            ->assertSeeInOrder(['Leader', 'Officer', 'Member']);
    }

    /**
     * @test
     */
    public function it_can_sort_members_by_contribution()
    {
        $member1 = Player::factory()->create();
        $member2 = Player::factory()->create();
        $member3 = Player::factory()->create();

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member1->id,
            'contribution_points' => 100,
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member2->id,
            'contribution_points' => 500,
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member3->id,
            'contribution_points' => 1000,
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->set('sortBy', 'contribution')
            ->assertSeeInOrder(['1000', '500', '100']);
    }

    /**
     * @test
     */
    public function it_can_search_members()
    {
        $member1 = Player::factory()->create(['name' => 'Alice']);
        $member2 = Player::factory()->create(['name' => 'Bob']);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member1->id,
        ]);

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member2->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->set('search', 'Alice')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    /**
     * @test
     */
    public function it_can_view_member_details()
    {
        $member = Player::factory()->create();

        AllianceMember::factory()->create([
            'alliance_id' => $this->alliance->id,
            'player_id' => $member->id,
            'rank' => 'officer',
            'contribution_points' => 750,
        ]);

        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->call('viewMember', $member->id)
            ->assertEmitted('showMemberDetails', $member->id);
    }

    /**
     * @test
     */
    public function it_can_refresh_alliance_data()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->call('refreshData')
            ->assertEmitted('allianceDataRefreshed');
    }

    /**
     * @test
     */
    public function it_handles_nonexistent_alliance()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => 99999])
            ->assertSee('Alliance not found');
    }

    /**
     * @test
     */
    public function it_can_toggle_member_list_view()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('List View')
            ->call('toggleView')
            ->assertSee('Grid View');
    }

    /**
     * @test
     */
    public function it_can_export_alliance_data()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->call('exportData')
            ->assertEmitted('allianceDataExported');
    }

    /**
     * @test
     */
    public function it_can_share_alliance_link()
    {
        Livewire::actingAs($this->user)
            ->test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->call('shareAlliance')
            ->assertEmitted('allianceShared');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        Livewire::test(AllianceDetail::class, ['allianceId' => $this->alliance->id])
            ->assertSee('Please login to view alliance details');
    }
}
