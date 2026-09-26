<?php

namespace Src\Application\Enums;

use InvalidArgumentException;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;

enum ProfileUpdateErrorType: string
{
    case RateLimited = 'rate_limited';
    case ServerError = 'server_error';
    case Timeout = 'timeout';
    case ConnectionError = 'connection_error';
    case InvalidPayload = 'invalid_payload';
    case StaleRevision = 'stale_revision';
    case Permanent = 'permanent';

    public static function fromResponseKind(OnlyFansResponseKind $kind): self
    {
        return match ($kind) {
            OnlyFansResponseKind::RateLimited => self::RateLimited,
            OnlyFansResponseKind::ServerError => self::ServerError,
            OnlyFansResponseKind::Timeout => self::Timeout,
            OnlyFansResponseKind::ConnectionError => self::ConnectionError,
            OnlyFansResponseKind::PermanentFailure => self::Permanent,
            OnlyFansResponseKind::Ok => throw new InvalidArgumentException('Successful responses have no error type.'),
        };
    }

    public function isRetryable(): bool
    {
        return match ($this) {
            self::RateLimited, self::ServerError, self::Timeout, self::ConnectionError => true,
            self::InvalidPayload, self::StaleRevision, self::Permanent => false,
        };
    }

    public function isRateLimited(): bool
    {
        return $this === self::RateLimited;
    }
}
