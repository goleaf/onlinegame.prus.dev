<?php

namespace Database\Factories\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Village>
 */
class VillageFactory extends Factory
{
    protected $model = Village::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'world_id' => World::factory(),
            'name' => $this->faker->city().' Village',
            'x_coordinate' => $this->faker->numberBetween(0, 400),
            'y_coordinate' => $this->faker->numberBetween(0, 400),
            'population' => $this->faker->numberBetween(100, 5000),
            'is_capital' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the village is a capital.
     */
    public function capital(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_capital' => true,
            'population' => $this->faker->numberBetween(2000, 10000),
        ]);
    }

    /**
     * Indicate that the village is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a village at specific coordinates.
     */
    public function atCoordinates(int $x, int $y): static
    {
        return $this->state(fn (array $attributes) => [
            'x_coordinate' => $x,
            'y_coordinate' => $y,
        ]);
    }
}
