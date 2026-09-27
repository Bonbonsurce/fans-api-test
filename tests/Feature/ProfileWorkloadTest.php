<?php

namespace Tests\Feature;

use App\Models\Profile;
use Database\Seeders\ProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Src\Application\Enums\ProfileQueue;
use Src\Application\Services\Workload\WorkloadScenario;
use Src\Infrastructure\Integrations\OnlyFans\ScriptedOnlyFansClient;
use Src\Infrastructure\Integrations\OnlyFans\WorkloadScript;
use Src\Infrastructure\Integrations\ValueObjects\OnlyFans\OnlyFansProfileResponse;
use Tests\TestCase;

class ProfileWorkloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_seeder_upserts_without_duplicates(): void
    {
        $this->seed(ProfileSeeder::class);
        $this->seed(ProfileSeeder::class);

        $this->assertSame(3, Profile::query()->count());
        $this->assertDatabaseHas('profiles', [
            'username' => 'ladydisdainvip',
            'likes' => 2998,
        ]);
        $this->assertDatabaseHas('profiles', [
            'username' => 'madison420ivy',
            'likes' => 120000,
        ]);
        $this->assertDatabaseHas('profiles', [
            'username' => 'amouranth',
            'likes' => 1821184,
        ]);
    }

    public function test_workload_prepares_five_light_and_two_heavy_profiles(): void
    {
        Queue::fake();

        $profiles = app(WorkloadScenario::class)->prepare();

        $this->assertCount(7, $profiles);
        $this->assertCount(5, $profiles->filter(fn (Profile $profile) => $profile->queue() === ProfileQueue::Profiles));
        $this->assertCount(2, $profiles->filter(fn (Profile $profile) => $profile->queue() === ProfileQueue::Heavy));
    }

    public function test_scripted_client_serves_workload_responses(): void
    {
        $scripts = app(WorkloadScript::class);
        $scripts->put('ladydisdainvip', [
            OnlyFansProfileResponse::ok(['likes' => 3000, 'revision' => 5]),
        ]);

        $client = new ScriptedOnlyFansClient($scripts);
        $response = $client->fetchProfile('ladydisdainvip');

        $this->assertTrue($response->isOk());
        $this->assertSame(3000, $response->payload['likes']);
        $this->assertNull($scripts->pull('ladydisdainvip'));
    }

    public function test_workload_command_is_registered(): void
    {
        $this->artisan('app:profile-workload --help')->assertSuccessful();
    }
}
