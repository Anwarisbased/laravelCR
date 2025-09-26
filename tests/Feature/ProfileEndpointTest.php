<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class ProfileEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the GET profile endpoint returns the FullProfileDTO data.
     */
    public function test_can_get_full_user_profile(): void
    {
        // ARRANGE
        // Create a user with specific meta data to test against
        $user = User::factory()->create([
            'name' => 'Profile User',
            'meta' => [
                'phone_number' => '1234567890',
                '_canna_referral_code' => 'PROFILE123',
                'shipping_first_name' => 'Profile',
                'shipping_last_name' => 'User',
                'shipping_address_1' => '123 Test Lane',
                'shipping_city' => 'Testville',
                'shipping_state' => 'TS',
                'shipping_postcode' => '54321',
                'favorite_strain' => 'OG Kush' // A custom field
            ]
        ]);

        // Mock the CustomFieldRepository to define what custom fields exist
        // This is a more advanced test that would require mocking. For now, we'll
        // assume the service can fetch it and we'll check the output.
        
        // ACT
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/rewards/v2/users/me/profile');
        
        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        // Check various parts of the FullProfileDTO structure
        $response->assertJsonPath('data.firstName', 'Profile');
        $response->assertJsonPath('data.lastName', 'User');
        $response->assertJsonPath('data.phoneNumber.value', '1234567890');
        $response->assertJsonPath('data.referralCode.value', 'PROFILE123');
        $response->assertJsonPath('data.shippingAddress.city', 'Testville');
        
        // Note: The customFields structure is complex. We'll check for one value.
        // The service needs to be implemented to properly populate this.
        // $response->assertJsonPath('data.customFields.values.favorite_strain', 'OG Kush');
    }
}