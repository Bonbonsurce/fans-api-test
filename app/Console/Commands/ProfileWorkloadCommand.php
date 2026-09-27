<?php

namespace App\Console\Commands;

use App\Models\Profile;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use JsonException;
use Src\Application\DTOs\WorkloadPhaseResultDTO;
use Src\Application\Enums\ProfileQueue;
use Src\Application\Enums\WorkloadPhase;
use Src\Application\Services\Workload\WorkloadReport;
use Src\Application\Services\Workload\WorkloadRunner;
use Src\Application\Services\Workload\WorkloadScenario;

#[Signature('app:profile-workload {--phase=both : before, after, or both} {--timeout=120 : Seconds to wait for each phase}')]
#[Description('Queue a repeatable light/heavy profile refresh workload and record before/after metrics')]
class ProfileWorkloadCommand extends Command
{
    /**
     * @throws JsonException
     */
    public function handle(
        WorkloadScenario $scenario,
        WorkloadRunner $runner,
        WorkloadReport $report,
    ): int
    {
        $requestedPhase = (string) $this->option('phase');
        $timeout = max(1, (int) $this->option('timeout'));

        if (! $this->validateOptions($requestedPhase)) {
            return self::FAILURE;
        }

        $this->warnIfWorkloadModeDisabled();

        $this->prepareProfiles($scenario);
        $results = $this->runPhases($runner, $this->resolvePhases($requestedPhase), $timeout);
        $path = $report->store($requestedPhase, $timeout, $scenario->usernames(), $results);

        $this->components->info("Wrote metrics to $path");

        return self::SUCCESS;
    }

    private function validateOptions(string $requestedPhase): bool
    {
        if (! empty($this->resolvePhases($requestedPhase))) {
            return true;
        }

        $this->components->error('Phase must be before, after, or both.');

        return false;
    }

    private function warnIfWorkloadModeDisabled(): void
    {
        if (config('services.onlyfans.workload')) {
            return;
        }

        $this->components->warn(
            'ONLYFANS_WORKLOAD is off. Start Horizon with ONLYFANS_WORKLOAD=true so workers use the scripted client.'
        );
    }

    private function prepareProfiles(WorkloadScenario $scenario): void
    {
        $profiles = $scenario->prepare();
        $this->printPrepared($profiles);
    }

    /**
     * @param  list<WorkloadPhase>  $phases
     * @return array<string, WorkloadPhaseResultDTO>
     */
    private function runPhases(WorkloadRunner $runner, array $phases, int $timeout): array
    {
        $results = [];

        foreach ($phases as $phase) {
            $this->components->info('Running '.$phase->label().'...');
            $results[$phase->value] = $runner->run($phase, $timeout);
            $this->printMetrics($results[$phase->value]);
        }

        return $results;
    }

    /**
     * @return list<WorkloadPhase>
     */
    private function resolvePhases(string $requested): array
    {
        return match ($requested) {
            'before' => [WorkloadPhase::Before],
            'after' => [WorkloadPhase::After],
            'both' => [WorkloadPhase::Before, WorkloadPhase::After],
            default => [],
        };
    }

    /**
     * @param  Collection<int, Profile>  $profiles
     */
    private function printPrepared(Collection $profiles): void
    {
        $light = $profiles->filter(fn (Profile $profile) => $profile->queue() === ProfileQueue::Profiles)->count();
        $heavy = $profiles->filter(fn (Profile $profile) => $profile->queue() === ProfileQueue::Heavy)->count();

        $this->components->info(sprintf(
            'Prepared %d profiles (%d light, %d heavy). Watch http://volley.localhost/horizon',
            $profiles->count(),
            $light,
            $heavy,
        ));

        foreach ($profiles as $profile) {
            $this->line(sprintf(
                '  @%s likes=%s queue=%s',
                $profile->username,
                $profile->likes ?? 'null',
                $profile->queue()->value,
            ));
        }
    }

    private function printMetrics(WorkloadPhaseResultDTO $result): void
    {
        $metrics = $result->metrics;

        $this->line(sprintf(
            '  %s: successful=%d attempts=%d attempts_per_success=%s oldest_waiting_job_age=%ds drained=%s',
            $result->phase->value,
            $metrics->successfulRefreshes,
            $metrics->attempts,
            $metrics->attemptsPerSuccessfulRefresh ?? 'n/a',
            $metrics->oldestWaitingJobAgeSeconds,
            $metrics->drained ? 'yes' : 'no',
        ));
    }
}
