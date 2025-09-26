<?php

namespace Database\Factories;

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
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];
        $type = $this->faker->randomElement($resourceTypes);

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
     * Create a wood resource
     */
    public function wood(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'wood',
            'amount' => $this->faker->numberBetween(5000, 50000),
            'production_rate' => $this->faker->numberBetween(20, 100),
        ]);
    }

    /**
     * Create a clay resource
     */
    public function clay(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'clay',
            'amount' => $this->faker->numberBetween(5000, 50000),
            'production_rate' => $this->faker->numberBetween(20, 100),
        ]);
    }

    /**
     * Create an iron resource
     */
    public function iron(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'iron',
            'amount' => $this->faker->numberBetween(5000, 50000),
            'production_rate' => $this->faker->numberBetween(20, 100),
        ]);
    }

    /**
     * Create a crop resource
     */
    public function crop(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'crop',
            'amount' => $this->faker->numberBetween(5000, 50000),
            'production_rate' => $this->faker->numberBetween(20, 100),
        ]);
    }

    /**
     * Create a low-level resource
     */
    public function lowLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->numberBetween(1000, 10000),
            'production_rate' => $this->faker->numberBetween(10, 30),
            'storage_capacity' => $this->faker->numberBetween(10000, 50000),
            'level' => $this->faker->numberBetween(1, 5),
        ]);
    }

    /**
     * Create a high-level resource
     */
    public function highLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $this->faker->numberBetween(50000, 500000),
            'production_rate' => $this->faker->numberBetween(50, 200),
            'storage_capacity' => $this->faker->numberBetween(500000, 2000000),
            'level' => $this->faker->numberBetween(10, 20),
        ]);
    }

    /**
     * Create a full storage resource
     */
    public function fullStorage(): static
    {
        return $this->state(function (array $attributes) {
            $capacity = $this->faker->numberBetween(10000, 100000);

            return [
                'amount' => $capacity,
                'storage_capacity' => $capacity,
            ];
        });
    }

    /**
     * Create an empty resource
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => 0,
        ]);
    }
}
