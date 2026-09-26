<?php

namespace Src\Application\DTOs;

use App\Models\Profile;

final readonly class ProfileUpdateDataDTO
{
    public function __construct(
        public Profile $profile,
        public int $attemptNumber,
        public ?string $jobUuid = null,
    ) {}
}
