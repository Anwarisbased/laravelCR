<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DataCachingService
{
    /**
     * Cache prefix for data objects
     */
    private const CACHE_PREFIX = 'data_objects';

    /**
     * Default TTL for cached data objects (in minutes)
     */
    private const DEFAULT_TTL = 60; // 1 hour

    /**
     * Generate a cache key for a data object
     */
    private function generateCacheKey(string $dataClass, mixed $identifier): string
    {
        $identifier = is_object($identifier) ? $identifier->id ?? $identifier : $identifier;
        return sprintf(
            '%s:%s:%s',
            self::CACHE_PREFIX,
            Str::lower(class_basename($dataClass)),
            is_array($identifier) ? md5(json_encode($identifier)) : $identifier
        );
    }

    /**
     * Get a cached data object
     */
    public function get(string $dataClass, mixed $identifier): mixed
    {
        $key = $this->generateCacheKey($dataClass, $identifier);
        return Cache::get($key);
    }

    /**
     * Cache a data object
     */
    public function put(string $dataClass, mixed $identifier, mixed $data, ?int $ttl = null): void
    {
        $key = $this->generateCacheKey($dataClass, $identifier);
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        Cache::put($key, $data, now()->addMinutes($ttl));
    }

    /**
     * Remove a cached data object
     */
    public function forget(string $dataClass, mixed $identifier): void
    {
        $key = $this->generateCacheKey($dataClass, $identifier);
        Cache::forget($key);
    }

    /**
     * Cache a UserData object
     */
    public function cacheUserData(int $userId, \App\Data\UserData $userData, ?int $ttl = null): void
    {
        $this->put(\App\Data\UserData::class, $userId, $userData, $ttl);
    }

    /**
     * Get a cached UserData object
     */
    public function getCachedUserData(int $userId): ?\App\Data\UserData
    {
        return $this->get(\App\Data\UserData::class, $userId);
    }

    /**
     * Cache a ProfileData object
     */
    public function cacheProfileData(int $userId, \App\Data\ProfileData $profileData, ?int $ttl = null): void
    {
        $this->put(\App\Data\ProfileData::class, $userId, $profileData, $ttl);
    }

    /**
     * Get a cached ProfileData object
     */
    public function getCachedProfileData(int $userId): ?\App\Data\ProfileData
    {
        return $this->get(\App\Data\ProfileData::class, $userId);
    }

    /**
     * Cache a ProductData collection
     */
    public function cacheProductDataCollection(array $productIds, array $productData, ?int $ttl = null): void
    {
        $this->put(\App\Data\Catalog\ProductData::class, $productIds, $productData, $ttl);
    }

    /**
     * Get a cached ProductData collection
     */
    public function getCachedProductDataCollection(array $productIds): ?array
    {
        return $this->get(\App\Data\Catalog\ProductData::class, $productIds);
    }

    /**
     * Cache a single ProductData object
     */
    public function cacheProductData(int $productId, \App\Data\Catalog\ProductData $productData, ?int $ttl = null): void
    {
        $this->put(\App\Data\Catalog\ProductData::class, $productId, $productData, $ttl);
    }

    /**
     * Get a cached ProductData object
     */
    public function getCachedProductData(int $productId): ?\App\Data\Catalog\ProductData
    {
        return $this->get(\App\Data\Catalog\ProductData::class, $productId);
    }

    /**
     * Cache an OrderData object
     */
    public function cacheOrderData(int $orderId, \App\Data\OrderData $orderData, ?int $ttl = null): void
    {
        $this->put(\App\Data\OrderData::class, $orderId, $orderData, $ttl);
    }

    /**
     * Get a cached OrderData object
     */
    public function getCachedOrderData(int $orderId): ?\App\Data\OrderData
    {
        return $this->get(\App\Data\OrderData::class, $orderId);
    }

    /**
     * Clear all cached data objects for a specific user
     */
    public function clearUserData(int $userId): void
    {
        $this->forget(\App\Data\UserData::class, $userId);
        $this->forget(\App\Data\ProfileData::class, $userId);
    }

    /**
     * Clear all cached product data
     */
    public function clearProductData(array $productIds = []): void
    {
        if (empty($productIds)) {
            // If no specific IDs provided, we'd need to use tags or another strategy
            // For now, this method can be used to clear specific products
            return;
        }

        foreach ($productIds as $productId) {
            $this->forget(\App\Data\Catalog\ProductData::class, $productId);
        }
    }

    /**
     * Flush all data object caches (use with caution)
     */
    public function flushAll(): void
    {
        // Note: Laravel's Cache::flush() will clear ALL cache entries
        // This is a destructive operation that should be used carefully
        Cache::flush();
    }
}