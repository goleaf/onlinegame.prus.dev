<?php

namespace Database\Factories\Game;

use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'world_id' => World::factory(),
            'name' => $this->faker->userName(),
            'tribe' => $this->faker->randomElement(['roman', 'teuton', 'gaul', 'natars']),
            'points' => $this->faker->numberBetween(0, 100000),
            'is_online' => $this->faker->boolean(20), // 20% chance of being online
            'last_active_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'population' => $this->faker->numberBetween(100, 10000),
            'villages_count' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'last_login' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Indicate that the player is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the player is online.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'last_login' => now(),
        ]);
    }

    /**
     * Create a player with specific tribe.
     */
    public function tribe(string $tribe): static
    {
        return $this->state(fn (array $attributes) => [
            'tribe' => $tribe,
        ]);
    }
}