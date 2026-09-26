<?php

namespace App\Models;

use Database\Factories\ProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use Src\Application\Enums\ProfileQueue;
use Src\Application\Enums\ProfileRefreshStatus;

#[Fillable([
    'uuid',
    'username',
    'likes',
    'revision',
    'last_attempt_at',
    'last_successful_refresh_at',
    'last_failure',
])]
class Profile extends Model
{
    /** @use HasFactory<ProfileFactory> */
    use HasFactory, HasUuids, Searchable;

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * @return HasMany<ProfileUpdateAttempt, $this>
     */
    public function updateAttempts(): HasMany
    {
        return $this->hasMany(ProfileUpdateAttempt::class);
    }

    public function refreshIntervalHours(): int
    {
        return $this->isHeavy() ? 24 : 72;
    }

    public function queue(): ProfileQueue
    {
        return $this->isHeavy() ? ProfileQueue::Heavy : ProfileQueue::Profiles;
    }

    public function refreshStatus(): ProfileRefreshStatus
    {
        $attemptIsNewer = ! is_null($this->last_attempt_at)
            && ! is_null($this->last_successful_refresh_at)
            && $this->last_attempt_at > $this->last_successful_refresh_at;

        return match (true) {
            ! is_null($this->last_failure) && (is_null($this->last_successful_refresh_at) || $attemptIsNewer) => ProfileRefreshStatus::Failed,
            ! is_null($this->last_successful_refresh_at) => ProfileRefreshStatus::Ok,
            default => ProfileRefreshStatus::Pending,
        };
    }

    private function isHeavy(): bool
    {
        return ($this->likes ?? 0) > 100_000;
    }

    protected function casts(): array
    {
        return [
            'likes' => 'integer',
            'revision' => 'integer',
            'last_attempt_at' => 'datetime',
            'last_successful_refresh_at' => 'datetime',
            'last_failure' => 'array',
        ];
    }
}
