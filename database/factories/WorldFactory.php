<?php

namespace Database\Factories;

use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\World>
 */
class WorldFactory extends Factory
{
    protected $model = World::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $worldNames = [
            'Travian World 1',
            'Speed World',
            'Classic World',
            'Hero World',
            'Plus World',
            'Artifact World',
            'Wonder World',
            'Battle World',
        ];

        return [
            'name' => $this->faker->randomElement($worldNames),
            'description' => $this->faker->paragraph(),
            'is_active' => true,
            'max_players' => $this->faker->numberBetween(1000, 10000),
            'map_size' => $this->faker->numberBetween(200, 800),
            'speed' => $this->faker->numberBetween(1, 5),
            'has_plus' => $this->faker->boolean(30),
            'has_artifacts' => $this->faker->boolean(20),
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Create an active world
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'start_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+6 months'),
        ]);
    }

    /**
     * Create an inactive world
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'end_date' => $this->faker->dateTimeBetween('-1 year', '-1 month'),
        ]);
    }

    /**
     * Create a speed world
     */
    public function speedWorld(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Speed World '.$this->faker->numberBetween(1, 10),
            'speed' => $this->faker->numberBetween(3, 10),
            'description' => 'High-speed world with accelerated gameplay',
        ]);
    }

    /**
     * Create a classic world
     */
    public function classic(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Classic World '.$this->faker->numberBetween(1, 10),
            'speed' => 1,
            'has_plus' => false,
            'has_artifacts' => false,
            'description' => 'Classic Travian experience with standard settings',
        ]);
    }

    /**
     * Create a plus world
     */
    public function plus(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Plus World '.$this->faker->numberBetween(1, 10),
            'has_plus' => true,
            'has_artifacts' => $this->faker->boolean(50),
            'description' => 'Premium world with Plus features and artifacts',
        ]);
    }
}
