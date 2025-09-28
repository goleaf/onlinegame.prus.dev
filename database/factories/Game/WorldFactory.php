<?php

namespace Database\Factories\Game;

use App\Models\Game\World;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\World>
 */
class WorldFactory extends Factory
{
    protected $model = World::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true).' World',
            'description' => $this->faker->paragraph(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the world is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
