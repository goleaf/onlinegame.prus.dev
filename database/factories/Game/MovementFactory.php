<?php

namespace Database\Factories\Game;

use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Movement>
 */
class MovementFactory extends Factory
{
    protected $model = Movement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = $this->faker->dateTimeBetween('-1 day', 'now');
        $arrivesAt = $this->faker->dateTimeBetween($startedAt, '+1 day');
        $returnAt = $this->faker->dateTimeBetween($arrivesAt, '+2 days');

        return [
            'player_id' => Player::factory(),
            'from_village_id' => Village::factory(),
            'to_village_id' => Village::factory(),
            'type' => $this->faker->randomElement(['attack', 'support', 'spy', 'trade', 'return']),
            'troops' => [
                'legionnaire' => $this->faker->numberBetween(0, 100),
                'praetorian' => $this->faker->numberBetween(0, 50),
                'imperian' => $this->faker->numberBetween(0, 30),
            ],
            'resources' => [
                'wood' => $this->faker->numberBetween(0, 1000),
                'clay' => $this->faker->numberBetween(0, 1000),
                'iron' => $this->faker->numberBetween(0, 1000),
                'crop' => $this->faker->numberBetween(0, 1000),
            ],
            'started_at' => $startedAt,
            'arrives_at' => $arrivesAt,
            'returned_at' => $returnAt,
            'status' => $this->faker->randomElement(['travelling', 'arrived', 'returning', 'completed', 'cancelled']),
            'metadata' => [
                'distance' => $this->faker->numberBetween(1, 100),
                'speed' => $this->faker->numberBetween(1, 20),
                'carry_capacity' => $this->faker->numberBetween(0, 1000),
            ],
        ];
    }

    /**
     * Indicate that the movement is travelling.
     */
    public function travelling(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'travelling',
            'started_at' => now()->subMinutes(30),
            'arrives_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Indicate that the movement has arrived.
     */
    public function arrived(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'arrived',
            'started_at' => now()->subHour(),
            'arrives_at' => now()->subMinutes(30),
        ]);
    }

    /**
     * Indicate that the movement is returning.
     */
    public function returning(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'returning',
            'started_at' => now()->subHours(2),
            'arrives_at' => now()->subHour(),
            'return_at' => now()->addMinutes(30),
        ]);
    }

    /**
     * Indicate that the movement is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subHours(3),
            'arrives_at' => now()->subHours(2),
            'return_at' => now()->subHour(),
        ]);
    }

    /**
     * Create a movement for a specific player.
     */
    public function forPlayer(Player $player): static
    {
        return $this->state(fn(array $attributes) => [
            'player_id' => $player->id,
        ]);
    }

    /**
     * Create a movement between specific villages.
     */
    public function betweenVillages(Village $fromVillage, Village $toVillage): static
    {
        return $this->state(fn(array $attributes) => [
            'from_village_id' => $fromVillage->id,
            'to_village_id' => $toVillage->id,
        ]);
    }
}
