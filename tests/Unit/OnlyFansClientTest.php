<?php

namespace Tests\Unit;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Onlyfansapi\Core\Exceptions\APIConnectionException;
use Onlyfansapi\Core\Exceptions\APIException;
use Onlyfansapi\Core\Exceptions\APIStatusException;
use Onlyfansapi\Core\Exceptions\APITimeoutException;
use Onlyfansapi\Profiles\ProfileGetResponse as SdkProfileGetResponse;
use PHPUnit\Framework\Attributes\Test;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansClient;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansErrorClassifier;
use Tests\TestCase;

class OnlyFansClientTest extends TestCase
{
    #[Test]
    public function it_returns_ok_for_a_successful_response(): void
    {
        $client = $this->clientThatReturns(SdkProfileGetResponse::with());

        $response = $client->fetchProfile('madison420ivy');

        $this->assertTrue($response->isOk());
        $this->assertSame(OnlyFansResponseKind::Ok, $response->kind);
        $this->assertSame(200, $response->status);
    }

    #[Test]
    public function it_distinguishes_rate_limit_server_timeout_and_connection_errors(): void
    {
        $request = new Request('GET', 'https://example.test/profiles/madison420ivy');

        $rateLimited = $this->clientThatThrows(
            APIStatusException::from($request, new Response(429, [], '{}')),
        )->fetchProfile('madison420ivy');
        $this->assertSame(OnlyFansResponseKind::RateLimited, $rateLimited->kind);
        $this->assertSame(429, $rateLimited->status);

        $serverError = $this->clientThatThrows(
            APIStatusException::from($request, new Response(500, [], '{}')),
        )->fetchProfile('madison420ivy');
        $this->assertSame(OnlyFansResponseKind::ServerError, $serverError->kind);
        $this->assertSame(500, $serverError->status);

        $timeout = $this->clientThatThrows(new APITimeoutException($request))->fetchProfile('madison420ivy');
        $this->assertSame(OnlyFansResponseKind::Timeout, $timeout->kind);

        $connection = $this->clientThatThrows(new APIConnectionException($request))->fetchProfile('madison420ivy');
        $this->assertSame(OnlyFansResponseKind::ConnectionError, $connection->kind);

        $notFound = $this->clientThatThrows(
            APIStatusException::from($request, new Response(404, [], '{}')),
        )->fetchProfile('madison420ivy');
        $this->assertSame(OnlyFansResponseKind::PermanentFailure, $notFound->kind);
        $this->assertSame(404, $notFound->status);
    }

    private function clientThatReturns(SdkProfileGetResponse $sdkResponse): OnlyFansClient
    {
        return new class($sdkResponse) extends OnlyFansClient
        {
            public function __construct(private SdkProfileGetResponse $sdkResponse)
            {
                parent::__construct(new OnlyFansErrorClassifier);
            }

            protected function retrieveProfile(string $username): SdkProfileGetResponse
            {
                return $this->sdkResponse;
            }
        };
    }

    private function clientThatThrows(APIException $exception): OnlyFansClient
    {
        return new class($exception) extends OnlyFansClient
        {
            public function __construct(private APIException $exception)
            {
                parent::__construct(new OnlyFansErrorClassifier);
            }

            protected function retrieveProfile(string $username): SdkProfileGetResponse
            {
                throw $this->exception;
            }
        };
    }
}
