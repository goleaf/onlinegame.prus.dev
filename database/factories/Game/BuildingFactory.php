<?php

namespace Database\Factories\Game;

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
            'metadata' => [],
        ];
    }

    /**
     * Indicate that the building is upgrading.
     */
    public function upgrading(): static
    {
        return $this->state(fn (array $attributes) => [
            'upgrade_started_at' => now(),
            'upgrade_completed_at' => now()->addHours($this->faker->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate that the building is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a building at specific coordinates.
     */
    public function atPosition(int $x, int $y): static
    {
        return $this->state(fn (array $attributes) => [
            'x' => $x,
            'y' => $y,
        ]);
    }
}
