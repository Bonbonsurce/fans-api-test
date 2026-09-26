<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Src\Application\Services\ProfileUpdateDispatcher;

#[Signature('app:profile-updates')]
#[Description('Queue profile updates that are due')]
class ProfileUpdates extends Command
{
    public function handle(ProfileUpdateDispatcher $dispatcher): int
    {
        $due = $dispatcher->dispatchDue();

        $this->components->info("Due profiles: $due.");

        return self::SUCCESS;
    }
}
