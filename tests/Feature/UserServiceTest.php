<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = app(UserService::class);
    }

    public function test_get_user_session_data_returns_correct_dto(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email' => 'session@example.com',
            'name' => 'Test User',
            'meta' => [
                'first_name' => 'Test',
                'last_name' => 'User'
            ]
        ]);

        // ACT
        $sessionData = $this->userService->get_user_session_data(\App\Domain\ValueObjects\UserId::fromInt($user->id));

        // ASSERT
        $this->assertEquals($user->id, $sessionData->id);
        $this->assertEquals('Test', $sessionData->firstName);
        $this->assertEquals('User', $sessionData->lastName);
        $this->assertEquals('session@example.com', (string)$sessionData->email);
        $this->assertNotNull($sessionData->pointsBalance);
        $this->assertNotNull($sessionData->rank);
    }

    public function test_get_full_profile_data_returns_correct_dto(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email' => 'profile@example.com',
            'name' => 'Test User',
            'meta' => [
                'first_name' => 'Test',
                'last_name' => 'User',
                'phone_number' => '+15551234567',  // Valid 11-digit phone number
                '_canna_referral_code' => 'TEST123'
            ]
        ]);

        // ACT
        $profileData = $this->userService->get_full_profile_data(\App\Domain\ValueObjects\UserId::fromInt($user->id));

        // ASSERT
        $this->assertEquals('Test', $profileData->firstName);
        $this->assertEquals('User', $profileData->lastName);
        $this->assertNotNull($profileData->phoneNumber);
        $this->assertNotNull($profileData->referralCode);
        $this->assertNotNull($profileData->shippingAddress);
    }

    public function test_request_password_reset_creates_token(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        // ACT
        $this->userService->request_password_reset(\App\Domain\ValueObjects\EmailAddress::fromString('reset@example.com'));

        // ASSERT
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'reset@example.com'
        ]);
    }

    public function test_perform_password_reset_updates_password(): void
    {
        // ARRANGE
        Event::fake();
        $user = User::factory()->create([
            'email' => 'reset2@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        // Create a password reset token
        $token = Password::createToken($user);

        // ACT
        $this->userService->perform_password_reset($token, \App\Domain\ValueObjects\EmailAddress::fromString('reset2@example.com'), \App\Domain\ValueObjects\PlainTextPassword::fromString('newpassword123'));

        // ASSERT
        // Refresh the user from the database
        $user->refresh();
        
        // Check that the password has been updated
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        
        // Check that the token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'reset2@example.com'
        ]);
    }

    public function test_perform_password_reset_fails_with_invalid_token(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email' => 'invalid@example.com',
            'password' => Hash::make('oldpassword')
        ]);

        // ACT & ASSERT
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->userService->perform_password_reset('invalid-token', \App\Domain\ValueObjects\EmailAddress::fromString('invalid@example.com'), \App\Domain\ValueObjects\PlainTextPassword::fromString('newpassword123'));
    }

    public function test_login_returns_correct_token_and_user_data(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'name' => 'Login User',
            'password' => Hash::make('password123'),
            'meta' => [
                'first_name' => 'Login',
                'last_name' => 'User'
            ]
        ]);

        // ACT
        $result = $this->userService->login(\App\Domain\ValueObjects\EmailAddress::fromString('login@example.com'), \App\Domain\ValueObjects\PlainTextPassword::fromString('password123'));

        // ASSERT
        $this->assertNotNull($result);
        $this->assertIsString($result->token);
        $this->assertEquals('login@example.com', $result->user_email);
        $this->assertEquals('Login', $result->user_nicename);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        // ARRANGE
        $user = User::factory()->create([
            'email' => 'loginfail@example.com',
            'password' => Hash::make('correctpassword')
        ]);

        // ACT & ASSERT
        $this->expectException(\Exception::class);
        $this->userService->login(\App\Domain\ValueObjects\EmailAddress::fromString('loginfail@example.com'), \App\Domain\ValueObjects\PlainTextPassword::fromString('wrongpassword'));
    }
}