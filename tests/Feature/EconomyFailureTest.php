<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class EconomyFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_redeem_a_product_with_insufficient_points()
    {
        // ARRANGE
        // 1. Seed the DB with ranks and products.
        $this->seed();

        // 2. Create a user but override their points to be very low.
        $user = User::factory()->create();
        $user->meta = [
            '_canna_points_balance' => 100, // Not enough to afford the 5000 point product
            '_canna_lifetime_points' => 100
        ];
        $user->save();

        // ACT: Attempt to redeem the product (ID 1, costs 5000 points) while authenticated as this user.
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rewards/v2/actions/redeem', [
            'productId' => 1,
            'shippingDetails' => [ 'first_name' => 'Test', /* ... other details ... */ ],
        ]);

        // ASSERT
        // The exception handler in bootstrap/app.php will catch the Exception from the policy
        // and convert it into a 402 error with a JSON message.
        $response->assertStatus(402);
        $response->assertJson([
            'message' => 'Insufficient points.'
        ]);

        // Verify no order was created.
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
        ]);

        // Verify the user's points were not deducted.
        $user->refresh();
        $this->assertEquals(100, $user->meta['_canna_points_balance']);
    }
}