<?php

namespace Tests\Stubs;

use Src\Infrastructure\Integrations\OnlyFans\OnlyFansClient;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansErrorClassifier;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;

final class StubOnlyFansClient extends OnlyFansClient
{
    /** @var array<string, list<OnlyFansProfileResponse>> */
    private array $queued = [];

    /** @var array<string, OnlyFansProfileResponse> */
    private array $defaults = [];

    public function __construct()
    {
        parent::__construct(new OnlyFansErrorClassifier);
    }

    public function always(string $username, OnlyFansProfileResponse $response): self
    {
        $this->defaults[$username] = $response;

        return $this;
    }

    public function next(string $username, OnlyFansProfileResponse $response): self
    {
        $this->queued[$username][] = $response;

        return $this;
    }

    public function fetchProfile(string $username): OnlyFansProfileResponse
    {
        if (($this->queued[$username] ?? []) !== []) {
            return array_shift($this->queued[$username]);
        }

        return $this->defaults[$username] ?? OnlyFansProfileResponse::ok([]);
    }
}
