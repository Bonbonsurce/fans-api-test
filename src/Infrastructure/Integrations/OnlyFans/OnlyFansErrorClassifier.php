<?php

namespace Src\Infrastructure\Integrations\OnlyFans;

use Onlyfansapi\Core\Exceptions\APIConnectionException;
use Onlyfansapi\Core\Exceptions\APIException;
use Onlyfansapi\Core\Exceptions\APITimeoutException;
use Onlyfansapi\Core\Exceptions\InternalServerException;
use Onlyfansapi\Core\Exceptions\RateLimitException;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;

final class OnlyFansErrorClassifier
{
    public function classify(APIException $exception): OnlyFansResponseKind
    {
        return match (true) {
            $exception instanceof APITimeoutException => OnlyFansResponseKind::Timeout,
            $exception instanceof APIConnectionException => OnlyFansResponseKind::ConnectionError,
            $exception instanceof RateLimitException,
            $exception->status === 429 => OnlyFansResponseKind::RateLimited,
            $exception->status === 408 => OnlyFansResponseKind::Timeout,
            $exception instanceof InternalServerException,
            ($exception->status ?? 0) >= 500 => OnlyFansResponseKind::ServerError,
            default => OnlyFansResponseKind::PermanentFailure,
        };
    }
}
