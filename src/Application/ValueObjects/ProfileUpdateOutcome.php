<?php

namespace Src\Application\ValueObjects;

use Src\Application\Enums\ProfileUpdateAttemptStatus;
use Src\Application\Enums\ProfileUpdateErrorType;

final readonly class ProfileUpdateOutcome
{
    public function __construct(
        public ProfileUpdateAttemptStatus $status,
        public int $httpStatus,
        public bool $retryable,
        public ?ProfileUpdateErrorType $errorType = null,
        public ?string $errorMessage = null,
        public ?int $likes = null,
        public ?int $revision = null,
    ) {}

    public static function failed(
        int $httpStatus,
        ProfileUpdateErrorType $errorType,
        string $message,
        ?bool $retryable = null,
        ?int $likes = null,
        ?int $revision = null,
    ): self
    {
        return new self(
            status: ProfileUpdateAttemptStatus::Failed,
            httpStatus: $httpStatus,
            retryable: $retryable ?? $errorType->isRetryable(),
            errorType: $errorType,
            errorMessage: $message,
            likes: $likes,
            revision: $revision,
        );
    }

    public static function rejected(int $httpStatus, int $likes, int $revision): self
    {
        return new self(
            status: ProfileUpdateAttemptStatus::Rejected,
            httpStatus: $httpStatus,
            retryable: false,
            errorType: ProfileUpdateErrorType::StaleRevision,
            errorMessage: 'Older revision must not overwrite newer accepted data.',
            likes: $likes,
            revision: $revision,
        );
    }

    public static function succeeded(int $httpStatus, int $likes, int $revision): self
    {
        return new self(
            status: ProfileUpdateAttemptStatus::Succeeded,
            httpStatus: $httpStatus,
            retryable: false,
            likes: $likes,
            revision: $revision,
        );
    }

    public function result(): ProfileUpdateResult
    {
        return new ProfileUpdateResult($this->status, $this->retryable, $this->errorType);
    }
}
