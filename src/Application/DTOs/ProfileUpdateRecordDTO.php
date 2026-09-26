<?php

namespace Src\Application\DTOs;

use Carbon\CarbonInterface;
use Src\Application\ValueObjects\ProfileUpdateOutcome;

final readonly class ProfileUpdateRecordDTO
{
    public function __construct(
        public ProfileUpdateDataDTO $data,
        public ProfileUpdateOutcome $outcome,
        public CarbonInterface $startedAt,
    ) {}
}
