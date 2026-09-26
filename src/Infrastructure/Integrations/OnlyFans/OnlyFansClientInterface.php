<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;

interface OnlyFansClientInterface
{
    public function fetchProfile(string $username): OnlyFansProfileResponse;
}
