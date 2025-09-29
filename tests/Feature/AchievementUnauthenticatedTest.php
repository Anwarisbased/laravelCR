<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AchievementUnauthenticatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_achievement_endpoints()
    {
        $response = $this->getJson('/api/rewards/v2/users/me/achievements');
        $response->assertStatus(401);

        $response = $this->getJson('/api/rewards/v2/users/me/achievements/locked');
        $response->assertStatus(401);

        $response = $this->getJson('/api/rewards/v2/users/me/achievements/progress');
        $response->assertStatus(401);
    }
}