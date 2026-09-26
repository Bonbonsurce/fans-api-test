<?php

namespace Src\Infrastructure\Integrations\ValueObjects\OnlyFans;

use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;

final readonly class OnlyFansProfileResponse
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public OnlyFansResponseKind $kind,
        public int $status,
        public array $payload,
        public ?string $message = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function ok(array $payload): self
    {
        return new self(OnlyFansResponseKind::Ok, 200, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function failure(OnlyFansResponseKind $kind, int $status, array $payload = [], ?string $message = null): self
    {
        return new self($kind, $status, $payload, $message);
    }

    public function isOk(): bool
    {
        return $this->kind->isOk() && $this->status === 200;
    }
}
