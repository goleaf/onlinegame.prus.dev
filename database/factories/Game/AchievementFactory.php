<?php

namespace Database\Factories\Game;

use App\Models\Game\Achievement;
use App\Models\Game\Player;
use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    public function definition(): array
    {
        $unlockedAt = $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-1 day', 'now') : null;
        $status = $unlockedAt ? 'unlocked' : 'available';

        return [
            'world_id' => World::factory(),
            'player_id' => Player::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(2),
            'type' => $this->faker->randomElement(['building', 'troop', 'battle', 'resource', 'exploration', 'social']),
            'status' => $status,
            'progress' => $this->faker->numberBetween(0, 100),
            'target' => $this->faker->numberBetween(1, 100),
            'rewards' => [
                'points' => $this->faker->numberBetween(100, 5000),
                'resources' => [
                    'wood' => $this->faker->numberBetween(1000, 20000),
                    'clay' => $this->faker->numberBetween(1000, 20000),
                    'iron' => $this->faker->numberBetween(1000, 20000),
                    'crop' => $this->faker->numberBetween(1000, 20000),
                ]
            ],
            'requirements' => [
                'level' => $this->faker->numberBetween(1, 30),
                'buildings' => [
                    'warehouse' => $this->faker->numberBetween(1, 20),
                    'granary' => $this->faker->numberBetween(1, 20),
                ]
            ],
            'unlocked_at' => $unlockedAt,
        ];
    }

    public function unlocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unlocked',
            'unlocked_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
            'unlocked_at' => null,
        ]);
    }

    public function building(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'building',
            'title' => 'Master Builder',
            'description' => 'Build and upgrade various structures to become a master builder.',
        ]);
    }

    public function troop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'troop',
            'title' => 'Army Commander',
            'description' => 'Train and command a powerful army.',
        ]);
    }

    public function battle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'battle',
            'title' => 'Warrior',
            'description' => 'Prove your worth in battle.',
        ]);
    }

    public function resource(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'resource',
            'title' => 'Resource Master',
            'description' => 'Master the art of resource management.',
        ]);
    }

    public function exploration(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'exploration',
            'title' => 'Explorer',
            'description' => 'Discover new lands and territories.',
        ]);
    }

    public function social(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'social',
            'title' => 'Diplomat',
            'description' => 'Build relationships and alliances.',
        ]);
    }
}
