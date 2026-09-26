<?php

namespace Src\Application\Services;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Collection;
use Src\Infrastructure\Repositories\ProfileRepository;

readonly class ProfileService
{
    public function __construct(
        private ProfileRepository $profileRepository,
    ) {}

    /**
     * @return Collection<int, Profile>
     */
    public function all(): Collection
    {
        return $this->profileRepository->getAll();
    }
}
