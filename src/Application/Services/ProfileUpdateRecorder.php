<?php

namespace Src\Application\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\DB;
use Src\Application\DTOs\ProfileUpdateDataDTO;
use Src\Application\DTOs\ProfileUpdateRecordDTO;
use Src\Application\DTOs\ProfileUpdateStatsDTO;
use Src\Application\ValueObjects\ProfileUpdateOutcome;
use Src\Application\ValueObjects\ProfileUpdateResult;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\ParsedProfileStats;
use Src\Infrastructure\Repositories\ProfileRepository;
use Src\Infrastructure\Repositories\ProfileUpdateAttemptRepository;
use Throwable;

readonly class ProfileUpdateRecorder
{
    public function __construct(
        private ProfileRepository $profiles,
        private ProfileUpdateAttemptRepository $attempts,
        private ProfileUpdateLogger $logger,
    ) {}

    public function record(ProfileUpdateRecordDTO $record): ProfileUpdateResult
    {
        $this->persist($record);

        return $record->outcome->result();
    }

    /**
     * @throws Throwable
     */
    public function recordStats(ProfileUpdateStatsDTO $update): ProfileUpdateResult
    {
        return DB::transaction(function () use ($update): ProfileUpdateResult {
            $profile = $this->profiles->lockForUpdate($update->data->profile);
            $outcome = $this->outcomeFor($profile, $update->stats, $update->httpStatus);

            if ($outcome->status->isSucceeded()) {
                $this->profiles->save($this->accept($profile, $update->stats));
            }

            $result = $this->record(new ProfileUpdateRecordDTO(
                new ProfileUpdateDataDTO($profile, $update->data->attemptNumber, $update->data->jobUuid),
                $outcome,
                $update->startedAt,
            ));

            $update->data->profile->setRawAttributes($profile->getAttributes());
            $update->data->profile->exists = true;

            return $result;
        });
    }

    private function outcomeFor(Profile $profile, ParsedProfileStats $stats, int $httpStatus): ProfileUpdateOutcome
    {
        return ! is_null($profile->revision) && $stats->revision < $profile->revision
            ? ProfileUpdateOutcome::rejected($httpStatus, $stats->likes, $stats->revision)
            : ProfileUpdateOutcome::succeeded($httpStatus, $stats->likes, $stats->revision);
    }

    private function accept(Profile $profile, ParsedProfileStats $stats): Profile
    {
        $profile->fill([
            'likes' => $stats->likes,
            'revision' => $stats->revision,
            'last_successful_refresh_at' => now(),
        ]);

        return $profile;
    }

    private function persist(ProfileUpdateRecordDTO $record): void
    {
        $profile = $record->data->profile;
        $outcome = $record->outcome;
        $finishedAt = now();

        $profile->last_attempt_at = $record->startedAt;
        $profile->last_failure = is_null($outcome->errorType) ? null : [
            'kind' => $outcome->errorType->value,
            'status' => $outcome->httpStatus,
            'message' => $outcome->errorMessage ?? $outcome->errorType->value,
        ];

        $this->profiles->save($profile);
        $this->attempts->create($profile, $record->data->attemptNumber, $outcome, $record->startedAt, $finishedAt);
        $this->logger->finished($record->data, $outcome);
    }
}
