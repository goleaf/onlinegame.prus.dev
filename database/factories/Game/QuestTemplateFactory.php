<?php

namespace Database\Factories\Game;

use App\Models\Game\QuestTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestTemplateFactory extends Factory
{
    protected $model = QuestTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'key' => $this->faker->unique()->slug(3),
            'description' => $this->faker->paragraph(3),
            'instructions' => $this->faker->paragraph(2),
            'category' => $this->faker->randomElement(['tutorial', 'building', 'combat', 'exploration', 'trade', 'alliance', 'special']),
            'difficulty' => $this->faker->numberBetween(1, 5),
            'requirements' => [
                'level' => $this->faker->numberBetween(1, 20),
                'buildings' => [
                    'warehouse' => $this->faker->numberBetween(1, 10),
                    'granary' => $this->faker->numberBetween(1, 10),
                ],
            ],
            'rewards' => [
                'points' => $this->faker->numberBetween(50, 2000),
                'resources' => [
                    'wood' => $this->faker->numberBetween(500, 10000),
                    'clay' => $this->faker->numberBetween(500, 10000),
                    'iron' => $this->faker->numberBetween(500, 10000),
                    'crop' => $this->faker->numberBetween(500, 10000),
                ],
            ],
            'experience_reward' => $this->faker->numberBetween(100, 1000),
            'gold_reward' => $this->faker->numberBetween(50, 500),
            'resource_rewards' => [
                'wood' => $this->faker->numberBetween(100, 1000),
                'clay' => $this->faker->numberBetween(100, 1000),
                'iron' => $this->faker->numberBetween(100, 1000),
                'crop' => $this->faker->numberBetween(100, 1000),
            ],
            'is_repeatable' => $this->faker->boolean(20),
            'is_active' => true,
        ];
    }

    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'main',
            'title' => 'Main Quest: ' . $this->faker->sentence(3),
            'description' => 'A crucial quest that advances the main storyline.',
        ]);
    }

    public function side(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'side',
            'title' => 'Side Quest: ' . $this->faker->sentence(3),
            'description' => 'An optional quest that provides additional rewards.',
        ]);
    }

    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'daily',
            'title' => 'Daily Quest: ' . $this->faker->sentence(3),
            'description' => 'A quest that can be completed once per day.',
        ]);
    }

    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'weekly',
            'title' => 'Weekly Quest: ' . $this->faker->sentence(3),
            'description' => 'A quest that can be completed once per week.',
        ]);
    }

    public function special(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'special',
            'title' => 'Special Quest: ' . $this->faker->sentence(3),
            'description' => 'A limited-time quest with special rewards.',
        ]);
    }
}
