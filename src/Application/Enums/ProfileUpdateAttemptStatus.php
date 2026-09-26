<?php

namespace Src\Application\Enums;

enum ProfileUpdateAttemptStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Rejected = 'rejected';

    public function isSucceeded(): bool
    {
        return $this === self::Succeeded;
    }
}
