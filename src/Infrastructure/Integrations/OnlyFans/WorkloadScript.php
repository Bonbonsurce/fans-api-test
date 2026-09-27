<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Illuminate\Support\Facades\Cache;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;

final class WorkloadScript
{
    private const string PREFIX = 'onlyfans-workload:';

    private const int TTL_HOURS = 2;

    /**
     * @param  list<OnlyFansProfileResponse>  $responses
     */
    public function put(string $username, array $responses): void
    {
        $this->store($username, array_map($this->encode(...), $responses));
    }

    public function pull(string $username): ?OnlyFansProfileResponse
    {
        $steps = $this->load($username);

        if ($steps === []) {
            return null;
        }

        $next = array_shift($steps);
        $this->persistRemaining($username, $steps);

        return is_array($next) ? $this->decode($next) : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function load(string $username): array
    {
        $steps = Cache::get($this->key($username));

        return is_array($steps) ? array_values($steps) : [];
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     */
    private function persistRemaining(string $username, array $steps): void
    {
        if ($steps === []) {
            Cache::forget($this->key($username));

            return;
        }

        $this->store($username, $steps);
    }

    /**
     * @param  list<array<string, mixed>>  $steps
     */
    private function store(string $username, array $steps): void
    {
        Cache::put($this->key($username), $steps, now()->addHours(self::TTL_HOURS));
    }

    /**
     * @return array{kind: string, status: int, payload: array<string, mixed>, message: string|null}
     */
    private function encode(OnlyFansProfileResponse $response): array
    {
        return [
            'kind' => $response->kind->value,
            'status' => $response->status,
            'payload' => $response->payload,
            'message' => $response->message,
        ];
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function decode(array $step): OnlyFansProfileResponse
    {
        return new OnlyFansProfileResponse(
            OnlyFansResponseKind::from((string) $step['kind']),
            (int) $step['status'],
            is_array($step['payload'] ?? null) ? $step['payload'] : [],
            is_string($step['message'] ?? null) ? $step['message'] : null,
        );
    }

    private function key(string $username): string
    {
        return self::PREFIX.$username;
    }
}
