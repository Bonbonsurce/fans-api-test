<?php

namespace Src\Application\Services;

use Src\Application\DTOs\ProfileUpdateDataDTO;
use Src\Application\ValueObjects\ProfileUpdateOutcome;

readonly class ProfileUpdateLogger
{
    public function finished(ProfileUpdateDataDTO $data, ProfileUpdateOutcome $outcome): void
    {
        $level = $outcome->status->isSucceeded() ? 'info' : 'warning';

        logger()->log($level, 'profile.update.finished', $this->context($data, $outcome));
    }

    /**
     * @return array<string, mixed>
     */
    private function context(ProfileUpdateDataDTO $data, ProfileUpdateOutcome $outcome): array
    {
        $profile = $data->profile;

        return [
            'job_uuid' => $data->jobUuid,
            'username' => $profile->username,
            'profile_uuid' => $profile->uuid,
            'profile_id' => $profile->id,
            'attempt_number' => $data->attemptNumber,
            'status' => $outcome->status->value,
            'http_status' => $outcome->httpStatus,
            'revision' => $outcome->revision,
            'likes' => $outcome->likes,
            'error_type' => $outcome->errorType?->value,
        ];
    }
}
