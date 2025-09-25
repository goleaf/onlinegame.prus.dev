<?php

namespace Database\Factories\Game;

use App\Models\Game\BuildingQueue;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\BuildingQueue>
 */
class BuildingQueueFactory extends Factory
{
    protected $model = BuildingQueue::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'village_id' => Village::factory(),
            'building_type_id' => BuildingType::factory(),
            'target_level' => $this->faker->numberBetween(1, 20),
            'started_at' => now(),
            'completed_at' => now()->addHours($this->faker->numberBetween(1, 24)),
            'costs' => [
                'wood' => $this->faker->numberBetween(100, 1000),
                'clay' => $this->faker->numberBetween(100, 1000),
                'iron' => $this->faker->numberBetween(100, 1000),
                'crop' => $this->faker->numberBetween(100, 1000),
            ],
            'status' => 'in_progress',
            'is_completed' => false,
        ];
    }

    /**
     * Indicate that the building queue is completed.
     */
    public function completed(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_completed' => true,
            'status' => 'completed',
            'completed_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the building queue is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_completed' => false,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => now()->addHours($this->faker->numberBetween(1, 24)),
        ]);
    }
}

