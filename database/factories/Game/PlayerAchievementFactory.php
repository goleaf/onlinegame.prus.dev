<?php

namespace Database\Factories\Game;

use App\Models\Game\AchievementTemplate;
use App\Models\Game\Player;
use App\Models\Game\PlayerAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerAchievementFactory extends Factory
{
    protected $model = PlayerAchievement::class;

    public function definition(): array
    {
        $unlockedAt = $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-1 day', 'now') : null;

        return [
            'player_id' => Player::factory(),
            'achievement_id' => AchievementTemplate::factory(),
            'unlocked_at' => $unlockedAt ?: now(),
            'progress_data' => [
                'current_progress' => $this->faker->numberBetween(0, 100),
                'total_required' => $this->faker->numberBetween(1, 100),
                'completed_objectives' => $this->faker->numberBetween(0, 5),
            ],
        ];
    }

    public function unlocked(): static
    {
        return $this->state(fn(array $attributes) => [
            'unlocked_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    public function available(): static
    {
        return $this->state(fn(array $attributes) => [
            'unlocked_at' => null,
        ]);
    }
}
