<?php

namespace Src\Application\Services\Workload;

use App\Models\Profile;
use App\Models\ProfileUpdateAttempt;
use Illuminate\Support\Collection;
use Src\Application\DTOs\WorkloadPhaseResultDTO;
use Src\Application\Enums\ProfileQueue;
use Src\Application\Enums\WorkloadPhase;
use Src\Infrastructure\Queue\RedisQueueProbe;

final readonly class WorkloadRunner
{
    /** @var list<string> */
    private array $queueNames;

    public function __construct(
        private WorkloadScenario $scenario,
        private WorkloadMetrics $metrics,
        private RedisQueueProbe $queues,
    )
    {
        $this->queueNames = [
            ProfileQueue::Profiles->value,
            ProfileQueue::Heavy->value,
        ];
    }

    public function run(WorkloadPhase $phase, int $timeoutSeconds): WorkloadPhaseResultDTO
    {
        $profiles = $this->scenario->prepare();
        $this->scenario->installScripts($profiles);

        $attemptCursor = (int) ProfileUpdateAttempt::query()->max('id');
        $startedAt = now();

        $this->scenario->dispatch($profiles, $phase);

        [$drained, $oldestAge] = $this->waitUntilDrained($timeoutSeconds);

        return new WorkloadPhaseResultDTO(
            phase: $phase,
            metrics: $this->metrics->collect(
                profileIds: $profiles->modelKeys(),
                afterAttemptId: $attemptCursor,
                oldestWaitingJobAgeSeconds: $oldestAge,
                drained: $drained,
            ),
            startedAt: $startedAt,
            finishedAt: now(),
            queuesRemaining: $this->queueDepths(),
            profiles: $this->profileSnapshot($profiles),
        );
    }

    /**
     * @return array{0: bool, 1: int}
     */
    private function waitUntilDrained(int $timeoutSeconds): array
    {
        $oldestAge = 0;
        $deadline = now()->addSeconds($timeoutSeconds);

        while (now()->lte($deadline)) {
            $oldestAge = max($oldestAge, $this->queues->oldestAgeSeconds(...$this->queueNames));

            if ($this->queues->isDrained(...$this->queueNames)) {
                return [true, $oldestAge];
            }

            usleep(250_000);
        }

        return [false, $oldestAge];
    }

    /**
     * @return array<string, int>
     */
    private function queueDepths(): array
    {
        return [
            ProfileQueue::Profiles->value => $this->queues->depth(ProfileQueue::Profiles->value),
            ProfileQueue::Heavy->value => $this->queues->depth(ProfileQueue::Heavy->value),
        ];
    }

    /**
     * @param  Collection<int, Profile>  $profiles
     * @return list<array<string, mixed>>
     */
    private function profileSnapshot(Collection $profiles): array
    {
        return $profiles
            ->map(function (Profile $profile): array {
                $profile->refresh();

                return [
                    'username' => $profile->username,
                    'queue' => $profile->queue()->value,
                    'likes' => $profile->likes,
                    'revision' => $profile->revision,
                    'last_failure' => $profile->last_failure,
                    'last_successful_refresh_at' => $profile->last_successful_refresh_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }
}
