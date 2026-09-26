<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use App\Exceptions\InvalidPayloadException;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\ParsedProfileStats;

final class OnlyFansProfileStatsParser
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function parse(array $payload): ParsedProfileStats
    {
        return new ParsedProfileStats(
            likes: $this->normalizeInteger($this->extractLikes($payload), 'likes'),
            revision: $this->normalizeInteger($payload['revision'] ?? null, 'revision'),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractLikes(array $payload): mixed
    {
        if (array_key_exists('likes', $payload)) {
            return $payload['likes'];
        }

        $profile = $payload['profile'] ?? null;
        if (is_array($profile) && array_key_exists('likes', $profile)) {
            return $profile['likes'];
        }

        throw new InvalidPayloadException('Missing likes field.');
    }

    private function normalizeInteger(mixed $value, string $field): int
    {
        if (is_null($value)) {
            throw new InvalidPayloadException("Missing $field field.");
        }

        if (! is_numeric($value)) {
            throw new InvalidPayloadException("Non-numeric $field is invalid.");
        }

        if (is_string($value) && ! preg_match('/^-?\d+$/', $value)) {
            throw new InvalidPayloadException("Non-numeric $field is invalid.");
        }

        $normalized = (int) $value;

        if ((float) $value !== (float) $normalized) {
            throw new InvalidPayloadException("Non-numeric $field is invalid.");
        }

        if ($normalized < 0) {
            throw new InvalidPayloadException("Negative $field is invalid.");
        }

        return $normalized;
    }
}
