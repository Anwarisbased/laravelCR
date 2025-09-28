<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_has_correct_fillable_attributes(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->id);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->name);
    }

    public function test_user_model_has_hidden_attributes(): void
    {
        $user = User::factory()->create();

        $attributes = $user->toArray();

        $this->assertArrayNotHasKey('password', $attributes);
        $this->assertArrayNotHasKey('remember_token', $attributes);
    }

    public function test_user_model_has_casts(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'meta' => ['key' => 'value'],
            'is_admin' => true
        ]);

        $this->assertInstanceOf('DateTime', $user->email_verified_at);
        $this->assertIsArray($user->meta);
        $this->assertIsBool($user->is_admin);
    }

    public function test_user_has_referral_meta_functionality(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create([
            'meta' => ['_canna_referred_by_user_id' => $user1->id]
        ]);

        $this->assertNull($user1->meta['_canna_referred_by_user_id'] ?? null); // First user has no referrer
        $this->assertEquals($user1->id, $user2->meta['_canna_referred_by_user_id'] ?? null); // Second user refers to first
    }

    public function test_user_automatically_generates_referral_code(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        // The referral code should be generated via the boot method
        $user->refresh();
        
        $this->assertNotNull($user->meta['_canna_referral_code'] ?? null);
        // The referral code should be based on the user's name
        $this->assertStringContainsString('TEST', strtoupper($user->meta['_canna_referral_code']));
    }

    public function test_user_meta_stores_referral_code(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'meta' => ['_canna_referral_code' => 'JOHNDOE123']
        ]);

        $this->assertEquals('JOHNDOE123', $user->meta['_canna_referral_code']);
    }
}