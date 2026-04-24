<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reason' => fake()->randomElement(['Spam', 'Harassment', 'Misinformation', 'Hate speech', 'Off-topic']),
            'status' => 'pending',
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => 'resolved',
            'resolution_note' => fake()->sentence(),
            'reviewed_by' => User::factory()->moderator(),
            'reviewed_at' => now(),
        ]);
    }
}
