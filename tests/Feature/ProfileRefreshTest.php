<?php

namespace Tests\Feature;

use App\Exceptions\TransientProfileUpdateException;
use App\Jobs\UpdateProfileJob;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Src\Application\Enums\OnlyFans\OnlyFansResponseKind;
use Src\Application\Enums\ProfileUpdateAttemptStatus;
use Src\Application\Enums\ProfileUpdateErrorType;
use Src\Application\Services\ProfileUpdater;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;
use Tests\TestCase;

class ProfileRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_format_refresh_keeps_the_valid_likes_count(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok($this->fixture('old-format')));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertSame(10, $profile->revision);
        $this->assertNotNull($profile->last_successful_refresh_at);
        $this->assertNull($profile->last_failure);
        $this->assertSame(1, $profile->updateAttempts()->count());
        $this->assertSame(ProfileUpdateAttemptStatus::Succeeded, $profile->updateAttempts()->first()?->status);
    }

    public function test_nested_likes_format_stores_the_new_count(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok($this->fixture('new-format')));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(121000, $profile->likes);
        $this->assertSame(11, $profile->revision);
        $this->assertNotNull($profile->last_successful_refresh_at);
        $this->assertNull($profile->last_failure);
        $this->assertDatabaseHas('profile_update_attempts', [
            'profile_id' => $profile->id,
            'status' => ProfileUpdateAttemptStatus::Succeeded->value,
            'likes' => 121000,
            'revision' => 11,
            'http_status' => 200,
            'attempt_number' => 1,
        ]);
    }

    public function test_missing_likes_does_not_erase_the_last_valid_count(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $successfulRefreshAt = $profile->last_successful_refresh_at;
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok($this->fixture('missing-likes')));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertSame(10, $profile->revision);
        $this->assertTrue($profile->last_successful_refresh_at?->equalTo($successfulRefreshAt));
        $this->assertSame('invalid_payload', $profile->last_failure['kind'] ?? null);
        $this->assertDatabaseHas('profile_update_attempts', [
            'profile_id' => $profile->id,
            'status' => ProfileUpdateAttemptStatus::Failed->value,
            'error_type' => 'invalid_payload',
        ]);
    }

    public function test_http_429_is_recorded_as_a_rate_limit_and_does_not_wipe_data(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $successfulRefreshAt = $profile->last_successful_refresh_at;
        $this->onlyFans->next(
            $profile->username,
            OnlyFansProfileResponse::failure(OnlyFansResponseKind::RateLimited, 429),
        );

        $job = $this->handleUpdate($profile);
        $job->assertReleased();
        $this->assertGreaterThanOrEqual(8, $job->job->releaseDelay);
        $this->assertLessThanOrEqual(25, $job->job->releaseDelay);

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertTrue($profile->last_successful_refresh_at?->equalTo($successfulRefreshAt));
        $this->assertSame('rate_limited', $profile->last_failure['kind'] ?? null);
        $this->assertSame(429, $profile->last_failure['status'] ?? null);
        $this->assertDatabaseHas('profile_update_attempts', [
            'profile_id' => $profile->id,
            'status' => ProfileUpdateAttemptStatus::Failed->value,
            'error_type' => 'rate_limited',
            'http_status' => 429,
        ]);
    }

    public function test_http_500_with_an_empty_body_does_not_count_as_a_successful_refresh(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $successfulRefreshAt = $profile->last_successful_refresh_at;
        $this->onlyFans->next(
            $profile->username,
            OnlyFansProfileResponse::failure(OnlyFansResponseKind::ServerError, 500),
        );

        $this->runExpectingRetry($profile, ProfileUpdateErrorType::ServerError);

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertTrue($profile->last_successful_refresh_at?->equalTo($successfulRefreshAt));
        $this->assertSame('server_error', $profile->last_failure['kind'] ?? null);
        $this->assertSame(500, $profile->last_failure['status'] ?? null);
    }

    public function test_permanent_client_error_is_recorded_once_and_does_not_retry(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $successfulRefreshAt = $profile->last_successful_refresh_at;
        $this->onlyFans->next(
            $profile->username,
            OnlyFansProfileResponse::failure(OnlyFansResponseKind::PermanentFailure, 404, message: 'Profile not found.'),
        );

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertSame(10, $profile->revision);
        $this->assertTrue($profile->last_successful_refresh_at?->equalTo($successfulRefreshAt));
        $this->assertSame('permanent', $profile->last_failure['kind'] ?? null);
        $this->assertSame(404, $profile->last_failure['status'] ?? null);
        $this->assertDatabaseCount('profile_update_attempts', 1);
        $this->assertDatabaseHas('profile_update_attempts', [
            'profile_id' => $profile->id,
            'status' => ProfileUpdateAttemptStatus::Failed->value,
            'error_type' => 'permanent',
            'http_status' => 404,
        ]);
    }

    public function test_timeout_and_connection_errors_are_distinguishable(): void
    {
        $profile = $this->profileWithValidSnapshot();

        $this->onlyFans->next(
            $profile->username,
            OnlyFansProfileResponse::failure(OnlyFansResponseKind::Timeout, 0, message: 'Request timed out.'),
        );
        $this->runExpectingRetry($profile, ProfileUpdateErrorType::Timeout);
        $profile->refresh();
        $this->assertSame('timeout', $profile->last_failure['kind'] ?? null);

        $this->onlyFans->next(
            $profile->username,
            OnlyFansProfileResponse::failure(OnlyFansResponseKind::ConnectionError, 0, message: 'Connection failed.'),
        );
        $this->runExpectingRetry($profile, ProfileUpdateErrorType::ConnectionError);
        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertSame('connection_error', $profile->last_failure['kind'] ?? null);
        $this->assertSame(2, $profile->updateAttempts()->count());
    }

    public function test_an_older_revision_does_not_overwrite_newer_accepted_data(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok([
            'likes' => 119000,
            'revision' => 9,
        ]));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertSame(10, $profile->revision);
        $this->assertSame('stale_revision', $profile->last_failure['kind'] ?? null);
        $this->assertDatabaseHas('profile_update_attempts', [
            'profile_id' => $profile->id,
            'status' => ProfileUpdateAttemptStatus::Rejected->value,
            'error_type' => 'stale_revision',
            'revision' => 9,
            'likes' => 119000,
        ]);
    }

    public function test_a_completed_job_does_not_prove_the_profile_was_refreshed_successfully(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next(
            $profile->username,
            OnlyFansProfileResponse::failure(OnlyFansResponseKind::ServerError, 500),
        );

        $successfulRefreshAt = $profile->last_successful_refresh_at;

        $this->runExpectingRetry($profile, ProfileUpdateErrorType::ServerError);

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertTrue($profile->last_successful_refresh_at?->equalTo($successfulRefreshAt));
        $this->assertDatabaseHas('profile_update_attempts', [
            'profile_id' => $profile->id,
            'status' => ProfileUpdateAttemptStatus::Failed->value,
            'error_type' => 'server_error',
        ]);
    }

    public function test_each_update_writes_a_structured_log_without_secrets(): void
    {
        $logged = null;
        Log::listen(function (MessageLogged $event) use (&$logged): void {
            if ($event->message === 'profile.update.finished') {
                $logged = $event;
            }
        });

        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok($this->fixture('new-format')));

        $this->handleUpdate($profile);

        $this->assertNotNull($logged);
        $this->assertSame($profile->username, $logged->context['username'] ?? null);
        $this->assertSame($profile->uuid, $logged->context['profile_uuid'] ?? null);
        $this->assertSame(1, $logged->context['attempt_number'] ?? null);
        $this->assertArrayNotHasKey('api_key', $logged->context);
        $this->assertArrayNotHasKey('payload', $logged->context);
    }

    public function test_job_retry_timeout_and_backoff_match_the_chosen_limits(): void
    {
        $job = new UpdateProfileJob($this->profileWithValidSnapshot());

        $this->assertSame(5, $job->tries);
        $this->assertSame(30, $job->timeout);
        $this->assertSame(72 * 60 * 60, $job->uniqueFor());
        $this->assertSame([15, 45, 90, 180], $job->backoff());
    }

    public function test_an_equal_revision_is_accepted(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok([
            'likes' => 125000,
            'revision' => 10,
        ]));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(125000, $profile->likes);
        $this->assertSame(10, $profile->revision);
        $this->assertNull($profile->last_failure);
    }

    public function test_an_explicit_zero_replaces_the_stored_likes_count(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok([
            'likes' => 0,
            'revision' => 11,
        ]));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(0, $profile->likes);
        $this->assertSame(11, $profile->revision);
        $this->assertNull($profile->last_failure);
    }

    public function test_negative_likes_do_not_erase_the_last_valid_count(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->next($profile->username, OnlyFansProfileResponse::ok([
            'likes' => -1,
            'revision' => 12,
        ]));

        $this->handleUpdate($profile)->assertNotReleased();

        $profile->refresh();

        $this->assertSame(120000, $profile->likes);
        $this->assertSame(10, $profile->revision);
        $this->assertSame('invalid_payload', $profile->last_failure['kind'] ?? null);
    }

    public function test_replaying_a_finished_update_keeps_the_accepted_snapshot(): void
    {
        $profile = $this->profileWithValidSnapshot();
        $this->onlyFans->always($profile->username, OnlyFansProfileResponse::ok($this->fixture('new-format')));

        $this->handleUpdate($profile);
        $this->handleUpdate($profile->fresh());

        $profile->refresh();

        $this->assertSame(1, Profile::query()->count());
        $this->assertSame(121000, $profile->likes);
        $this->assertSame(11, $profile->revision);
        $this->assertSame(2, $profile->updateAttempts()->count());
        $this->assertSame(
            [ProfileUpdateAttemptStatus::Succeeded, ProfileUpdateAttemptStatus::Succeeded],
            $profile->updateAttempts()->orderBy('id')->pluck('status')->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(string $name): array
    {
        $path = base_path('tests/Fixtures/onlyfans/'.$name.'.json');

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $payload;
    }

    private function handleUpdate(Profile $profile): UpdateProfileJob
    {
        $job = new UpdateProfileJob($profile);
        $job->withFakeQueueInteractions();
        $job->handle(app(ProfileUpdater::class));

        return $job;
    }

    private function runExpectingRetry(Profile $profile, ProfileUpdateErrorType $errorType): void
    {
        $job = new UpdateProfileJob($profile);
        $job->withFakeQueueInteractions();

        try {
            $job->handle(app(ProfileUpdater::class));
            $this->fail('A '.$errorType->value.' response must be retried.');
        } catch (TransientProfileUpdateException $exception) {
            $this->assertSame($errorType, $exception->errorType);
        }

        $job->assertNotReleased();
    }

    private function profileWithValidSnapshot(): Profile
    {
        return Profile::factory()->create([
            'username' => 'madison420ivy',
            'likes' => 120_000,
            'revision' => 10,
            'last_attempt_at' => now()->subDay(),
            'last_successful_refresh_at' => now()->subDay(),
            'last_failure' => null,
        ]);
    }
}
