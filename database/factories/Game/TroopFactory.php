<?php

namespace Database\Factories\Game;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game\Troop>
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
            'in_village' => $this->faker->numberBetween(0, 1000),
            'in_attack' => 0,
            'in_defense' => 0,
            'in_support' => 0,
        ];
    }
}
