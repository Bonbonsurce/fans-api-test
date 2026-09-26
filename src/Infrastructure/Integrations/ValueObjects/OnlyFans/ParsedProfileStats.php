<?php

namespace Src\Infrastructure\Integrations\ValueObjects\OnlyFans;

final readonly class ParsedProfileStats
{
    public function __construct(
        public int $likes,
        public int $revision,
    ) {}
}
