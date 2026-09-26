<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProfileController::class, 'index'])->name('profiles.index');
Route::get('/{profile}', [ProfileController::class, 'show'])
    ->whereUuid('profile')
    ->name('profiles.show');
