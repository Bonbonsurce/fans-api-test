<?php

namespace Src\Application\Enums\OnlyFans;

enum OnlyFansResponseKind: string
{
    case Ok = 'ok';
    case RateLimited = 'rate_limited';
    case ServerError = 'server_error';
    case Timeout = 'timeout';
    case ConnectionError = 'connection_error';
    case PermanentFailure = 'permanent_failure';

    public function isOk(): bool
    {
        return $this === self::Ok;
    }
}
