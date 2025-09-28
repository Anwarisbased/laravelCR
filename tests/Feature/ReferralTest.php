<?php

namespace Tests\Feature;

use App\Models\Referral;
use App\Models\User;
use App\Notifications\ReferralBonusAwardedNotification;
use App\Services\AppReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_referral_code(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->referral_code);
        $this->assertIsString($user->referral_code);
        $this->assertGreaterThan(0, strlen($user->referral_code)); // Check it's not empty
    }

    public function test_user_can_get_referrals_list(): void
    {
        $referrer = User::factory()->create();
        $invitee = User::factory()->create();

        Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'converted',
            'converted_at' => now(),
            'bonus_points_awarded' => 500,
        ]);

        $response = $this->actingAs($referrer, 'sanctum')
            ->getJson('/rewards/v2/users/me/referrals');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'referrals' => [
                        [
                            'invitee_email' => $invitee->email,
                            'status' => 'converted',
                            'bonus_points_awarded' => 500,
                        ]
                    ]
                ]
            ]);
    }

    public function test_referral_processing_works(): void
    {
        Notification::fake();

        $referrer = User::factory()->create();
        $invitee = User::factory()->create();

        // Test referral processing
        $referralService = app(\App\Services\ReferralService::class);
        $result = $referralService->processSignUp($invitee, $referrer->referral_code);

        $this->assertTrue($result);

        // Check referral record was created
        $this->assertDatabaseHas('referrals', [
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'signed_up',
        ]);

        // Test conversion process
        $referralService->handle_referral_conversion($invitee);

        // Check referral was converted
        $this->assertDatabaseHas('referrals', [
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee->id,
            'status' => 'converted',
        ]);
        
        // Check that notifications were sent
        Notification::assertSentTo($referrer, ReferralBonusAwardedNotification::class);
    }

    public function test_invalid_referral_code_returns_false(): void
    {
        $user = User::factory()->create();
        $referralService = app(\App\Services\ReferralService::class);

        $result = $referralService->processSignUp($user, 'INVALID_CODE');

        $this->assertFalse($result);
    }

    public function test_get_nudge_options(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/rewards/v2/users/me/referrals/nudge', [
                'email' => 'newuser@example.com'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'can_nudge' => true,
                    'referral_code' => $user->referral_code,
                ]
            ]);
    }

    public function test_get_referral_stats(): void
    {
        $referrer = User::factory()->create();
        $invitee1 = User::factory()->create();
        $invitee2 = User::factory()->create();

        // Create referral that is converted
        Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee1->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'converted',
        ]);

        // Create referral that is still pending
        Referral::create([
            'referrer_user_id' => $referrer->id,
            'invitee_user_id' => $invitee2->id,
            'referral_code' => $referrer->referral_code,
            'status' => 'signed_up',
        ]);

        $referralService = app(\App\Services\ReferralService::class);
        $stats = $referralService->get_referral_stats($referrer->id);

        $this->assertEquals(2, $stats['total_referrals']);
        $this->assertEquals(1, $stats['converted_referrals']);
        $this->assertEquals(50, $stats['conversion_rate']);
    }

    public function test_process_referral_endpoint(): void
    {
        $referrer = User::factory()->create();
        $invitee = User::factory()->create();

        $response = $this->actingAs($invitee, 'sanctum')
            ->postJson('/rewards/v2/users/me/referrals/process', [
                'referral_code' => $referrer->referral_code
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Referral processed successfully'
            ]);
    }
}
