<?php

namespace Database\Factories;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'village_id' => Village::factory(),
            'building_type_id' => BuildingType::factory(),
            'name' => $this->faker->words(2, true),
            'level' => $this->faker->numberBetween(1, 20),
            'x' => $this->faker->numberBetween(0, 18),
            'y' => $this->faker->numberBetween(0, 18),
            'is_active' => true,
            'upgrade_started_at' => null,
            'upgrade_completed_at' => null,
            'metadata' => null,
        ];
    }

    /**
     * Create a level 1 building
     */
    public function level1(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 1,
            'upgrade_started_at' => null,
            'upgrade_completed_at' => null,
        ]);
    }

    /**
     * Create a high-level building
     */
    public function highLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => $this->faker->numberBetween(10, 20),
        ]);
    }

    /**
     * Create a building that's currently upgrading
     */
    public function upgrading(): static
    {
        return $this->state(fn (array $attributes) => [
            'upgrade_started_at' => now(),
            'upgrade_completed_at' => now()->addHours($this->faker->numberBetween(1, 24)),
        ]);
    }

    /**
     * Create a completed building
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'upgrade_started_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'upgrade_completed_at' => now(),
        ]);
    }

    /**
     * Create a building at specific coordinates
     */
    public function atCoordinates(int $x, int $y): static
    {
        return $this->state(fn (array $attributes) => [
            'x' => $x,
            'y' => $y,
        ]);
    }
}
