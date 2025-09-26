<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\RewardCode;
use App\Models\User;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_can_scan_a_code_register_and_receive_welcome_gift_with_zero_points(): void
    {
        // ARRANGE
        // 1. Seed the database with ranks and products, including the Welcome Gift (ID 204)
        // and a standard scannable product (SKU PWT-SCAN-001, awards 400 points).
        $this->seed();

        // 2. Create a valid, unused RewardCode.
        $rewardCode = RewardCode::create([
            'code' => 'GOLDEN-PATH-123',
            'sku' => 'PWT-SCAN-001', // This SKU awards points on a NORMAL scan
            'is_used' => false,
        ]);

        // ACT - STEP 1: A new user "scans" the code via the unauthenticated endpoint.
        $unauthClaimResponse = $this->postJson('/api/rewards/v2/unauthenticated/claim', [
            'code' => $rewardCode->code,
        ]);

        // ASSERT - STEP 1
        $unauthClaimResponse->assertStatus(200);
        $unauthClaimResponse->assertJsonPath('data.status', 'registration_required');
        $registrationToken = $unauthClaimResponse->json('data.registration_token');

        // ACT - STEP 2: The user uses the token to complete registration.
        $newUserEmail = 'onboarding-user@example.com';
        $registerResponse = $this->postJson('/api/auth/register-with-token', [
            'email' => $newUserEmail,
            'password' => 'password123',
            'firstName' => 'Golden',
            'agreedToTerms' => true,
            'registration_token' => $registrationToken,
        ]);

        // ASSERT - STEP 2
        $registerResponse->assertStatus(200);
        $registerResponse->assertJsonStructure(['success', 'data' => ['token']]);

        // ASSERT - FINAL OUTCOME
        $this->assertDatabaseHas('users', ['email' => $newUserEmail]);
        $newUser = User::where('email', $newUserEmail)->first();

        // 1. Assert the Welcome Gift (ID 204) order was created.
        $this->assertDatabaseHas('orders', [
            'user_id' => $newUser->id,
            'product_id' => 204,
            'is_redemption' => true,
        ]);

        // 2. CRITICAL: Assert points balance is ZERO. The first scan awards the gift, not points.
        $newUser->refresh();
        $this->assertEquals(0, $newUser->meta['_canna_points_balance']);
        $this->assertEquals(0, $newUser->meta['_canna_lifetime_points']);

        // 3. Assert the reward code was consumed correctly.
        $this->assertDatabaseHas('reward_codes', [
            'code' => $rewardCode->code,
            'is_used' => true,
            'user_id' => $newUser->id,
        ]);
    }
}