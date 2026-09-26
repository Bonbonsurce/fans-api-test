<?php

namespace Src\Application\Services;

use App\Jobs\UpdateProfileJob;
use App\Models\Profile;
use Src\Infrastructure\Repositories\ProfileRepository;

readonly class ProfileUpdateDispatcher
{
    public function __construct(private ProfileRepository $profiles) {}

    public function dispatchDue(): int
    {
        $due = $this->profiles->dueForUpdate();

        $due->each(fn (Profile $profile) => UpdateProfileJob::dispatch($profile));

        return $due->count();
    }
}
