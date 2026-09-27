<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProfileSeeder extends Seeder
{
    /**
     * @return list<array{username: string, likes: int, revision: int}>
     */
    public static function definitions(): array
    {
        return [
            ['username' => 'madison420ivy', 'likes' => 120_000, 'revision' => 10],
            ['username' => 'amouranth', 'likes' => 1_821_184, 'revision' => 42],
            ['username' => 'ladydisdainvip', 'likes' => 2_998, 'revision' => 4],
        ];
    }

    public function run(): void
    {
        self::upsert(self::definitions());
    }

    /**
     * @param  list<array{username: string, likes: int, revision: int}>  $definitions
     */
    public static function upsert(array $definitions): void
    {
        $now = now();
        $usernames = array_column($definitions, 'username');
        $uuids = Profile::query()->whereIn('username', $usernames)->pluck('uuid', 'username');

        $rows = array_map(function (array $definition) use ($uuids, $now): array {
            return [
                'uuid' => $uuids[$definition['username']] ?? (string) Str::uuid(),
                'username' => $definition['username'],
                'likes' => $definition['likes'],
                'revision' => $definition['revision'],
                'last_attempt_at' => $now->copy()->subHour(),
                'last_successful_refresh_at' => $now->copy()->subHour(),
                'last_failure' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $definitions);

        Profile::query()->upsert($rows, ['username'], [
            'likes',
            'revision',
            'last_attempt_at',
            'last_successful_refresh_at',
            'last_failure',
            'updated_at',
        ]);
    }
}
