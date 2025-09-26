<?php

namespace Database\Factories\Game;

use App\Models\Game\Resource;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Resource>
 */
class ResourceFactory extends Factory
{
    protected $model = Resource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['wood', 'clay', 'iron', 'crop']);

        return [
            'village_id' => Village::factory(),
            'type' => $type,
            'amount' => $this->faker->numberBetween(1000, 50000),
            'production_rate' => $this->faker->numberBetween(10, 100),
            'storage_capacity' => $this->faker->numberBetween(10000, 100000),
            'level' => $this->faker->numberBetween(1, 20),
            'last_updated' => now(),
        ];
    }

    /**
     * Create a wood resource.
     */
    public function wood(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'wood',
            'production_rate' => $this->faker->numberBetween(20, 50),
        ]);
    }

    /**
     * Create a clay resource.
     */
    public function clay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'clay',
            'production_rate' => $this->faker->numberBetween(20, 50),
        ]);
    }

    /**
     * Create an iron resource.
     */
    public function iron(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'iron',
            'production_rate' => $this->faker->numberBetween(20, 50),
        ]);
    }

    /**
     * Create a crop resource.
     */
    public function crop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'crop',
            'production_rate' => $this->faker->numberBetween(10, 30),
        ]);
    }

    /**
     * Create a resource with high production.
     */
    public function highProduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'production_rate' => $this->faker->numberBetween(100, 500),
            'level' => $this->faker->numberBetween(15, 20),
        ]);
    }
}
