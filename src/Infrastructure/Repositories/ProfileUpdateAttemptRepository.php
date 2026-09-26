<?php

namespace Src\Infrastructure\Repositories;

use App\Models\Profile;
use App\Models\ProfileUpdateAttempt;
use Carbon\CarbonInterface;
use Src\Application\ValueObjects\ProfileUpdateOutcome;

readonly class ProfileUpdateAttemptRepository
{
    public function create(
        Profile $profile,
        int $attemptNumber,
        ProfileUpdateOutcome $outcome,
        CarbonInterface $startedAt,
        CarbonInterface $finishedAt,
    ): ProfileUpdateAttempt
    {
        return $profile->updateAttempts()->create([
            'status' => $outcome->status,
            'attempt_number' => $attemptNumber,
            'http_status' => $outcome->httpStatus,
            'revision' => $outcome->revision,
            'likes' => $outcome->likes,
            'error_type' => $outcome->errorType,
            'error_message' => $outcome->errorMessage,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ]);
    }
}
