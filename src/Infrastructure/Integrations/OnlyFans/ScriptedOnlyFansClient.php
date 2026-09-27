<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;

final readonly class ScriptedOnlyFansClient implements OnlyFansClientInterface
{
    public function __construct(private WorkloadScript $scripts) {}

    public function fetchProfile(string $username): OnlyFansProfileResponse
    {
        return $this->scripts->pull($username) ?? OnlyFansProfileResponse::failure(
            OnlyFansResponseKind::PermanentFailure,
            404,
            message: 'No workload script for '.$username,
        );
    }
}
