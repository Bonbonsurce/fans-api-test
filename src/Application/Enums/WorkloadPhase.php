<?php

namespace Src\Application\Enums;

enum WorkloadPhase: string
{
    case Before = 'before';
    case After = 'after';

    public function usesSingleQueue(): bool
    {
        return $this === self::Before;
    }

    public function label(): string
    {
        return match ($this) {
            self::Before => 'before (single queue)',
            self::After => 'after (split queues)',
        };
    }
}
