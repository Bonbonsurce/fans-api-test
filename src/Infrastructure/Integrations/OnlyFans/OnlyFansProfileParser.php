<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\ParsedProfileStats;

/**
 * @deprecated Use {@see OnlyFansProfileStatsParser}. This parser only understands top-level likes, revision in payload
 */
final class OnlyFansProfileParser
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function parse(array $payload): ParsedProfileStats
    {
        return new ParsedProfileStats(
            likes: (int) ($payload['likes'] ?? 0),
            revision: (int) ($payload['revision'] ?? 0),
        );
    }
}
