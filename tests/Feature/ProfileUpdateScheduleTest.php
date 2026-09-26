<?php

namespace Tests\Feature;

use App\Jobs\UpdateProfileJob;
use App\Models\Profile;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Src\Application\Enums\ProfileQueue;
use Tests\TestCase;

class ProfileUpdateScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_exactly_100000_likes_uses_the_72_hour_queue(): void
    {
        Queue::fake();

        Profile::factory()->create([
            'username' => 'boundary',
            'likes' => 100_000,
            'last_successful_refresh_at' => now()->subHours(24),
        ]);

        $this->artisan('app:profile-updates')->assertSuccessful();

        Queue::assertNothingPushed();

        Profile::factory()->create([
            'username' => 'boundary-due',
            'likes' => 100_000,
            'last_successful_refresh_at' => now()->subHours(72),
        ]);

        $this->artisan('app:profile-updates')->assertSuccessful();

        Queue::assertPushed(UpdateProfileJob::class, function (UpdateProfileJob $job): bool {
            return $job->profile->username === 'boundary-due' && $job->queue === ProfileQueue::Profiles->value;
        });
    }

    public function test_profiles_above_100000_likes_use_the_heavy_queue_every_24_hours(): void
    {
        Queue::fake();

        Profile::factory()->create([
            'username' => 'amouranth',
            'likes' => 100_001,
            'last_successful_refresh_at' => now()->subHours(23),
        ]);

        $this->artisan('app:profile-updates')->assertSuccessful();
        Queue::assertNothingPushed();

        Profile::query()->where('username', 'amouranth')->update([
            'last_successful_refresh_at' => now()->subHours(24),
        ]);

        $this->artisan('app:profile-updates')->assertSuccessful();

        Queue::assertPushed(UpdateProfileJob::class, function (UpdateProfileJob $job): bool {
            return $job->profile->username === 'amouranth' && $job->queue === ProfileQueue::Heavy->value;
        });
    }

    public function test_a_profile_already_queued_is_not_dispatched_again(): void
    {
        Queue::fake();

        $profile = Profile::factory()->create([
            'username' => 'madison420ivy',
            'likes' => 120_000,
            'last_successful_refresh_at' => now()->subDay(),
        ]);

        UpdateProfileJob::dispatch($profile);
        UpdateProfileJob::dispatch($profile);
        $this->artisan('app:profile-updates');

        Queue::assertPushed(UpdateProfileJob::class, 1);
    }

    public function test_a_profile_without_a_successful_refresh_is_queued_on_the_light_queue(): void
    {
        Queue::fake();

        Profile::factory()->create([
            'username' => 'new-account',
            'likes' => null,
            'last_successful_refresh_at' => null,
        ]);

        $this->artisan('app:profile-updates')->assertSuccessful();

        Queue::assertPushed(UpdateProfileJob::class, function (UpdateProfileJob $job): bool {
            return $job->profile->username === 'new-account' && $job->queue === ProfileQueue::Profiles->value;
        });
    }

    public function test_profile_updates_are_scheduled_hourly(): void
    {
        $event = collect(app(Schedule::class)->events())->first(
            fn ($event) => str_contains((string) $event->command, 'app:profile-updates'),
        );

        $this->assertNotNull($event);
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }
}
