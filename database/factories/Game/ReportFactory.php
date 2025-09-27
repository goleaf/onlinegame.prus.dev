<?php

namespace Database\Factories\Game;

use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['attack', 'defense', 'support', 'spy', 'trade', 'system']);
        $status = $this->faker->randomElement(['victory', 'defeat', 'draw', 'pending']);

        return [
            'world_id' => World::factory(),
            'attacker_id' => Player::factory(),
            'defender_id' => Player::factory(),
            'from_village_id' => Village::factory(),
            'to_village_id' => Village::factory(),
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraphs(3, true),
            'type' => $type,
            'status' => $status,
            'battle_data' => [
                'attacker_losses' => $this->faker->numberBetween(0, 1000),
                'defender_losses' => $this->faker->numberBetween(0, 1000),
                'resources_looted' => [
                    'wood' => $this->faker->numberBetween(0, 10000),
                    'clay' => $this->faker->numberBetween(0, 10000),
                    'iron' => $this->faker->numberBetween(0, 10000),
                    'crop' => $this->faker->numberBetween(0, 10000),
                ],
                'experience_gained' => $this->faker->numberBetween(0, 1000),
                'troops_sent' => [
                    'legionnaire' => $this->faker->numberBetween(0, 100),
                    'praetorian' => $this->faker->numberBetween(0, 50),
                    'imperian' => $this->faker->numberBetween(0, 30),
                ],
                'troops_lost' => [
                    'legionnaire' => $this->faker->numberBetween(0, 50),
                    'praetorian' => $this->faker->numberBetween(0, 25),
                    'imperian' => $this->faker->numberBetween(0, 15),
                ],
            ],
            'attachments' => $this->faker->optional(0.3)->randomElements([
                ['name' => 'battle_screenshot.png', 'type' => 'image', 'size' => 1024000],
                ['name' => 'troop_movements.csv', 'type' => 'data', 'size' => 2048],
                ['name' => 'resource_analysis.pdf', 'type' => 'document', 'size' => 512000],
            ], $this->faker->numberBetween(0, 2)),
            'is_read' => $this->faker->boolean(30),
            'is_important' => $this->faker->boolean(20),
            'read_at' => $this->faker->optional(0.3)->dateTimeBetween('-1 week', 'now'),
        ];
    }

    /**
     * Indicate that the report is unread.
     */
    public function unread(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the report is read.
     */
    public function read(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_read' => true,
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the report is important.
     */
    public function important(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_important' => true,
        ]);
    }

    /**
     * Indicate that the report is unimportant.
     */
    public function unimportant(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_important' => false,
        ]);
    }

    /**
     * Create a report for a specific type.
     */
    public function type(string $type): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => $type,
        ]);
    }

    /**
     * Create a report with a specific status.
     */
    public function status(string $status): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => $status,
        ]);
    }

    /**
     * Create a report for a specific world.
     */
    public function forWorld(World $world): static
    {
        return $this->state(fn(array $attributes) => [
            'world_id' => $world->id,
        ]);
    }

    /**
     * Create a report between specific players.
     */
    public function betweenPlayers(Player $attacker, Player $defender): static
    {
        return $this->state(fn(array $attributes) => [
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
        ]);
    }

    /**
     * Create a report between specific villages.
     */
    public function betweenVillages(Village $fromVillage, Village $toVillage): static
    {
        return $this->state(fn(array $attributes) => [
            'from_village_id' => $fromVillage->id,
            'to_village_id' => $toVillage->id,
        ]);
    }

    /**
     * Create a victory report.
     */
    public function victory(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'victory',
            'battle_data' => [
                'attacker_losses' => $this->faker->numberBetween(0, 100),
                'defender_losses' => $this->faker->numberBetween(100, 1000),
                'resources_looted' => [
                    'wood' => $this->faker->numberBetween(1000, 10000),
                    'clay' => $this->faker->numberBetween(1000, 10000),
                    'iron' => $this->faker->numberBetween(1000, 10000),
                    'crop' => $this->faker->numberBetween(1000, 10000),
                ],
                'experience_gained' => $this->faker->numberBetween(100, 1000),
            ],
        ]);
    }

    /**
     * Create a defeat report.
     */
    public function defeat(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'defeat',
            'battle_data' => [
                'attacker_losses' => $this->faker->numberBetween(500, 2000),
                'defender_losses' => $this->faker->numberBetween(0, 200),
                'resources_looted' => [
                    'wood' => 0,
                    'clay' => 0,
                    'iron' => 0,
                    'crop' => 0,
                ],
                'experience_gained' => $this->faker->numberBetween(10, 100),
            ],
        ]);
    }

    /**
     * Create a draw report.
     */
    public function draw(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'draw',
            'battle_data' => [
                'attacker_losses' => $this->faker->numberBetween(200, 800),
                'defender_losses' => $this->faker->numberBetween(200, 800),
                'resources_looted' => [
                    'wood' => $this->faker->numberBetween(0, 1000),
                    'clay' => $this->faker->numberBetween(0, 1000),
                    'iron' => $this->faker->numberBetween(0, 1000),
                    'crop' => $this->faker->numberBetween(0, 1000),
                ],
                'experience_gained' => $this->faker->numberBetween(50, 300),
            ],
        ]);
    }
}
