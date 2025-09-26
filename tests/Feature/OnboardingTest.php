<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\RewardCode;
use App\Models\User;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the entire "scan-first" user onboarding flow, verifying
     * the original business logic where the first scan awards a
     * gift but zero points.
     *
     * @return void
     */
    public function test_new_user_can_scan_a_code_register_and_receive_welcome_gift_with_zero_points(): void
    {
        // ARRANGE
        // 1. Seed the database with our ranks and products. This is critical as it
        // creates the "Laravel Welcome Gift" (product ID 204) and the
        // "Standard Scannable Product" (SKU PWT-SCAN-001, awards 400 points).
        $this->seed();

        // 2. Create a valid, unused RewardCode in the database. This represents
        // the QR code the new user finds on a physical product.
        $rewardCode = RewardCode::create([
            'code' => 'GOLDEN-PATH-123',
            'sku' => 'PWT-SCAN-001', // This SKU is associated with the product that AWARDS 400 points
            'is_used' => false,
        ]);

        // 3. Set the Welcome Gift Product ID in Laravel's config.
        // This simulates the WordPress admin setting and tells the FirstScanBonusService
        // which product (ID 204) to award as the gift.
        config(['cannarewards.welcome_reward_product_id' => 204]);

        // ACT - STEP 1: A new user "scans" the code via the unauthenticated endpoint.
        $unauthClaimResponse = $this->postJson('/api/rewards/v2/unauthenticated/claim', [
            'code' => $rewardCode->code,
        ]);

        // ASSERT - STEP 1: Verify the unauthenticated claim was successful.
        $unauthClaimResponse->assertStatus(200);
        $unauthClaimResponse->assertJsonStructure([
            'success',
            'data' => ['status', 'registration_token', 'reward_preview']
        ]);
        $unauthClaimResponse->assertJsonPath('data.status', 'registration_required');
        $unauthClaimResponse->assertJsonPath('data.reward_preview.id', 204); // Assert it correctly previews the welcome gift
        $registrationToken = $unauthClaimResponse->json('data.registration_token');

        // ACT - STEP 2: The user uses the token from the first step to complete registration.
        $newUserEmail = 'onboarding-user@example.com';
        $registerResponse = $this->postJson('/api/auth/register-with-token', [
            'email' => $newUserEmail,
            'password' => 'password123',
            'firstName' => 'Golden',
            'agreedToTerms' => true,
            'registration_token' => $registrationToken,
        ]);

        // ASSERT - STEP 2: Verify registration was successful and returned a login token.
        $registerResponse->assertStatus(200);
        $registerResponse->assertJsonStructure(['success', 'data' => ['token']]);

        // ASSERT - FINAL OUTCOME: Verify all side-effects of the event-driven flow.

        // 1. A new user record must exist in the database.
        $this->assertDatabaseHas('users', [
            'email' => $newUserEmail,
        ]);
        $newUser = User::where('email', $newUserEmail)->first();

        // 2. An order for the WELCOME GIFT (product ID 204) must have been created for this new user.
        // This proves the `FirstScanBonusService` event listener worked correctly.
        $this->assertDatabaseHas('orders', [
            'user_id' => $newUser->id,
            'product_id' => 204, // The Welcome Gift Product ID from our seeder
            'is_redemption' => true,
        ]);

        // 3. The user's points balance must be ZERO.
        // This is the key assertion for Path A. It proves the `StandardScanService`
        // correctly identified this as a first scan and did NOT award points.
        $this->assertEquals(0, $newUser->meta['_canna_points_balance']);
        $this->assertEquals(0, $newUser->meta['_canna_lifetime_points']);

        // 4. The original reward code must now be marked as used and associated with the new user.
        $this->assertDatabaseHas('reward_codes', [
            'code' => $rewardCode->code,
            'is_used' => true,
            'user_id' => $newUser->id,
        ]);
    }
}