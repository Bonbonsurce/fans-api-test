<?php

namespace Tests\Unit;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Onlyfansapi\Core\Exceptions\APIConnectionException;
use Onlyfansapi\Core\Exceptions\APIStatusException;
use Onlyfansapi\Core\Exceptions\APITimeoutException;
use PHPUnit\Framework\Attributes\Test;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Infrastructure\Integrations\OnlyFans\OnlyFansErrorClassifier;
use Tests\TestCase;

class OnlyFansErrorClassifierTest extends TestCase
{
    private OnlyFansErrorClassifier $classifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->classifier = new OnlyFansErrorClassifier;
    }

    #[Test]
    public function it_classifies_rate_limits(): void
    {
        $exception = APIStatusException::from(
            new Request('GET', 'https://example.test/profiles/madison420ivy'),
            new Response(429, [], '{}'),
        );

        $this->assertSame(OnlyFansResponseKind::RateLimited, $this->classifier->classify($exception));
    }

    #[Test]
    public function it_classifies_server_errors(): void
    {
        $exception = APIStatusException::from(
            new Request('GET', 'https://example.test/profiles/madison420ivy'),
            new Response(500, [], '{}'),
        );

        $this->assertSame(OnlyFansResponseKind::ServerError, $this->classifier->classify($exception));
    }

    #[Test]
    public function it_classifies_client_errors_as_permanent(): void
    {
        $request = new Request('GET', 'https://example.test/profiles/madison420ivy');

        $notFound = APIStatusException::from($request, new Response(404, [], '{}'));
        $unauthorized = APIStatusException::from($request, new Response(401, [], '{}'));

        $this->assertSame(OnlyFansResponseKind::PermanentFailure, $this->classifier->classify($notFound));
        $this->assertSame(OnlyFansResponseKind::PermanentFailure, $this->classifier->classify($unauthorized));
    }

    #[Test]
    public function it_classifies_timeouts_separately_from_connection_errors(): void
    {
        $request = new Request('GET', 'https://example.test/profiles/madison420ivy');

        $this->assertSame(
            OnlyFansResponseKind::Timeout,
            $this->classifier->classify(new APITimeoutException($request)),
        );
        $this->assertSame(
            OnlyFansResponseKind::ConnectionError,
            $this->classifier->classify(new APIConnectionException($request)),
        );
    }
}
