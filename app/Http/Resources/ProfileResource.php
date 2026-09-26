<?php

namespace App\Http\Resources;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Profile
 */
class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'username' => $this->username,
            'likes' => $this->likes,
            'revision' => $this->revision,
            'last_attempt_at' => $this->last_attempt_at,
            'last_successful_refresh_at' => $this->last_successful_refresh_at,
            'last_failure' => $this->last_failure,
            'refresh_interval_hours' => $this->refreshIntervalHours(),
            'status' => $this->refreshStatus()->value,
        ];
    }
}
