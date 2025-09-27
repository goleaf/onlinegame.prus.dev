<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Troop>
 */
class TroopFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'village_id' => \App\Models\Game\Village::factory(),
            'unit_type_id' => \App\Models\Game\UnitType::factory(),
            'count' => $this->faker->numberBetween(0, 1000),
            'is_training' => false,
            'training_started_at' => null,
            'training_completed_at' => null,
        ];
    }
}
