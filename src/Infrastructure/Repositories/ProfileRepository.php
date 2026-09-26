<?php

namespace Src\Infrastructure\Repositories;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Collection;

readonly class ProfileRepository
{
    public function __construct(private Profile $profile) {}

    public function getAll(): Collection
    {
        return $this->profile->newQuery()
            ->orderBy('username')
            ->get();
    }

    public function save(Profile $profile): Profile
    {
        $profile->save();

        return $profile;
    }

    public function lockForUpdate(Profile $profile): Profile
    {
        return $this->profile->newQuery()
            ->whereKey($profile->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function dueForUpdate(): Collection
    {
        $heavyBefore = now()->subHours(24);
        $lightBefore = now()->subHours(72);

        return $this->profile->newQuery()
            ->where(function ($query) use ($heavyBefore, $lightBefore): void {
                $query->whereNull('last_successful_refresh_at')
                    ->orWhere(function ($heavy) use ($heavyBefore): void {
                        $heavy->where('likes', '>', 100_000)
                            ->where('last_successful_refresh_at', '<=', $heavyBefore);
                    })
                    ->orWhere(function ($light) use ($lightBefore): void {
                        $light->where(function ($likes): void {
                            $likes->whereNull('likes')
                                ->orWhere('likes', '<=', 100_000);
                        })->where('last_successful_refresh_at', '<=', $lightBefore);
                    });
            })
            ->orderBy('id')
            ->get();
    }
}
