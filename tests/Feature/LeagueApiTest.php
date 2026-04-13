<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LeagueApiTest extends TestCase
{
    private string $statePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->statePath = storage_path('framework/testing/league-state.json');
        config(['league.state_path' => $this->statePath]);
        File::ensureDirectoryExists(dirname($this->statePath));
        File::delete($this->statePath);
    }

    public function test_default_teams_can_be_seeded_and_listed(): void
    {
        $this->postJson('/api/teams/defaults')
            ->assertOk()
            ->assertJson([
                'addedCount' => 4,
            ]);

        $this->getJson('/api/teams')
            ->assertOk()
            ->assertJsonCount(4);
    }

    public function test_fixtures_can_be_generated_and_week_can_be_played(): void
    {
        $this->postJson('/api/teams/defaults')->assertOk();

        $this->postJson('/api/fixtures')
            ->assertOk()
            ->assertJsonCount(6);

        $response = $this->putJson('/api/fixtures/play');

        $response->assertOk()
            ->assertJsonPath('week', 0)
            ->assertJsonPath('totalWeek', 6);

        $this->getJson('/api/fixtures/standings')
            ->assertOk()
            ->assertJsonCount(4);
    }
}
