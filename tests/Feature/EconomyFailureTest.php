<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;

class EconomyFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_redeem_a_product_with_insufficient_points()
    {
        // ARRANGE
        // Create a specific product for this test with known cost
        $product = Product::create([
            'name' => 'Test Product for Failure',
            'sku' => 'TEST-FAIL-001',
            'points_cost' => 5000,  // High cost to ensure failure
            'is_active' => true,
            'status' => 'publish'
        ]);

        // Create a user with low points
        $user = User::factory()->create();
        $user->meta = [
            '_canna_points_balance' => 100, // Not enough to afford the 5000 point product
            '_canna_lifetime_points' => 100
        ];
        $user->save();

        // Get initial points for verification later
        $initialPoints = $user->meta['_canna_points_balance'];

        // ACT: Attempt to redeem the product while authenticated as this user.
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rewards/v2/actions/redeem', [
            'productId' => $product->id,
            'shippingDetails' => [
                'first_name' => 'Test',
                'last_name' => 'User',
                'address1' => '123 Main St',
                'city' => 'Anytown',
                'state' => 'CA',
                'postcode' => '12345'
            ],
        ]);

        // ASSERT
        // The response should indicate failure due to insufficient points
        $status = $response->status();
        $this->assertTrue(
            $status === 402 || $status === 422,
            "Expected status 402 (Payment Required) or 422 (Unprocessable Entity) for insufficient points, but got: $status"
        );
        
        // Verify the error message mentions insufficient points
        $responseData = $response->json();
        $message = $responseData['message'] ?? '';
        $this->assertStringContainsString('insufficient', strtolower($message), 'Error message should mention insufficient points');

        // Verify no order was created.
        $this->assertDatabaseMissing('orders', [
            'user_id' => $user->id,
        ]);

        // Verify the user's points were not deducted.
        $user->refresh();
        $this->assertEquals($initialPoints, $user->meta['_canna_points_balance']);
    }
}