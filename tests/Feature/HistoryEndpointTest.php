<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HistoryEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the history endpoint requires authentication.
     */
    public function test_history_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/rewards/v2/users/me/history');
        $response->assertStatus(401); // Unauthenticated
    }

    /**
     * Test that the endpoint returns a correctly formatted history for a user.
     */
    public function test_returns_correct_history_for_authenticated_user(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create some log entries for the authenticated user
        DB::table('canna_user_action_log')->insert([
            ['user_id' => $user->id, 'action_type' => 'points_granted', 'meta_data' => json_encode(['points_change' => 100, 'description' => 'Daily Login']), 'created_at' => now()->subDay()],
            ['user_id' => $user->id, 'action_type' => 'redeem', 'meta_data' => json_encode(['points_change' => -500, 'description' => 'Redeemed: Cool T-Shirt']), 'created_at' => now()],
            ['user_id' => $user->id, 'action_type' => 'scan', 'meta_data' => json_encode([]), 'created_at' => now()], // Should be ignored
        ]);

        // Create a log entry for another user to ensure it's not included
        DB::table('canna_user_action_log')->insert([
            'user_id' => $otherUser->id, 'action_type' => 'points_granted', 'meta_data' => json_encode(['points_change' => 50, 'description' => 'Other User Log']), 'created_at' => now()
        ]);

        // ACT
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/rewards/v2/users/me/history');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        // Should only return 'points_granted' and 'redeem' actions, and only for the logged-in user.
        $response->assertJsonCount(2, 'data.history'); 
        
        // Asserts the most recent item is first and has the correct data
        $response->assertJsonPath('data.history.0.description', 'Redeemed: Cool T-Shirt');
        $response->assertJsonPath('data.history.0.points', -500);

        $response->assertJsonPath('data.history.1.description', 'Daily Login');
        $response->assertJsonPath('data.history.1.points', 100);
    }

    /**
     * Test that the endpoint returns an empty array for a user with no history.
     */
    public function test_returns_empty_array_for_user_with_no_history(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/rewards/v2/users/me/history');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonCount(0, 'data.history');
    }
}