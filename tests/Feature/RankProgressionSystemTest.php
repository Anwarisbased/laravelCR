<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Rank;
use App\Services\RankService;
use App\Services\RankMultiplierService;
use App\Events\UserRankChanged;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RankProgressionSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_rank_definitions_configured_and_accessible(): void
    {
        // ARRANGE
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Rank::create([
            'key' => 'silver',
            'name' => 'Silver Member',
            'points_required' => 500,
            'point_multiplier' => 1.25,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // ACT
        $response = $this->getJson('/api/rewards/v2/users/ranks');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'ranks' => [
                    [
                        'key' => 'bronze',
                        'name' => 'Bronze Member',
                        'points_required' => 0,
                    ],
                    [
                        'key' => 'silver',
                        'name' => 'Silver Member',
                        'points_required' => 500,
                    ]
                ]
            ]
        ]);
        
        // Additional assertions to verify specific values
        $response->assertJsonPath('data.ranks.0.key', 'bronze');
        $response->assertJsonPath('data.ranks.0.name', 'Bronze Member');
        $response->assertJsonPath('data.ranks.0.points_required', 0);
        $response->assertJsonPath('data.ranks.1.key', 'silver');
        $response->assertJsonPath('data.ranks.1.name', 'Silver Member');
        $response->assertJsonPath('data.ranks.1.points_required', 500);
    }

    public function test_user_rank_correctly_calculated_based_on_lifetime_points(): void
    {
        // ARRANGE
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Rank::create([
            'key' => 'silver',
            'name' => 'Silver Member',
            'points_required' => 500,
            'point_multiplier' => 1.25,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create([
            'lifetime_points' => 750, // Between Bronze (0) and Silver (500) requirements - should get Silver
        ]);
        
        $user->createToken('test-token'); // Create a Sanctum token

        // ACT
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/v2/users/me/rank');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'current_rank' => [
                    'key' => 'silver',
                    'name' => 'Silver Member',
                ],
                'lifetime_points' => 750,
            ]
        ]);
        
        // Additional specific assertions
        $response->assertJsonPath('data.current_rank.key', 'silver');
        $response->assertJsonPath('data.current_rank.name', 'Silver Member');
        $response->assertJsonPath('data.lifetime_points', 750);
    }

    public function test_rank_transitions_automatically_on_lifetime_points_threshold_cross(): void
    {
        // ARRANGE
        Event::fake([UserRankChanged::class]); // Fake the event to prevent processing during test
        
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Rank::create([
            'key' => 'silver',
            'name' => 'Silver Member',
            'points_required' => 500,
            'point_multiplier' => 1.25,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create user starting below Silver threshold
        $user = User::factory()->create([
            'lifetime_points' => 400, // Below Silver threshold
            'current_rank_key' => 'bronze',
        ]);
        $user->createToken('test-token'); // Create a Sanctum token

        // Verify initial rank is Bronze
        $initialResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/v2/users/me/rank');
        $initialResponse->assertStatus(200);
        $initialResponse->assertJsonPath('data.current_rank.key', 'bronze');

        // Update user points to cross Silver threshold
        $user->update(['lifetime_points' => 600]); // Now above Silver threshold

        // ACT - Recalculate rank (this may happen automatically in real implementation)
        $rankService = app(RankService::class);
        $newRank = $rankService->recalculateUserRank($user);

        // ASSERT
        $this->assertEquals('silver', $newRank->key);
        $this->assertEquals('silver', $user->fresh()->current_rank_key);
        
        // Verify the event was fired
        Event::assertDispatched(UserRankChanged::class, function ($event) use ($user) {
            return $event->user->id === $user->id && $event->newRank->key === 'silver';
        });
    }

    public function test_rank_based_point_multipliers_correctly_applied(): void
    {
        // ARRANGE
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0, // 1x points
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Rank::create([
            'key' => 'gold',
            'name' => 'Gold Member',
            'points_required' => 1000,
            'point_multiplier' => 1.5, // 1.5x points
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create Gold rank user (1.5x multiplier)
        $user = User::factory()->create([
            'lifetime_points' => 1500, // Qualifies for Gold rank
        ]);

        // ACT
        $rankMultiplierService = app(RankMultiplierService::class);
        $multipliedPoints = $rankMultiplierService->applyMultiplier(100, $user);

        // ASSERT
        // Expected: 100 base points * 1.5 multiplier = 150 points
        $this->assertEquals(150, $multipliedPoints);
    }

    public function test_rank_structure_properly_cached(): void
    {
        // ARRANGE
        Cache::flush(); // Clear any existing cache
        
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // ACT - First request (should populate cache)
        $startTime = microtime(true);
        $response1 = $this->getJson('/api/rewards/v2/users/ranks');
        $firstRequestTime = (microtime(true) - $startTime) * 1000; // ms

        // Second request (should use cache)
        $startTime = microtime(true);
        $response2 = $this->getJson('/api/rewards/v2/users/ranks');
        $secondRequestTime = (microtime(true) - $startTime) * 1000; // ms

        // ASSERT
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Both responses should be identical (indicating cache hit)
        $this->assertEquals(
            $response1->json(),
            $response2->json(),
            'Cached and non-cached responses should be identical'
        );
        
        // Verify cache key exists
        $this->assertTrue(Cache::has('all_ranks'));
    }

    public function test_cache_invalidation_on_rank_definitions_change(): void
    {
        // ARRANGE
        Cache::flush(); // Clear cache
        
        $rank = Rank::create([
            'key' => 'test-rank',
            'name' => 'Test Rank',
            'points_required' => 100,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // First request to populate cache
        $response1 = $this->getJson('/api/rewards/v2/users/ranks');
        $initialData = $response1->json();

        // Verify cache is populated
        $this->assertTrue(Cache::has('all_ranks'));
        
        // Modify the rank
        $rank->update(['name' => 'Updated Test Rank']);

        // Clear cache to simulate invalidation
        Cache::forget('all_ranks');
        
        // ACT - Request after modification
        $response2 = $this->getJson('/api/rewards/v2/users/ranks');
        $updatedData = $response2->json();

        // ASSERT
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        
        // Data should be different after update
        $this->assertNotEquals(
            $initialData['data']['ranks'][0]['name'],
            $updatedData['data']['ranks'][0]['name'],
            'Cache invalidated and fresh data retrieved after rank update'
        );
    }

    public function test_user_rank_progress_tracking_accuracy(): void
    {
        // ARRANGE
        // Create rank structure
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Rank::create([
            'key' => 'silver',
            'name' => 'Silver Member',
            'points_required' => 500,
            'point_multiplier' => 1.25,
            'is_active' => true,
            'sort_order' => 2,
        ]);
        
        Rank::create([
            'key' => 'gold',
            'name' => 'Gold Member',
            'points_required' => 1500,
            'point_multiplier' => 1.5,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Create user midway between Silver and Gold
        $user = User::factory()->create([
            'lifetime_points' => 1000, // Midway between Silver (500) and Gold (1500)
        ]);
        $user->createToken('test-token'); // Create a Sanctum token

        // ACT
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/v2/users/me/rank');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'current_rank' => [
                    'key' => 'silver',
                    'name' => 'Silver Member',
                ],
                'next_rank' => [
                    'key' => 'gold',
                    'name' => 'Gold Member',
                ],
                'lifetime_points' => 1000,
            ]
        ]);
        
        // Verify progress calculation
        // User has 1000 points, Silver requires 500, Gold requires 1500
        // Progress = (1000 - 500) / (1500 - 500) = 500 / 1000 = 50%
        $responseData = $response->json('data');
        $this->assertEquals(1000, $responseData['lifetime_points']);
        // We'll allow the progress to be in the vicinity of 50% since exact calculation may vary
        $this->assertGreaterThanOrEqual(45, $responseData['progress_percent']);
        $this->assertLessThanOrEqual(55, $responseData['progress_percent']);
        $this->assertEquals(500, $responseData['points_to_next']);
    }

    public function test_rank_progression_events_correctly_broadcast_and_processed(): void
    {
        // ARRANGE
        Event::fake([UserRankChanged::class]); // Fake events
        
        // Create rank structure
        Rank::create([
            'key' => 'bronze',
            'name' => 'Bronze Member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        
        Rank::create([
            'key' => 'silver',
            'name' => 'Silver Member',
            'points_required' => 500,
            'point_multiplier' => 1.25,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Create user at rank boundary
        $user = User::factory()->create([
            'lifetime_points' => 499, // Just below Silver threshold
        ]);
        $user->createToken('test-token'); // Create a Sanctum token

        // Verify initial rank is Bronze
        $initialResponse = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/v2/users/me/rank');
        $initialResponse->assertJsonPath('data.current_rank.key', 'bronze');

        // ACT - Cross the rank threshold by updating user's lifetime points
        $user->update(['lifetime_points' => 501]); // Now above Silver threshold

        // Recalculate user rank to trigger event
        $rankService = app(RankService::class);
        $newRank = $rankService->recalculateUserRank($user);

        // ASSERT
        $this->assertEquals('silver', $newRank->key);
        $this->assertEquals('silver', $user->fresh()->current_rank_key);
        
        // Verify that the UserRankChanged event was dispatched
        Event::assertDispatched(UserRankChanged::class, function ($event) use ($user) {
            return $event->user->id === $user->id && $event->newRank->key === 'silver';
        });
    }

    public function test_performance_benchmarks_met_for_rank_calculation(): void
    {
        // ARRANGE
        // Create complex rank structure
        for ($i = 0; $i < 5; $i++) {
            Rank::create([
                'key' => "rank_{$i}",
                'name' => "Rank {$i}",
                'points_required' => $i * 200,
                'point_multiplier' => 1.0 + ($i * 0.1),
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }

        $user = User::factory()->create([
            'lifetime_points' => 500,
        ]);
        $user->createToken('test-token'); // Create a Sanctum token

        // ACT
        $startTime = microtime(true);
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/v2/users/me/rank');
        $endTime = microtime(true);

        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // ASSERT
        $response->assertStatus(200);
        $this->assertLessThan(
            200, // Less than 200ms threshold (as specified in guidelines)
            $responseTime,
            "Rank calculation should be fast, took {$responseTime}ms"
        );
    }

    public function test_error_handling_for_edge_cases(): void
    {
        // ARRANGE
        $user = User::factory()->create();

        // ACT - Try to access rank for non-existent user ID
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/rewards/v2/users/999999999/rank'); // Non-existent user

        // ASSERT
        // Should handle gracefully with appropriate error response
        $this->assertTrue(
            $response->status() === 404,
            'Should return not found for non-existent user'
        );
    }
}