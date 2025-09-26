<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
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
        $this->seed();
        $user = User::factory()->create();

        // Update user points using the repository to ensure cache consistency
        $userRepository = $this->app->make(UserRepository::class);
        $userId = new UserId($user->id);
        $userRepository->savePointsAndRank($userId, 10000, 10000, 'gold');

        // ACT
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/rewards/v2/actions/redeem', [
            'productId' => 1, // This product costs 5000 points, per our seeder.
            'shippingDetails' => [
                'first_name' => 'John', 'last_name' => 'Doe', 'address_1' => '123 Test St',
                'city' => 'Test City', 'state' => 'TS', 'postcode' => '12345'
            ]
        ]);

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonPath('data.new_points_balance', 5000); // 10000 - 5000 = 5000

        // Use direct database check since Laravel's JSON syntax might be different
        $updatedUser = \App\Models\User::find($user->id);
        $this->assertEquals(5000, $updatedUser->meta['_canna_points_balance'] ?? null);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'product_id' => 1,
        ]);
    }
}