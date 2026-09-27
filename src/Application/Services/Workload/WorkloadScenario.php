<?php

namespace Src\Application\Services\Workload;

use App\Jobs\UpdateProfileJob;
use App\Models\Profile;
use Database\Seeders\ProfileSeeder;
use Illuminate\Bus\UniqueLock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Application\Enums\ProfileQueue;
use Src\Application\Enums\WorkloadPhase;
use Src\Infrastructure\Integrations\OnlyFans\WorkloadScript;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;

final readonly class WorkloadScenario
{
    public function __construct(private WorkloadScript $scripts) {}

    /**
     * @return Collection<int, Profile>
     */
    public function prepare(): Collection
    {
        ProfileSeeder::upsert([
            ...ProfileSeeder::definitions(),
            ...$this->extraLightProfiles(),
        ]);

        return Profile::query()
            ->whereIn('username', $this->usernames())
            ->orderBy('username')
            ->get();
    }

    /**
     * @param  Collection<int, Profile>  $profiles
     */
    public function installScripts(Collection $profiles): void
    {
        foreach ($profiles as $profile) {
            $profile->refresh();
            $this->scripts->put($profile->username, $this->scriptFor($profile));
        }
    }

    /**
     * @param  Collection<int, Profile>  $profiles
     */
    public function dispatch(Collection $profiles, WorkloadPhase $phase): void
    {
        foreach ($profiles as $profile) {
            $profile = $profile->fresh() ?? $profile;
            Cache::lock(UniqueLock::getKey(new UpdateProfileJob($profile)))->forceRelease();

            $pending = UpdateProfileJob::dispatch($profile);

            if ($phase->usesSingleQueue()) {
                $pending->onQueue(ProfileQueue::Profiles->value);
            }
        }
    }

    /**
     * @return list<string>
     */
    public function usernames(): array
    {
        return array_column([
            ...ProfileSeeder::definitions(),
            ...$this->extraLightProfiles(),
        ], 'username');
    }

    /**
     * @return list<OnlyFansProfileResponse>
     */
    private function scriptFor(Profile $profile): array
    {
        $success = OnlyFansProfileResponse::ok([
            'likes' => ($profile->likes ?? 0) + 1,
            'revision' => ($profile->revision ?? 0) + 1,
        ]);

        if ($profile->queue() === ProfileQueue::Heavy) {
            return [
                OnlyFansProfileResponse::failure(
                    OnlyFansResponseKind::RateLimited,
                    429,
                    message: 'Too Many Requests',
                ),
                $success,
            ];
        }

        return [$success];
    }

    /**
     * @return list<array{username: string, likes: int, revision: int}>
     */
    private function extraLightProfiles(): array
    {
        return [
            ['username' => 'lexnorth', 'likes' => 3_958, 'revision' => 2],
            ['username' => 'littlikimmy', 'likes' => 13_449, 'revision' => 3],
            ['username' => 'kristinemartinsen', 'likes' => 28_000, 'revision' => 5],
            ['username' => 'marysoph', 'likes' => 37_750, 'revision' => 6],
        ];
    }
}
