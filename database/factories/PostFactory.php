<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'body' => fake()->paragraph(3),
            'status' => 'active',
        ];
    }

    public function flagged(): static
    {
        return $this->state(fn () => ['status' => 'flagged']);
    }

    public function hidden(): static
    {
        return $this->state(fn () => ['status' => 'hidden']);
    }
}
