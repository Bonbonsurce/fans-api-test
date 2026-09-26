<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\ProfileUpdateAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Application\Enums\ProfileUpdateAttemptStatus;

/**
 * @extends Factory<ProfileUpdateAttempt>
 */
class ProfileUpdateAttemptFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'status' => ProfileUpdateAttemptStatus::Succeeded,
            'attempt_number' => 1,
            'http_status' => 200,
            'revision' => fake()->numberBetween(1, 20),
            'likes' => fake()->numberBetween(0, 200_000),
            'error_type' => null,
            'error_message' => null,
            'started_at' => now()->subSeconds(2),
            'finished_at' => now(),
        ];
    }
}
