<?php

namespace Database\Factories;

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
            'name' => $this->faker->city() . ' ' . $this->faker->randomElement(['Village', 'Settlement', 'Town', 'City']),
            'x_coordinate' => $this->faker->numberBetween(-200, 200),
            'y_coordinate' => $this->faker->numberBetween(-200, 200),
            'population' => $this->faker->numberBetween(0, 2000),
            'culture_points' => $this->faker->numberBetween(0, 10000),
            'is_capital' => false,
            'is_active' => true,
            'wood' => $this->faker->numberBetween(1000, 10000),
            'clay' => $this->faker->numberBetween(1000, 10000),
            'iron' => $this->faker->numberBetween(1000, 10000),
            'crop' => $this->faker->numberBetween(1000, 10000),
            'wood_capacity' => $this->faker->numberBetween(10000, 100000),
            'clay_capacity' => $this->faker->numberBetween(10000, 100000),
            'iron_capacity' => $this->faker->numberBetween(10000, 100000),
            'crop_capacity' => $this->faker->numberBetween(10000, 100000),
        ];
    }

    /**
     * Create a capital village
     */
    public function capital(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_capital' => true,
            'population' => $this->faker->numberBetween(1000, 5000),
            'culture_points' => $this->faker->numberBetween(5000, 50000),
        ]);
    }

    /**
     * Create a new village
     */
    public function newVillage(): static
    {
        return $this->state(fn (array $attributes) => [
            'population' => 0,
            'culture_points' => 0,
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
            'wood_capacity' => 10000,
            'clay_capacity' => 10000,
            'iron_capacity' => 10000,
            'crop_capacity' => 10000,
        ]);
    }

    /**
     * Create a developed village
     */
    public function developed(): static
    {
        return $this->state(fn (array $attributes) => [
            'population' => $this->faker->numberBetween(2000, 10000),
            'culture_points' => $this->faker->numberBetween(10000, 100000),
            'wood' => $this->faker->numberBetween(50000, 200000),
            'clay' => $this->faker->numberBetween(50000, 200000),
            'iron' => $this->faker->numberBetween(50000, 200000),
            'crop' => $this->faker->numberBetween(50000, 200000),
            'wood_capacity' => $this->faker->numberBetween(500000, 2000000),
            'clay_capacity' => $this->faker->numberBetween(500000, 2000000),
            'iron_capacity' => $this->faker->numberBetween(500000, 2000000),
            'crop_capacity' => $this->faker->numberBetween(500000, 2000000),
        ]);
    }

    /**
     * Create a village at specific coordinates
     */
    public function atCoordinates(int $x, int $y): static
    {
        return $this->state(fn (array $attributes) => [
            'x_coordinate' => $x,
            'y_coordinate' => $y,
        ]);
    }
}
