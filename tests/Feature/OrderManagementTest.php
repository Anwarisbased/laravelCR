<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Rank;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_redeem_reward_with_shipping_details()
    {
        $this->seed();

        // Create a user with sufficient points
        $user = User::factory()->create([
            'current_rank_key' => 'bronze',
        ]);
        $user->meta = [\App\Domain\MetaKeys::POINTS_BALANCE => 1000];
        $user->save();

        // Create a product to redeem
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'points_cost' => 100,
            'required_rank_key' => 'bronze'
        ]);

        // Shipping details for redemption
        $shippingDetails = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_1' => '123 Main St',
            'city' => 'Anytown',
            'state' => 'CA',
            'postcode' => '12345',
            'country' => 'US'
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/actions/redeem', [
                'product_id' => $product->id,
                'shipping_details' => $shippingDetails
            ]);

        $response->assertStatus(200);

        // Check that an order was created
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'points_cost' => $product->points_cost,
            'status' => 'processing',
            'is_canna_redemption' => true,
            'shipping_first_name' => $shippingDetails['first_name'],
            'shipping_last_name' => $shippingDetails['last_name'],
        ]);

        // Check that an order item was created
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'points_value' => $product->points_cost,
        ]);
    }

    public function test_user_can_get_their_order_history()
    {
        // Create a user
        $user = User::factory()->create();
        $user->meta = [\App\Domain\MetaKeys::POINTS_BALANCE => 1000];
        $user->save();

        // Create some orders for this user
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'is_canna_redemption' => true
        ]);

        // Create an order item for the order
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/me/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                        'status_display',
                        'points_cost',
                        'items',
                        'shipping_address',
                        'tracking_number',
                        'shipped_at',
                        'delivered_at',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next'
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'links',
                    'path',
                    'per_page',
                    'to',
                    'total'
                ]
            ]);
    }

    public function test_user_can_get_specific_order_details()
    {
        // Create a user
        $user = User::factory()->create();
        $user->meta = [\App\Domain\MetaKeys::POINTS_BALANCE => 1000];
        $user->save();

        // Create an order for this user
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'is_canna_redemption' => true
        ]);

        // Create an order item for the order
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => Product::factory()->create()->id
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'order_number',
                'status',
                'status_display',
                'points_cost',
                'items',
                'shipping_address',
                'tracking_number',
                'shipped_at',
                'delivered_at',
                'created_at',
                'updated_at',
            ]);
    }
}