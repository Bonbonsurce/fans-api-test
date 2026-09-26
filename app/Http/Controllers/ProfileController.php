<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\View\View;
use Src\Application\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function index(): View
    {
        return view('profiles.index', [
            'profiles' => $this->profileService->all(),
        ]);
    }

    public function show(Profile $profile): View
    {
        return view('profiles.show', compact('profile'));
    }
}
