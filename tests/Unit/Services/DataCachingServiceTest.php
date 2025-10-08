<?php

namespace Tests\Unit\Services;

use App\Data\UserData;
use App\Models\User;
use App\Services\DataCachingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DataCachingServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataCachingService $dataCachingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataCachingService = new DataCachingService();
    }

    public function test_it_can_cache_and_retrieve_user_data(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create UserData instance
        $userData = UserData::fromModel($user);

        // Cache the UserData
        $this->dataCachingService->cacheUserData($user->id, $userData);

        // Retrieve from cache
        $cachedUserData = $this->dataCachingService->getCachedUserData($user->id);

        // Assert the cached data matches the original
        $this->assertNotNull($cachedUserData);
        $this->assertEquals($userData->id, $cachedUserData->id);
        $this->assertEquals($userData->name, $cachedUserData->name);
        $this->assertEquals($userData->email, $cachedUserData->email);
    }

    public function test_it_returns_null_when_user_data_not_cached(): void
    {
        $cachedUserData = $this->dataCachingService->getCachedUserData(999);

        $this->assertNull($cachedUserData);
    }

    public function test_it_can_clear_user_data_from_cache(): void
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create and cache UserData
        $userData = UserData::fromModel($user);
        $this->dataCachingService->cacheUserData($user->id, $userData);

        // Verify it's cached
        $cachedUserData = $this->dataCachingService->getCachedUserData($user->id);
        $this->assertNotNull($cachedUserData);

        // Clear the cache
        $this->dataCachingService->clearUserData($user->id);

        // Verify it's cleared
        $cachedUserDataAfterClear = $this->dataCachingService->getCachedUserData($user->id);
        $this->assertNull($cachedUserDataAfterClear);
    }

    public function test_cache_key_generation(): void
    {
        // This test verifies the internal cache key generation
        $reflection = new \ReflectionClass($this->dataCachingService);
        $method = $reflection->getMethod('generateCacheKey');
        $method->setAccessible(true);

        $key = $method->invoke($this->dataCachingService, UserData::class, 123);
        
        $this->assertStringContainsString('data_objects', $key);
        $this->assertStringContainsString('userdata', $key);
        $this->assertStringContainsString('123', $key);
    }
}