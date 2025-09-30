<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Models\ProductCategory;
use App\Repositories\ActionLogRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use App\Services\ConfigService;

class CatalogService
{
    protected $productEligibilityService;
    protected $cacheTtl;
    private ConfigService $configService;
    private ActionLogRepository $logRepo;

    public function __construct(
        ProductEligibilityService $productEligibilityService, 
        ConfigService $configService, 
        ActionLogRepository $logRepo
    ) {
        $this->productEligibilityService = $productEligibilityService;
        $this->configService = $configService;
        $this->logRepo = $logRepo;
        $this->cacheTtl = config('cache.catalog_ttl', 1800); // 30 minutes
    }
    
    public function getAllRewardProducts(?User $user = null): Collection
    {
        $cacheKey = $user ? "catalog_products_user_{$user->id}" : 'catalog_products_all';
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
            $products = Product::active()
                ->rewardable()
                ->with('category')
                ->orderBy('sort_order')
                ->get();
                
            if ($user) {
                $products = $products->map(function ($product) use ($user) {
                    $product->eligibility = $this->productEligibilityService->checkEligibility($user, $product);
                    return $product;
                });
            }
            
            return $products;
        });
    }
    
    public function getProductWithEligibility(int $productId, ?User $user = null): ?Product
    {
        $cacheKey = $user ? 
            "product_{$productId}_user_{$user->id}" : 
            "product_{$productId}_no_user";
            
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($productId, $user) {
            $product = Product::active()
                ->with('category')
                ->find($productId);
                
            if (!$product) {
                return null;
            }
            
            if ($user) {
                $product->eligibility = $this->productEligibilityService->checkEligibility($user, $product);
            }
            
            return $product;
        });
    }
    
    public function getFeaturedProducts(?User $user = null, int $limit = 12): Collection
    {
        $cacheKey = $user ? 
            "featured_products_user_{$user->id}_limit_{$limit}" : 
            "featured_products_no_user_limit_{$limit}";
            
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $limit) {
            $products = Product::active()
                ->rewardable()
                ->featured()
                ->with('category')
                ->limit($limit)
                ->get();
                
            if ($user) {
                $products = $products->map(function ($product) use ($user) {
                    $product->eligibility = $this->productEligibilityService->checkEligibility($user, $product);
                    return $product;
                });
            }
            
            return $products;
        });
    }
    
    public function getNewProducts(?User $user = null, int $limit = 12): Collection
    {
        $cacheKey = $user ? 
            "new_products_user_{$user->id}_limit_{$limit}" : 
            "new_products_no_user_limit_{$limit}";
            
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $limit) {
            $products = Product::active()
                ->rewardable()
                ->new()
                ->with('category')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
                
            if ($user) {
                $products = $products->map(function ($product) use ($user) {
                    $product->eligibility = $this->productEligibilityService->checkEligibility($user, $product);
                    return $product;
                });
            }
            
            return $products;
        });
    }
    
    public function getCategories(): Collection
    {
        return Cache::remember('product_categories', 3600, function () {
            return ProductCategory::active()
                ->root()
                ->with('children')
                ->orderBy('sort_order')
                ->get();
        });
    }
    
    public function clearCache(): void
    {
        // Clear all catalog-related cache
        Cache::forget('catalog_products_all');
        Cache::forget('product_categories');
        // Note: User-specific caches would need to be cleared individually
        // or use cache tags for easier management
    }

    /**
     * A helper function to consistently format product data for the API response.
     * This ensures the frontend receives data in the exact structure it expects.
     *
     * @param object $product The product object.
     * @return array The formatted product data.
     */
    public function format_product_for_api($product): array
    {
        // In a pure Laravel implementation, we'd have proper image handling
        $image_url = Storage::url('products/' . $product->id . '.jpg');
        if (!Storage::exists('products/' . $product->id . '.jpg')) {
            $image_url = '/images/placeholder.png'; // Using Laravel placeholder
        }

        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'description' => $product->description ?? '',
            'images'      => [
                ['src' => $image_url]
            ],
            'meta_data'   => [
                [
                    'key'   => 'points_cost',
                    'value' => $product->points_cost ?? 0,
                ],
                [
                    'key'   => '_required_rank',
                    'value' => $product->required_rank ?? '',
                ],
            ],
        ];
    }

    public function is_user_eligible_for_free_claim(int $product_id, int $user_id): bool
    {
        if ($user_id <= 0) {
            return false;
        }
        
        $welcome_reward_id = $this->configService->getWelcomeRewardProductId();
        $referral_gift_id = $this->configService->getReferralSignupGiftId();

        if ($product_id === $welcome_reward_id || $product_id === $referral_gift_id) {
            $scan_count = $this->logRepo->countUserActions($user_id, 'scan');
            return $scan_count <= 1;
        }
        
        return false;
    }
}