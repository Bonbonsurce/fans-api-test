<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Onlyfansapi\Profiles\ProfileGetResponse as SdkProfileGetResponse;

final readonly class ProfileGetResponseAdapter
{
    public function __construct(private SdkProfileGetResponse $response) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->toArrayRecursive($this->response->__serialize());

        return $payload;
    }

    private function toArrayRecursive(mixed $value): mixed
    {
        if (is_object($value) && method_exists($value, '__serialize')) {
            return $this->toArrayRecursive($value->__serialize());
        }

        if (is_array($value)) {
            return array_map($this->toArrayRecursive(...), $value);
        }

        return $value;
    }
}
