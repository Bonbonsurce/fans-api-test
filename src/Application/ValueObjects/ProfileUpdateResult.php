<?php

namespace Src\Application\ValueObjects;

use Src\Application\Enums\ProfileUpdateAttemptStatus;
use Src\Application\Enums\ProfileUpdateErrorType;

final readonly class ProfileUpdateResult
{
    public function __construct(
        public ProfileUpdateAttemptStatus $status,
        public bool $retryable,
        public ?ProfileUpdateErrorType $errorType = null,
    ) {}
}
