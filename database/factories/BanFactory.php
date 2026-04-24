<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Ban>
 */
class BanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'banned_by' => User::factory()->admin(),
            'reason' => fake()->sentence(),
            'expires_at' => null,
        ];
    }

    public function temporary(): static
    {
        return $this->state(fn () => ['expires_at' => now()->addDays(7)]);
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subDay()]);
    }
}
