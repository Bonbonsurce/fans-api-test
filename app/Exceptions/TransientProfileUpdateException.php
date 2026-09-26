<?php

namespace App\Exceptions;

use RuntimeException;
use Src\Application\Enums\ProfileUpdateErrorType;

final class TransientProfileUpdateException extends RuntimeException
{
    public function __construct(public readonly ProfileUpdateErrorType $errorType)
    {
        parent::__construct("Profile update failed with a transient $errorType->value error.");
    }
}
