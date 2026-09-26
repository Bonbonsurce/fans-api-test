<?php

namespace Src\Application\Services;

use App\Exceptions\InvalidPayloadException;
use Carbon\CarbonInterface;
use Src\Application\DTOs\ProfileUpdateDataDTO;
use Src\Application\DTOs\ProfileUpdateRecordDTO;
use Src\Application\DTOs\ProfileUpdateStatsDTO;
use Src\Application\Enums\ProfileUpdateErrorType;
use Src\Application\ValueObjects\ProfileUpdateOutcome;
use Src\Application\ValueObjects\ProfileUpdateResult;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansClientInterface;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansProfileStatsParser;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;
use Throwable;

readonly class ProfileUpdater
{
    public function __construct(
        private OnlyFansClientInterface $onlyFansClient,
        private OnlyFansProfileStatsParser $onlyFansProfileStatsParser,
        private ProfileUpdateRecorder $recorder,
    ) {}

    /**
     * @throws Throwable
     */
    public function update(ProfileUpdateDataDTO $data): ProfileUpdateResult
    {
        $startedAt = now();
        $response = $this->onlyFansClient->fetchProfile($data->profile->username);

        if (! $response->isOk()) {
            return $this->record($data, $this->transportFailure($response), $startedAt);
        }

        try {
            $stats = $this->onlyFansProfileStatsParser->parse($response->payload);
        } catch (InvalidPayloadException $exception) {
            return $this->record($data, $this->payloadFailure($response, $exception), $startedAt);
        }

        return $this->recorder->recordStats(new ProfileUpdateStatsDTO($data, $stats, $response->status, $startedAt));
    }

    private function record(
        ProfileUpdateDataDTO $data,
        ProfileUpdateOutcome $outcome,
        CarbonInterface $startedAt,
    ): ProfileUpdateResult
    {
        return $this->recorder->record(new ProfileUpdateRecordDTO($data, $outcome, $startedAt));
    }

    private function transportFailure(OnlyFansProfileResponse $response): ProfileUpdateOutcome
    {
        $errorType = ProfileUpdateErrorType::fromResponseKind($response->kind);

        return ProfileUpdateOutcome::failed(
            $response->status,
            $errorType,
            $response->message ?? $errorType->value,
        );
    }

    private function payloadFailure(OnlyFansProfileResponse $response, InvalidPayloadException $exception): ProfileUpdateOutcome
    {
        return ProfileUpdateOutcome::failed(
            $response->status,
            ProfileUpdateErrorType::InvalidPayload,
            $exception->getMessage(),
            retryable: false,
        );
    }
}
