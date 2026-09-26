<?php

namespace Src\Application\DTOs;

use Carbon\CarbonInterface;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\ParsedProfileStats;

final readonly class ProfileUpdateStatsDTO
{
    public function __construct(
        public ProfileUpdateDataDTO $data,
        public ParsedProfileStats $stats,
        public int $httpStatus,
        public CarbonInterface $startedAt,
    ) {}
}
