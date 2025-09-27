<?php

namespace Database\Factories\Game;

use App\Models\Game\BuildingType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\BuildingType>
 */
class BuildingTypeFactory extends Factory
{
    protected $model = BuildingType::class;

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
            'description' => $this->faker->sentence(),
            'max_level' => $this->faker->numberBetween(5, 20),
            'requirements' => json_encode([]),
            'costs' => json_encode([
                'wood' => $this->faker->numberBetween(50, 500),
                'clay' => $this->faker->numberBetween(50, 500),
                'iron' => $this->faker->numberBetween(50, 500),
                'crop' => $this->faker->numberBetween(50, 500),
            ]),
            'production' => json_encode([]),
            'population' => json_encode([]),
            'is_special' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the building type is special.
     */
    public function special(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_special' => true,
            'max_level' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Indicate that the building type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
