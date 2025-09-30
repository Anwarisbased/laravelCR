<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Referral;
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
        $referrer = User::factory()->create();
        
        // Create the referral code for the referrer
        $referralCode = 'REFERRER123';
        $referrer->referral_code = $referralCode;
        $referrer->save();
        
        // Referee 1: Signed up but has not made a first scan (Pending)
        $refereePending = User::factory()->create();
        $pendingReferral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $refereePending->id,
            'referral_code' => $referralCode,
            'status' => 'signed_up', // Initially signed up, not converted
        ]);

        // Referee 2: Signed up AND made a first scan (Converted)
        $refereeConverted = User::factory()->create();
        $convertedReferral = Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $refereeConverted->id,
            'referral_code' => $referralCode,
            'status' => 'converted', // Already converted
            'converted_at' => now(),
        ]);

        // ACT
        $response = $this->actingAs($referrer, 'sanctum')->getJson('/api/rewards/v2/users/me/referrals');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        
        // This is the target state. The service needs to be implemented to make this pass.
        $response->assertJsonCount(2, 'data.referrals');
        $response->assertJsonFragment(['invitee_email' => $refereeConverted->email, 'status' => 'Converted']);
        $response->assertJsonFragment(['invitee_email' => $refereePending->email, 'status' => 'Pending']);
    }

    /**
     * Test the nudge endpoint. This test ensures the route is wired correctly.
     * NOTE: This test will fail until you implement the logic in `ReferralNudgeService::getNudgeOptions()`.
     */
    public function test_can_get_nudge_options_for_a_referee(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $user->referral_code = 'TESTCODE';
        $user->save();

        // ACT
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rewards/v2/users/me/referrals/nudge', [
            'email' => 'some.friend@example.com'
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        // The nudge service returns structured options based on business logic
        $response->assertJsonFragment([
            'can_nudge' => true,
            'message' => 'Invite some.friend@example.com to earn bonus points!',
            'referral_code' => 'TESTCODE',
        ]);
    }
}