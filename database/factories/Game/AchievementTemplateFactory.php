<?php

namespace Database\Factories\Game;

use App\Models\Game\AchievementTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementTemplateFactory extends Factory
{
    protected $model = AchievementTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'key' => $this->faker->unique()->slug(3),
            'description' => $this->faker->paragraph(2),
            'category' => $this->faker->randomElement(['building', 'combat', 'exploration', 'trade', 'alliance', 'special', 'milestone']),
            'points' => $this->faker->numberBetween(100, 5000),
            'requirements' => [
                'level' => $this->faker->numberBetween(1, 30),
                'buildings' => [
                    'warehouse' => $this->faker->numberBetween(1, 20),
                    'granary' => $this->faker->numberBetween(1, 20),
                ],
            ],
            'rewards' => [
                'points' => $this->faker->numberBetween(100, 5000),
                'resources' => [
                    'wood' => $this->faker->numberBetween(1000, 20000),
                    'clay' => $this->faker->numberBetween(1000, 20000),
                    'iron' => $this->faker->numberBetween(1000, 20000),
                    'crop' => $this->faker->numberBetween(1000, 20000),
                ],
            ],
            'icon' => $this->faker->randomElement(['achievement-1.png', 'achievement-2.png', 'achievement-3.png']),
            'is_hidden' => $this->faker->boolean(10),
            'is_active' => true,
        ];
    }

    public function building(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'building',
            'title' => 'Master Builder',
            'description' => 'Build and upgrade various structures to become a master builder.',
        ]);
    }

    public function troop(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'troop',
            'title' => 'Army Commander',
            'description' => 'Train and command a powerful army.',
        ]);
    }

    public function battle(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'battle',
            'title' => 'Warrior',
            'description' => 'Prove your worth in battle.',
        ]);
    }

    public function resource(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'resource',
            'title' => 'Resource Master',
            'description' => 'Master the art of resource management.',
        ]);
    }

    public function exploration(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'exploration',
            'title' => 'Explorer',
            'description' => 'Discover new lands and territories.',
        ]);
    }

    public function social(): static
    {
        return $this->state(fn(array $attributes) => [
            'type' => 'social',
            'title' => 'Diplomat',
            'description' => 'Build relationships and alliances.',
        ]);
    }
}
