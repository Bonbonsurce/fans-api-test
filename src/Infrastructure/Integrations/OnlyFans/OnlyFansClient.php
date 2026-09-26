<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Onlyfansapi\Client;
use Onlyfansapi\Core\Exceptions\APIException;
use Onlyfansapi\Profiles\ProfileGetResponse as SdkProfileGetResponse;
use RuntimeException;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;

class OnlyFansClient implements OnlyFansClientInterface
{
    private readonly ?Client $sdk;

    public function __construct(
        private readonly OnlyFansErrorClassifier $classifier,
        ?Client $sdk = null,
    )
    {
        $this->sdk = $sdk ?? $this->sdkFromConfig();

        if (!is_null($this->sdk) && trim($this->sdk->apiKey) === '') {
            throw new RuntimeException('Only Fans Api Key is not configured.');
        }
    }

    private function sdkFromConfig(): ?Client
    {
        $apiKey = config('services.onlyfans.api_key');
        $baseUrl = config('services.onlyfans.base_url');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            return null;
        }

        return new Client(
            apiKey: $apiKey,
            baseUrl: is_string($baseUrl) && $baseUrl !== '' ? $baseUrl : null,
        );
    }

    public function fetchProfile(string $username): OnlyFansProfileResponse
    {
        try {
            $response = $this->retrieveProfile($username);

            return new OnlyFansProfileResponse(
                OnlyFansResponseKind::Ok,
                200,
                (new ProfileGetResponseAdapter($response))->toArray(),
            );
        } catch (APIException $exception) {
            return new OnlyFansProfileResponse(
                $this->classifier->classify($exception),
                $exception->status ?? 0,
                is_array($exception->body) ? $exception->body : [],
                $exception->getMessage(),
            );
        }
    }

    /**
     * @throws APIException
     */
    protected function retrieveProfile(string $username): SdkProfileGetResponse
    {
        return ($this->sdk ?? throw new RuntimeException('OnlyFans SDK client is not configured.'))
            ->profiles
            ->retrieve($username, fresh: true);
    }
}
