<?php

namespace Database\Factories\Game;

use App\Models\Game\Player;
use App\Models\Game\Task;
use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $startedAt = $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-1 day', 'now') : null;
        $completedAt = $startedAt && $this->faker->boolean(50) ? $this->faker->dateTimeBetween($startedAt, 'now') : null;

        $status = $completedAt ? 'completed' : ($startedAt ? 'active' : 'available');

        return [
            'world_id' => World::factory(),
            'player_id' => Player::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['building', 'troop', 'resource', 'battle', 'trade', 'exploration']),
            'status' => $status,
            'progress' => $this->faker->numberBetween(0, 100),
            'target' => $this->faker->numberBetween(1, 100),
            'rewards' => [
                'points' => $this->faker->numberBetween(10, 1000),
                'resources' => [
                    'wood' => $this->faker->numberBetween(100, 5000),
                    'clay' => $this->faker->numberBetween(100, 5000),
                    'iron' => $this->faker->numberBetween(100, 5000),
                    'crop' => $this->faker->numberBetween(100, 5000),
                ],
            ],
            'deadline' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('now', '+7 days') : null,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'active',
            'started_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'started_at' => $this->faker->dateTimeBetween('-2 days', '-1 day'),
            'completed_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function available(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'available',
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'deadline' => $this->faker->dateTimeBetween('-1 day', '-1 hour'),
        ]);
    }

    public function building(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'building',
            'title' => 'Build ' . $this->faker->randomElement(['Warehouse', 'Granary', 'Barracks', 'Stable']) . ' Level ' . $this->faker->numberBetween(1, 20),
            'description' => 'Construct and upgrade a building to the specified level.',
        ]);
    }

    public function troop(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'troop',
            'title' => 'Train ' . $this->faker->randomElement(['Legionnaires', 'Praetorians', 'Imperians']) . ' x' . $this->faker->numberBetween(10, 100),
            'description' => 'Train the specified number of troops.',
        ]);
    }

    public function resource(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'resource',
            'title' => 'Collect ' . $this->faker->randomElement(['Wood', 'Clay', 'Iron', 'Crop']) . ' x' . $this->faker->numberBetween(1000, 10000),
            'description' => 'Gather the specified amount of resources.',
        ]);
    }

    public function battle(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'battle',
            'title' => 'Win ' . $this->faker->numberBetween(1, 5) . ' Battles',
            'description' => 'Successfully win the specified number of battles.',
        ]);
    }
}
