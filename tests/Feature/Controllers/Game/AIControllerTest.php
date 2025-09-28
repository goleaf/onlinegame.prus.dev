<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AIControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_ai_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/ai/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'available_models',
                'rate_limits',
                'usage_stats',
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_ai_response()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Generate a quest for a new player',
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 500,
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate', $requestData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'response',
                'usage',
                'model',
                'generated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_quest_with_ai()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Create a quest for a level 5 player',
            'quest_type' => 'combat',
            'difficulty' => 'medium',
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate-quest', $requestData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'quest' => [
                    'title',
                    'description',
                    'requirements',
                    'rewards',
                    'difficulty',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_artifact_with_ai()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Create a legendary weapon for a warrior',
            'artifact_type' => 'weapon',
            'rarity' => 'legendary',
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate-artifact', $requestData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'artifact' => [
                    'name',
                    'description',
                    'type',
                    'rarity',
                    'effects',
                    'requirements',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_battle_scenario_with_ai()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Create a battle scenario between two armies',
            'battle_type' => 'siege',
            'difficulty' => 'hard',
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate-battle', $requestData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'battle_scenario' => [
                    'title',
                    'description',
                    'attacker_forces',
                    'defender_forces',
                    'terrain_effects',
                    'weather_conditions',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_generate_dialogue_with_ai()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Create dialogue for a village elder',
            'character_type' => 'elder',
            'mood' => 'wise',
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate-dialogue', $requestData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'dialogue' => [
                    'character_name',
                    'character_type',
                    'lines',
                    'mood',
                    'context',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_ai_usage_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/ai/usage');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_requests',
                'requests_today',
                'tokens_used',
                'cost_estimate',
                'popular_models',
                'usage_by_hour',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_ai_models()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/ai/models');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'available_models' => [
                    '*' => [
                        'name',
                        'description',
                        'max_tokens',
                        'cost_per_token',
                        'capabilities',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_validates_ai_generation_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/ai/generate', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['prompt']);
    }

    /**
     * @test
     */
    public function it_validates_model_parameter()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Test prompt',
            'model' => 'invalid-model',
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate', $requestData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['model']);
    }

    /**
     * @test
     */
    public function it_validates_temperature_parameter()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Test prompt',
            'temperature' => 3.0,  // Invalid: exceeds max 2
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate', $requestData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['temperature']);
    }

    /**
     * @test
     */
    public function it_validates_max_tokens_parameter()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Test prompt',
            'max_tokens' => 5000,  // Invalid: exceeds max 4000
        ];

        $response = $this->actingAs($user)->post('/api/game/ai/generate', $requestData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['max_tokens']);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/ai/status');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_handles_ai_service_errors()
    {
        $user = User::factory()->create();

        $requestData = [
            'prompt' => 'Test prompt that will cause an error',
            'model' => 'gpt-3.5-turbo',
        ];

        // Mock AI service to return an error
        $this->mock(\App\Services\AIService::class, function ($mock): void {
            $mock
                ->shouldReceive('generateResponse')
                ->andThrow(new \Exception('AI service unavailable'));
        });

        $response = $this->actingAs($user)->post('/api/game/ai/generate', $requestData);

        $response
            ->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
