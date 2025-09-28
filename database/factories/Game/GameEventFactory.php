<?php

namespace Database\Factories\Game;

use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameEventFactory extends Factory
{
    protected $model = GameEvent::class;

    public function definition(): array
    {
        return [
            'player_id' => Player::factory(),
            'village_id' => Village::factory(),
            'event_type' => $this->faker->randomElement([
                'building_completed',
                'troop_training_completed',
                'attack_received',
                'attack_sent',
                'resource_updated',
                'village_founded',
                'alliance_joined',
                'quest_completed',
            ]),
            'event_data' => [
                'message' => $this->faker->sentence(),
                'details' => $this->faker->paragraph(),
            ],
            'occurred_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'is_read' => $this->faker->boolean(30),  // 30% chance of being read
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => false,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    public function buildingCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'building_completed',
            'event_data' => [
                'building_name' => $this->faker->randomElement(['Woodcutter', 'Clay Pit', 'Iron Mine', 'Crop Field']),
                'level' => $this->faker->numberBetween(1, 20),
                'village_name' => $this->faker->city(),
            ],
        ]);
    }

    public function attackReceived(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'attack_received',
            'event_data' => [
                'attacker_name' => $this->faker->name(),
                'attacker_tribe' => $this->faker->randomElement(['Romans', 'Teutons', 'Gauls']),
                'troops' => $this->faker->numberBetween(1, 100),
                'arrival_time' => $this->faker->dateTimeBetween('now', '+1 day'),
            ],
        ]);
    }
}
