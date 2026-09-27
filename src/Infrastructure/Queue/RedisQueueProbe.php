<?php

namespace Src\Infrastructure\Queue;

use Illuminate\Queue\RedisQueue;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

final class RedisQueueProbe
{
    public function depth(string $queue): int
    {
        return $this->connection()->size($queue);
    }

    public function oldestAgeSeconds(string ...$queues): int
    {
        return max(0, ...array_map(fn (string $queue): int => $this->oldestAgeFor($queue), $queues));
    }

    public function isDrained(string ...$queues): bool
    {
        foreach ($queues as $queue) {
            if ($this->depth($queue) > 0) {
                return false;
            }
        }

        return true;
    }

    private function oldestAgeFor(string $queue): int
    {
        $connection = $this->connection();
        $now = now()->getTimestamp();
        $oldest = 0;

        foreach ([
            ...$connection->pendingJobs($queue),
            ...$connection->delayedJobs($queue),
            ...$connection->reservedJobs($queue),
        ] as $job) {
            if (is_null($job->createdAt)) {
                continue;
            }

            $oldest = max($oldest, $now - $job->createdAt->getTimestamp());
        }

        return $oldest;
    }

    private function connection(): RedisQueue
    {
        $connection = Queue::connection('redis');

        if (! $connection instanceof RedisQueue) {
            throw new RuntimeException('Redis queue connection is not available.');
        }

        return $connection;
    }
}
