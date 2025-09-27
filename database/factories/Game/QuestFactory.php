<?php

namespace Database\Factories\Game;

use App\Models\Game\Player;
use App\Models\Game\PlayerQuest;
use App\Models\Game\QuestTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestFactory extends Factory
{
    protected $model = PlayerQuest::class;

    public function definition(): array
    {
        $startedAt = $this->faker->boolean(30) ? $this->faker->dateTimeBetween('-1 day', 'now') : null;
        $completedAt = $startedAt && $this->faker->boolean(50) ? $this->faker->dateTimeBetween($startedAt, 'now') : null;

        $status = $completedAt ? 'completed' : ($startedAt ? 'active' : 'available');

        return [
            'player_id' => Player::factory(),
            'quest_id' => QuestTemplate::factory(),
            'status' => $status,
            'progress' => $this->faker->numberBetween(0, 100),
            'progress_data' => [
                'completed_objectives' => $this->faker->numberBetween(0, 5),
                'total_objectives' => $this->faker->numberBetween(1, 10),
            ],
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'expires_at' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('now', '+7 days') : null,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'in_progress',
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

    public function failed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'failed',
            'started_at' => $this->faker->dateTimeBetween('-2 days', '-1 day'),
            'completed_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'expired',
            'started_at' => $this->faker->dateTimeBetween('-2 days', '-1 day'),
            'completed_at' => null,
            'expires_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }
}
