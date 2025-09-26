<?php

namespace Database\Factories\Game;

use App\Models\Game\UnitType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\UnitType>
 */
class UnitTypeFactory extends Factory
{
    protected $model = UnitType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'key' => $this->faker->slug(2),
            'tribe' => $this->faker->randomElement(['roman', 'teuton', 'gaul', 'natars']),
            'description' => $this->faker->sentence(),
            'attack' => $this->faker->numberBetween(0, 200),
            'defense_infantry' => $this->faker->numberBetween(0, 200),
            'defense_cavalry' => $this->faker->numberBetween(0, 200),
            'speed' => $this->faker->numberBetween(1, 20),
            'carry_capacity' => $this->faker->numberBetween(0, 100),
            'costs' => [
                'wood' => $this->faker->numberBetween(50, 1000),
                'clay' => $this->faker->numberBetween(50, 1000),
                'iron' => $this->faker->numberBetween(50, 1000),
                'crop' => $this->faker->numberBetween(50, 1000),
            ],
            'requirements' => [],
            'is_special' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the unit type is special.
     */
    public function special(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_special' => true,
            'attack' => $this->faker->numberBetween(100, 300),
            'defense_infantry' => $this->faker->numberBetween(100, 300),
            'defense_cavalry' => $this->faker->numberBetween(100, 300),
        ]);
    }

    /**
     * Indicate that the unit type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a unit type for a specific tribe.
     */
    public function tribe(string $tribe): static
    {
        return $this->state(fn (array $attributes) => [
            'tribe' => $tribe,
        ]);
    }
}
