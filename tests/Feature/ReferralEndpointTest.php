<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReferralEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching referral data. This test defines the contract the service must fulfill.
     * NOTE: This test will fail until you implement the logic in `ReferralService::get_user_referrals()`.
     */
    public function test_can_get_my_referrals_data(): void
    {
        // ARRANGE
        $referrer = User::factory()->create(['meta' => ['_canna_referral_code' => 'REFERRER123']]);
        
        // Referee 1: Signed up but has not made a first scan (Pending)
        $refereePending = User::factory()->create(['meta' => ['_canna_referred_by_user_id' => $referrer->id]]);

        // Referee 2: Signed up AND made a first scan (Converted)
        $refereeConverted = User::factory()->create(['meta' => ['_canna_referred_by_user_id' => $referrer->id]]);
        DB::table('canna_user_action_log')->insert([
            'user_id' => $refereeConverted->id, 'action_type' => 'scan', 'created_at' => now()
        ]);

        // ACT
        $response = $this->actingAs($referrer, 'sanctum')->getJson('/api/rewards/v2/users/me/referrals');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        // This is the target state. The service needs to be implemented to make this pass.
        $response->assertJsonCount(2, 'data.referrals');
        $response->assertJsonFragment(['email' => $refereeConverted->email, 'status' => 'Converted']);
        $response->assertJsonFragment(['email' => $refereePending->email, 'status' => 'Pending']);
    }

    /**
     * Test the nudge endpoint. This test ensures the route is wired correctly.
     * NOTE: This test will fail until you implement the logic in `ReferralService::get_nudge_options_for_referee()`.
     */
    public function test_can_get_nudge_options_for_a_referee(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rewards/v2/users/me/referrals/nudge', [
            'email' => 'some.friend@example.com'
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        // Initially, the service returns an empty array, which is a valid response.
        $response->assertJsonPath('data', []);
    }
}