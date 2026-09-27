<?php

namespace Src\Application\Services\Workload;

use App\Models\ProfileUpdateAttempt;
use Src\Application\DTOs\WorkloadMetricsDTO;

final class WorkloadMetrics
{
    /**
     * @param  list<int>  $profileIds
     */
    public function collect(
        array $profileIds,
        int $afterAttemptId,
        int $oldestWaitingJobAgeSeconds,
        bool $drained,
    ): WorkloadMetricsDTO
    {
        $attempts = ProfileUpdateAttempt::query()
            ->whereIn('profile_id', $profileIds)
            ->where('id', '>', $afterAttemptId)
            ->get();

        $successes = $attempts
            ->filter(fn (ProfileUpdateAttempt $attempt): bool => $attempt->status->isSucceeded())
            ->count();
        $total = $attempts->count();

        return new WorkloadMetricsDTO(
            successfulRefreshes: $successes,
            attempts: $total,
            attemptsPerSuccessfulRefresh: $successes === 0 ? null : round($total / $successes, 2),
            oldestWaitingJobAgeSeconds: $oldestWaitingJobAgeSeconds,
            drained: $drained,
        );
    }
}
