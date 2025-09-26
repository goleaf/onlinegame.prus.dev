<?php

namespace Database\Factories\Game;

use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Alliance>
 */
class AllianceFactory extends Factory
{
    protected $model = Alliance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'world_id' => World::factory(),
            'tag' => strtoupper($this->faker->lexify('???')),
            'name' => $this->faker->company() . ' Alliance',
            'description' => $this->faker->paragraph(),
            'leader_id' => Player::factory(),
            'points' => $this->faker->numberBetween(1000, 100000),
            'villages_count' => $this->faker->numberBetween(10, 1000),
            'members_count' => $this->faker->numberBetween(5, 100),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the alliance is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an alliance with specific world.
     */
    public function world($worldId): static
    {
        return $this->state(fn (array $attributes) => [
            'world_id' => $worldId,
        ]);
    }
}
