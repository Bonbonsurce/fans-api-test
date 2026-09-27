<?php

namespace Src\Application\DTOs;

use Carbon\CarbonInterface;
use Src\Application\Enums\WorkloadPhase;

final readonly class WorkloadPhaseResultDTO
{
    /**
     * @param  array<string, int>  $queuesRemaining
     * @param  list<array<string, mixed>>  $profiles
     */
    public function __construct(
        public WorkloadPhase $phase,
        public WorkloadMetricsDTO $metrics,
        public CarbonInterface $startedAt,
        public CarbonInterface $finishedAt,
        public array $queuesRemaining,
        public array $profiles,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'phase' => $this->phase->value,
            'label' => $this->phase->label(),
            'single_queue' => $this->phase->usesSingleQueue(),
            'started_at' => $this->startedAt->toIso8601String(),
            'finished_at' => $this->finishedAt->toIso8601String(),
            ...$this->metrics->toArray(),
            'queues_remaining' => $this->queuesRemaining,
            'profiles' => $this->profiles,
        ];
    }
}
