<?php

namespace Tests\Feature;

use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_index_shows_an_empty_state(): void
    {
        $response = $this->get(route('profiles.index'));

        $response->assertOk();
        $response->assertSee('No profiles yet');
    }

    public function test_profile_index_lists_the_stored_snapshot(): void
    {
        $profile = Profile::factory()->create([
            'username' => 'madison420ivy',
            'likes' => 120000,
            'revision' => 10,
        ]);

        $response = $this->get(route('profiles.index'));

        $response->assertOk();
        $response->assertSee('madison420ivy');
        $response->assertSee('120,000');
        $response->assertSee(route('profiles.show', $profile), false);
    }

    public function test_profile_show_returns_the_stored_snapshot(): void
    {
        $profile = Profile::factory()->create([
            'username' => 'madison420ivy',
            'likes' => 120000,
            'revision' => 10,
            'last_successful_refresh_at' => now()->subHour(),
            'last_attempt_at' => now(),
            'last_failure' => [
                'kind' => 'server_error',
                'status' => 500,
                'message' => 'Empty upstream response.',
            ],
        ]);

        $response = $this->get(route('profiles.show', $profile));

        $response->assertOk();
        $response->assertSee('madison420ivy');
        $response->assertSee('120,000');
        $response->assertSee('Failed');
        $response->assertSee('Empty upstream response.');
    }
}
