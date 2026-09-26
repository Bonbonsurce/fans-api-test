<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $this->upsert('madison420ivy', [
            'likes' => 120_000,
            'revision' => 10,
            'last_attempt_at' => now()->subHour(),
            'last_successful_refresh_at' => now()->subHour(),
            'last_failure' => null,
        ]);

        $this->upsert('amouranth', [
            'likes' => 1_821_184,
            'revision' => 42,
            'last_attempt_at' => now()->subHour(),
            'last_successful_refresh_at' => now()->subHour(),
            'last_failure' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsert(string $username, array $attributes): void
    {
        $profile = Profile::query()->firstOrNew(['username' => $username]);
        $profile->fill($attributes);
        $profile->uuid ??= (string) Str::uuid();
        $profile->save();
    }
}
