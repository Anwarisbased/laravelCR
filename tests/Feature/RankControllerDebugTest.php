<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Rank;
use Illuminate\Support\Facades\Auth;

class RankControllerDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_my_rank_method_directly(): void
    {
        // ARRANGE
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        $user = User::factory()->create([
            'lifetime_points' => 750,
        ]);
        
        // ACT
        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->getJson('/api/rewards/v2/users/me/rank');

        // DEBUG: Output the response
        \Log::info('getMyRank response', ['status' => $response->getStatusCode(), 'content' => $response->getContent()]);
        $response->dump();
    }
}