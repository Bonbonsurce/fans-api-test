<?php

namespace Database\Factories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'username' => fake()->unique()->userName(),
            'likes' => fake()->numberBetween(0, 200_000),
            'revision' => fake()->numberBetween(1, 20),
            'last_attempt_at' => now(),
            'last_successful_refresh_at' => now(),
            'last_failure' => null,
        ];
    }
}
