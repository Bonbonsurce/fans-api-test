<?php

namespace App\Jobs;

use App\Exceptions\TransientProfileUpdateException;
use App\Models\Profile;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Random\RandomException;
use Src\Application\DTOs\ProfileUpdateDataDTO;
use Src\Application\Services\ProfileUpdater;
use Src\Application\ValueObjects\ProfileUpdateResult;
use Throwable;

class UpdateProfileJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public Profile $profile)
    {
        $this->onQueue($profile->queue()->value);
    }

    public function uniqueId(): string
    {
        return (string) $this->profile->getKey();
    }

    public function uniqueFor(): int
    {
        return 72 * 60 * 60;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [15, 45, 90, 180];
    }

    /**
     * @throws RandomException
     * @throws Throwable
     */
    public function handle(ProfileUpdater $profileUpdater): void
    {
        $result = $profileUpdater->update(new ProfileUpdateDataDTO(
            profile: $this->profile,
            attemptNumber: $this->attempts(),
            jobUuid: $this->job?->uuid(),
        ));

        $this->retry($result);
    }

    /**
     * @throws RandomException
     */
    private function retry(ProfileUpdateResult $result): void
    {
        if (! $result->retryable || is_null($result->errorType)) {
            return;
        }

        if ($result->errorType->isRateLimited()) {
            $this->release(random_int(8, 25));

            return;
        }

        throw new TransientProfileUpdateException($result->errorType);
    }
}
