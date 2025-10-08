<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Repositories\UserRepository;
use App\Domain\ValueObjects\UserId;

class RedeemEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeem_endpoint_requires_authentication(): void
    {
        $response = $this->postJson('/api/rewards/v2/actions/redeem', [
            'productId' => 1,
            'shippingDetails' => ['first_name' => 'Test']
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_redeem_product_with_sufficient_points(): void
    {
        // ARRANGE
        // Create a specific product for this test with known cost
        $product = Product::create([
            'name' => 'Test Redemption Product',
            'sku' => 'TEST-REDEEM-001',
            'points_cost' => 5000,
            'is_active' => true,
            'status' => 'publish'
        ]);

        $user = User::factory()->create();

        // Set a known initial points value using the repository to ensure cache consistency
        $userRepository = $this->app->make(UserRepository::class);
        $userId = new UserId($user->id);
        $initialPoints = 10000; // Set a known value
        $userRepository->savePointsAndRank($userId, $initialPoints, $initialPoints, 'gold');
        
        // Refresh the user to get the updated points
        $user->refresh();
        
        $expectedFinalPoints = $initialPoints - $product->points_cost;

        // ACT
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rewards/v2/actions/redeem', [
            'productId' => $product->id, // Using the specific product we created
            'shippingDetails' => [
                'first_name' => 'John', 'last_name' => 'Doe', 'address1' => '123 Test St',
                'city' => 'Test City', 'state' => 'TS', 'postcode' => '12345'
            ]
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('new_points_balance', $expectedFinalPoints);

        // Use direct database check since Laravel's JSON syntax might be different
        $updatedUser = \App\Models\User::find($user->id);
        $this->assertEquals($expectedFinalPoints, $updatedUser->meta['_canna_points_balance'] ?? null);

        // Check that an order was created for the user
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
        ]);
        
        // Check that an order item was created with the correct product
        $order = \App\Models\Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);
    }
}