<?php

namespace App\Models;

use Database\Factories\ProfileUpdateAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Application\Enums\ProfileUpdateAttemptStatus;
use Src\Application\Enums\ProfileUpdateErrorType;

#[Fillable([
    'profile_id',
    'status',
    'attempt_number',
    'http_status',
    'revision',
    'likes',
    'error_type',
    'error_message',
    'started_at',
    'finished_at',
])]
class ProfileUpdateAttempt extends Model
{
    /** @use HasFactory<ProfileUpdateAttemptFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Profile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    protected function casts(): array
    {
        return [
            'status' => ProfileUpdateAttemptStatus::class,
            'attempt_number' => 'integer',
            'http_status' => 'integer',
            'revision' => 'integer',
            'likes' => 'integer',
            'error_type' => ProfileUpdateErrorType::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
