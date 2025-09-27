<?php

namespace Database\Factories;

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
        $tribes = ['roman', 'teuton', 'gaul', 'natars'];

        return [
            'user_id' => User::factory(),
            'world_id' => World::factory(),
            'name' => $this->faker->unique()->userName(),
            'tribe' => $this->faker->randomElement($tribes),
            'points' => $this->faker->numberBetween(0, 10000),
            'is_online' => $this->faker->boolean(30),  // 30% chance of being online
            'last_active_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'population' => $this->faker->numberBetween(0, 5000),
            'villages_count' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'last_login' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Create a Roman player
     */
    public function roman(): static
    {
        return $this->state(fn(array $attributes) => [
            'tribe' => 'roman',
        ]);
    }

    /**
     * Create a Teuton player
     */
    public function teuton(): static
    {
        return $this->state(fn(array $attributes) => [
            'tribe' => 'teuton',
        ]);
    }

    /**
     * Create a Gaul player
     */
    public function gaul(): static
    {
        return $this->state(fn(array $attributes) => [
            'tribe' => 'gaul',
        ]);
    }

    /**
     * Create an online player
     */
    public function online(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_online' => true,
            'last_active_at' => now(),
        ]);
    }

    /**
     * Create an offline player
     */
    public function offline(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_online' => false,
            'last_active_at' => $this->faker->dateTimeBetween('-1 week', '-1 hour'),
        ]);
    }

    /**
     * Create a high-level player
     */
    public function highLevel(): static
    {
        return $this->state(fn(array $attributes) => [
            'points' => $this->faker->numberBetween(5000, 50000),
            'population' => $this->faker->numberBetween(2000, 20000),
            'villages_count' => $this->faker->numberBetween(5, 20),
        ]);
    }

    /**
     * Create a new player
     */
    public function newPlayer(): static
    {
        return $this->state(fn(array $attributes) => [
            'points' => 0,
            'population' => 0,
            'villages_count' => 0,
            'last_active_at' => now(),
        ]);
    }
}
