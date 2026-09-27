<?php

namespace Src\Application\Services\Workload;

use Illuminate\Support\Facades\File;
use JsonException;
use Src\Application\DTOs\WorkloadPhaseResultDTO;
use Src\Application\Enums\WorkloadPhase;

final class WorkloadReport
{
    /**
     * @param  list<string>  $accounts
     * @param  array<string, WorkloadPhaseResultDTO>  $phases
     *
     * @throws JsonException
     */
    public function store(string $requestedPhase, int $timeoutSeconds, array $accounts, array $phases): string
    {
        $payload = [
            'recorded_at' => now()->toIso8601String(),
            'requested_phase' => $requestedPhase,
            'timeout_seconds' => $timeoutSeconds,
            'accounts' => $accounts,
            'metrics' => [
                WorkloadPhase::Before->value => ($phases[WorkloadPhase::Before->value] ?? null)?->metrics->toArray(),
                WorkloadPhase::After->value => ($phases[WorkloadPhase::After->value] ?? null)?->metrics->toArray(),
            ],
            'before' => ($phases[WorkloadPhase::Before->value] ?? null)?->toArray(),
            'after' => ($phases[WorkloadPhase::After->value] ?? null)?->toArray(),
        ];

        $directory = storage_path('app/workload-reports');
        File::ensureDirectoryExists($directory);

        $path = $directory.'/'.now()->format('Ymd-His').'.json';
        File::put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        logger()->info('workload.recorded', [
            'path' => $path,
            'metrics' => $payload['metrics'],
        ]);

        return $path;
    }
}
