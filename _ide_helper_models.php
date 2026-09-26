<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property string $username
 * @property int|null $likes
 * @property int|null $revision
 * @property \Carbon\CarbonImmutable|null $last_attempt_at
 * @property \Carbon\CarbonImmutable|null $last_successful_refresh_at
 * @property array<array-key, mixed>|null $last_failure
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProfileUpdateAttempt> $updateAttempts
 * @property-read int|null $update_attempts_count
 * @method static \Database\Factories\ProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLastAttemptAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLastFailure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLastSuccessfulRefreshAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereLikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereRevision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUsername($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Profile whereUuid($value)
 */
	class Profile extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $profile_id
 * @property \Src\Application\Enums\ProfileUpdateAttemptStatus $status
 * @property int $attempt_number
 * @property int|null $http_status
 * @property int|null $revision
 * @property int|null $likes
 * @property \Src\Application\Enums\ProfileUpdateErrorType|null $error_type
 * @property string|null $error_message
 * @property \Carbon\CarbonImmutable $started_at
 * @property \Carbon\CarbonImmutable|null $finished_at
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \App\Models\Profile $profile
 * @method static \Database\Factories\ProfileUpdateAttemptFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereAttemptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereErrorType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereFinishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereHttpStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereLikes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereRevision($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProfileUpdateAttempt whereUpdatedAt($value)
 */
	class ProfileUpdateAttempt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Carbon\CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Carbon\CarbonImmutable|null $created_at
 * @property \Carbon\CarbonImmutable|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

