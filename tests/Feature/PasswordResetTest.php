<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_password_reset(): void
    {
        // ARRANGE
        // 1. Create a user.
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // ACT: Hit the request-password-reset endpoint.
        $response = $this->postJson('/api/auth/request-password-reset', [
            'email' => $user->email,
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);

        // Check that a token was created in the database
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        // ARRANGE
        // 1. Create a user.
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword123')
        ]);

        // ACT: Hit the perform-password-reset endpoint with an invalid token.
        $response = $this->postJson('/api/auth/perform-password-reset', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        // ASSERT
        // The response should indicate that the token is invalid
        $response->assertStatus(400);
        $response->assertJson([
            'message' => 'Your password reset token is invalid or has expired.'
        ]);
    }
}