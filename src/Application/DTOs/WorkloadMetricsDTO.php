<?php

namespace Src\Application\DTOs;

final readonly class WorkloadMetricsDTO
{
    public function __construct(
        public int $successfulRefreshes,
        public int $attempts,
        public ?float $attemptsPerSuccessfulRefresh,
        public int $oldestWaitingJobAgeSeconds,
        public bool $drained,
    ) {}

    /**
     * @return array{
     *     successful_refreshes: int,
     *     attempts: int,
     *     attempts_per_successful_refresh: float|null,
     *     oldest_waiting_job_age_seconds: int,
     *     drained: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'successful_refreshes' => $this->successfulRefreshes,
            'attempts' => $this->attempts,
            'attempts_per_successful_refresh' => $this->attemptsPerSuccessfulRefresh,
            'oldest_waiting_job_age_seconds' => $this->oldestWaitingJobAgeSeconds,
            'drained' => $this->drained,
        ];
    }
}
