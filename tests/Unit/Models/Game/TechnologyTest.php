<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Player;
use App\Models\Game\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyTest extends TestCase
{
    use RefreshDatabase;

    protected Technology $technology;

    protected function setUp(): void
    {
        parent::setUp();
        $this->technology = new Technology();
    }

    /**
     * @test
     */
    public function it_can_create_technology()
    {
        $technology = Technology::create([
            'name' => 'Advanced Agriculture',
            'description' => 'Improves crop production',
            'category' => 'agriculture',
            'max_level' => 20,
            'base_costs' => ['wood' => 100, 'clay' => 50, 'iron' => 25, 'crop' => 10],
            'cost_multiplier' => ['wood' => 1.2, 'clay' => 1.2, 'iron' => 1.2, 'crop' => 1.2],
            'research_time_base' => 3600,
            'research_time_multiplier' => 1.5,
            'requirements' => ['building' => 'academy', 'level' => 5],
            'effects' => ['crop_production' => 0.1],
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Technology::class, $technology);
        $this->assertEquals('Advanced Agriculture', $technology->name);
        $this->assertEquals('agriculture', $technology->category);
        $this->assertEquals(20, $technology->max_level);
        $this->assertEquals(3600, $technology->research_time_base);
        $this->assertTrue($technology->is_active);
    }

    /**
     * @test
     */
    public function it_casts_base_costs_to_array()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'base_costs' => ['wood' => 100, 'clay' => 50],
        ]);

        $this->assertIsArray($technology->base_costs);
        $this->assertEquals(['wood' => 100, 'clay' => 50], $technology->base_costs);
    }

    /**
     * @test
     */
    public function it_casts_cost_multiplier_to_array()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'cost_multiplier' => ['wood' => 1.2, 'clay' => 1.2],
        ]);

        $this->assertIsArray($technology->cost_multiplier);
        $this->assertEquals(['wood' => 1.2, 'clay' => 1.2], $technology->cost_multiplier);
    }

    /**
     * @test
     */
    public function it_casts_requirements_to_array()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'requirements' => ['building' => 'academy', 'level' => 5],
        ]);

        $this->assertIsArray($technology->requirements);
        $this->assertEquals(['building' => 'academy', 'level' => 5], $technology->requirements);
    }

    /**
     * @test
     */
    public function it_casts_effects_to_array()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'effects' => ['crop_production' => 0.1, 'wood_production' => 0.05],
        ]);

        $this->assertIsArray($technology->effects);
        $this->assertEquals(['crop_production' => 0.1, 'wood_production' => 0.05], $technology->effects);
    }

    /**
     * @test
     */
    public function it_casts_is_active_to_boolean()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'is_active' => false,
        ]);

        $this->assertFalse($technology->is_active);
    }

    /**
     * @test
     */
    public function it_belongs_to_many_players()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
        ]);

        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        $technology->players()->attach($player1->id, [
            'level' => 5,
            'researched_at' => now(),
        ]);

        $technology->players()->attach($player2->id, [
            'level' => 3,
            'researched_at' => now()->subHour(),
        ]);

        $this->assertCount(2, $technology->players);
        $this->assertTrue($technology->players->contains($player1));
        $this->assertTrue($technology->players->contains($player2));
    }

    /**
     * @test
     */
    public function it_has_by_category_scope()
    {
        Technology::create([
            'name' => 'Agriculture Technology',
            'description' => 'Test description',
            'category' => 'agriculture',
            'max_level' => 10,
        ]);

        Technology::create([
            'name' => 'Military Technology',
            'description' => 'Test description',
            'category' => 'military',
            'max_level' => 10,
        ]);

        $agricultureTechnologies = Technology::byCategory('agriculture')->get();
        $this->assertCount(1, $agricultureTechnologies);
        $this->assertEquals('Agriculture Technology', $agricultureTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_has_active_scope()
    {
        Technology::create([
            'name' => 'Active Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'is_active' => true,
        ]);

        Technology::create([
            'name' => 'Inactive Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'is_active' => false,
        ]);

        $activeTechnologies = Technology::active()->get();
        $this->assertCount(1, $activeTechnologies);
        $this->assertEquals('Active Technology', $activeTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_has_by_max_level_scope()
    {
        Technology::create([
            'name' => 'High Level Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 20,
        ]);

        Technology::create([
            'name' => 'Low Level Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 5,
        ]);

        $highLevelTechnologies = Technology::byMaxLevel(10)->get();
        $this->assertCount(1, $highLevelTechnologies);
        $this->assertEquals('High Level Technology', $highLevelTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_has_by_research_time_scope()
    {
        Technology::create([
            'name' => 'Long Research Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'research_time_base' => 7200,
        ]);

        Technology::create([
            'name' => 'Quick Research Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'research_time_base' => 1800,
        ]);

        $longResearchTechnologies = Technology::byResearchTime(3600)->get();
        $this->assertCount(1, $longResearchTechnologies);
        $this->assertEquals('Long Research Technology', $longResearchTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_has_popular_scope()
    {
        Technology::create([
            'name' => 'Popular Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
        ]);

        Technology::create([
            'name' => 'Unpopular Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
        ]);

        $popularTechnologies = Technology::popular(1)->get();
        $this->assertCount(1, $popularTechnologies);
    }

    /**
     * @test
     */
    public function it_has_recent_scope()
    {
        Technology::create([
            'name' => 'Recent Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'created_at' => now()->subDays(3),
        ]);

        Technology::create([
            'name' => 'Old Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'created_at' => now()->subDays(40),
        ]);

        $recentTechnologies = Technology::recent(30)->get();
        $this->assertCount(1, $recentTechnologies);
        $this->assertEquals('Recent Technology', $recentTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_has_search_scope()
    {
        Technology::create([
            'name' => 'Agriculture Technology',
            'description' => 'Improves farming',
            'category' => 'agriculture',
            'max_level' => 10,
        ]);

        Technology::create([
            'name' => 'Military Technology',
            'description' => 'Improves combat',
            'category' => 'military',
            'max_level' => 10,
        ]);

        $searchResults = Technology::search('agriculture')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Agriculture Technology', $searchResults->first()->name);
    }

    /**
     * @test
     */
    public function it_has_with_player_info_scope()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
        ]);

        $technologyWithInfo = Technology::withPlayerInfo()->first();

        $this->assertTrue($technologyWithInfo->relationLoaded('players'));
    }

    /**
     * @test
     */
    public function it_has_by_difficulty_scope()
    {
        Technology::create([
            'name' => 'Easy Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'research_time_base' => 1800,  // 0.5 hours
        ]);

        Technology::create([
            'name' => 'Hard Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'research_time_base' => 7200,  // 2 hours
        ]);

        $easyTechnologies = Technology::byDifficulty(1)->get();
        $this->assertCount(1, $easyTechnologies);
        $this->assertEquals('Easy Technology', $easyTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_can_get_cached_technologies()
    {
        Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
            'is_active' => true,
        ]);

        $cachedTechnologies = Technology::getCachedTechnologies();

        $this->assertCount(1, $cachedTechnologies);
        $this->assertEquals('Test Technology', $cachedTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_can_get_cached_technologies_with_filters()
    {
        Technology::create([
            'name' => 'Agriculture Technology',
            'description' => 'Test description',
            'category' => 'agriculture',
            'max_level' => 10,
            'is_active' => true,
        ]);

        Technology::create([
            'name' => 'Military Technology',
            'description' => 'Test description',
            'category' => 'military',
            'max_level' => 10,
            'is_active' => true,
        ]);

        $agricultureTechnologies = Technology::getCachedTechnologies(null, ['category' => 'agriculture']);

        $this->assertCount(1, $agricultureTechnologies);
        $this->assertEquals('Agriculture Technology', $agricultureTechnologies->first()->name);
    }

    /**
     * @test
     */
    public function it_can_get_cached_technologies_with_search()
    {
        Technology::create([
            'name' => 'Advanced Agriculture',
            'description' => 'Test description',
            'category' => 'agriculture',
            'max_level' => 10,
            'is_active' => true,
        ]);

        Technology::create([
            'name' => 'Basic Military',
            'description' => 'Test description',
            'category' => 'military',
            'max_level' => 10,
            'is_active' => true,
        ]);

        $searchResults = Technology::getCachedTechnologies(null, ['search' => 'agriculture']);

        $this->assertCount(1, $searchResults);
        $this->assertEquals('Advanced Agriculture', $searchResults->first()->name);
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $technology = Technology::create([
            'name' => 'Test Technology',
            'description' => 'Test description',
            'category' => 'test',
            'max_level' => 10,
        ]);

        $this->assertNotNull($technology->reference_number);
        $this->assertStringStartsWith('TECH-', $technology->reference_number);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_mass_assignment()
    {
        $data = [
            'name' => 'Mass Assignment Test',
            'description' => 'Test mass assignment',
            'category' => 'test',
            'max_level' => 15,
            'base_costs' => ['wood' => 200, 'clay' => 100],
            'cost_multiplier' => ['wood' => 1.3, 'clay' => 1.3],
            'research_time_base' => 5400,
            'research_time_multiplier' => 1.8,
            'requirements' => ['building' => 'academy', 'level' => 10],
            'effects' => ['production_bonus' => 0.2],
            'is_active' => false,
        ];

        $technology = Technology::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $technology->$key);
        }
    }

    /**
     * @test
     */
    public function it_has_reference_trait()
    {
        $this->assertTrue(method_exists($this->technology, 'generateReference'));
    }

    /**
     * @test
     */
    public function it_has_auditing_trait()
    {
        $this->assertTrue(method_exists($this->technology, 'audits'));
    }

    /**
     * @test
     */
    public function it_has_taxonomy_trait()
    {
        $this->assertTrue(method_exists($this->technology, 'taxonomies'));
    }
}
