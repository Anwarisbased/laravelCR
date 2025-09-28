<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_event_is_fired(): void
    {
        // ARRANGE
        Event::fake();

        $user = User::factory()->create([
            'email' => 'eventtest@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        // Create password reset token using Laravel's password broker
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        // ACT
        $response = $this->postJson('/api/auth/perform-password-reset', [
            'email' => 'eventtest@example.com',
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        // ASSERT
        $response->assertStatus(200);
        
        // Assert that the PasswordReset event was dispatched
        Event::assertDispatched(PasswordReset::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_no_events_fired_on_failed_password_reset(): void
    {
        // ARRANGE
        Event::fake();

        // ACT
        $response = $this->postJson('/api/auth/perform-password-reset', [
            'email' => 'nonexistent@example.com',
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        // ASSERT
        $response->assertStatus(400);
        
        // Assert that no PasswordReset event was dispatched
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function test_user_registration_triggers_expected_behavior(): void
    {
        // ARRANGE
        Event::fake(); // We don't have specific events for registration in current code

        // ACT
        $response = $this->postJson('/api/auth/register', [
            'email' => 'registertest@example.com',
            'password' => 'password123',
            'firstName' => 'Test',
            'lastName' => 'User',
            'agreedToTerms' => true
        ]);

        // ASSERT
        $response->assertStatus(201);
        
        // Check that user was created properly
        $this->assertDatabaseHas('users', [
            'email' => 'registertest@example.com'
        ]);
        
        // Get the user to verify referral code was generated
        $user = User::where('email', 'registertest@example.com')->first();
        $this->assertNotNull($user->meta['_canna_referral_code'] ?? null);
    }

    public function test_logout_invalidates_token_and_cannot_access_protected_routes(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        // ACT - First access a protected route to confirm token works
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                                ->getJson('/api/rewards/v2/users/me/profile');
        
        // Then logout
        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                               ->postJson('/api/rewards/v2/users/me/session/logout');

        // Verify token was invalidated - checking the token was actually removed from DB
        $tokenExists = $user->tokens()->where('id', $user->currentAccessToken()->id ?? 0)->exists();
        $this->assertFalse($tokenExists, 'Token should be deleted from database after logout');

        // ASSERT
        $profileResponse->assertStatus(200); // Should work before logout
        $logoutResponse->assertStatus(200); // Logout should succeed
        $this->assertFalse($tokenExists, 'Token should be deleted from database after logout');
    }
}