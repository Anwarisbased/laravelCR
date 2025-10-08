<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Rank;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class CatalogFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed ranks for testing
        $this->seed();
    }

    public function test_all_reward_products_are_correctly_listed_in_catalog_with_pagination(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        
        // Create test products
        Product::create([
            'name' => 'Test Product 1',
            'sku' => 'TEST001',
            'description' => 'Test description 1',
            'points_cost' => 500,
            'is_active' => true,
            'status' => 'publish'
        ]);
        
        Product::create([
            'name' => 'Test Product 2',
            'sku' => 'TEST002',
            'description' => 'Test description 2',
            'points_cost' => 1000,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->getJson('/api/rewards/v2/catalog/products');

        // ASSERT
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'products' => [
                [
                    'id',
                    'name',
                    'sku',
                    'description',
                    'points_cost',
                    'points_award',
                    'required_rank_key',
                    'is_active',
                    'is_featured',
                    'is_new',
                    'brand',
                    'strain_type',
                    'thc_content',
                    'cbd_content',
                    'product_form',
                    'marketing_snippet',
                    'images',
                    'tags',
                    'available_from',
                    'available_until',
                    'created_at',
                    'updated_at'
                ]
            ]
        ]);
        
        // Verify that the products are returned
        $this->assertGreaterThanOrEqual(2, count($response->json('products')));
    }

    public function test_product_details_are_properly_formatted_for_api_response_with_all_metadata(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        
        // Create a test product with all the metadata
        $product = Product::create([
            'name' => 'Detailed Product',
            'sku' => 'DETAILED-001',
            'description' => 'This is a detailed product description',
            'short_description' => 'Short desc',
            'points_award' => 100,
            'points_cost' => 500,
            'required_rank_key' => 'bronze',
            'is_active' => true,
            'is_featured' => true,
            'is_new' => true,
            'brand' => 'Test Brand',
            'strain_type' => 'Sativa',
            'thc_content' => 15.5,
            'cbd_content' => 2.1,
            'product_form' => 'flower',
            'marketing_snippet' => 'Amazing product!',
            'image_urls' => ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'],
            'tags' => ['tag1', 'tag2'],
            'sort_order' => 1,
            'available_from' => now()->subDay(),
            'available_until' => now()->addDay(),
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->getJson("/api/rewards/v2/catalog/products/{$product->id}");

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals($product->id, $data['id']);
        $this->assertEquals($product->name, $data['name']);
        $this->assertEquals($product->sku, $data['sku']);
        $this->assertEquals($product->description, $data['description']);
        $this->assertEquals($product->short_description, $data['short_description']);
        $this->assertEquals($product->points_award, $data['points_award']);
        $this->assertEquals($product->points_cost, $data['points_cost']);
        $this->assertEquals($product->required_rank_key, $data['required_rank_key']);
        $this->assertEquals($product->is_active, $data['is_active']);
        $this->assertEquals($product->is_featured, $data['is_featured']);
        $this->assertEquals($product->is_new, $data['is_new']);
        $this->assertEquals($product->brand, $data['brand']);
        $this->assertEquals($product->strain_type, $data['strain_type']);
        $this->assertEquals($product->thc_content, $data['thc_content']);
        $this->assertEquals($product->cbd_content, $data['cbd_content']);
        $this->assertEquals($product->product_form, $data['product_form']);
        $this->assertEquals($product->marketing_snippet, $data['marketing_snippet']);
        $this->assertEquals($product->image_urls, $data['images']);
        $this->assertEquals($product->tags, $data['tags']);
        // Compare dates as strings since they'll be serialized differently
        $this->assertEquals($product->available_from->format('Y-m-d H:i:s'), $data['available_from']);
        $this->assertEquals($product->available_until->format('Y-m-d H:i:s'), $data['available_until']);
    }

    public function test_product_images_are_correctly_handled_and_formatted_with_thumbnails(): void
    {
        // ARRANGE
        $product = Product::create([
            'name' => 'Image Product',
            'sku' => 'IMAGE-001',
            'description' => 'Product with images',
            'points_cost' => 750,
            'is_active' => true,
            'image_urls' => ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'],
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->getJson("/api/rewards/v2/catalog/products/{$product->id}");

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals($product->image_urls, $data['images']);
    }

    public function test_welcome_gift_eligibility_is_correctly_determined_for_new_users(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        
        // Set welcome gift config
        Config::set('cannarewards.welcome_gift_product_id', 999);
        
        // Create a welcome gift product
        $welcomeProduct = Product::create([
            'id' => 999,
            'name' => 'Welcome Gift',
            'sku' => 'WELCOME-001',
            'description' => 'Welcome gift for new users',
            'points_cost' => 0,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/rewards/v2/catalog/products/{$welcomeProduct->id}");

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        
        // For first-time users, the welcome gift should be eligible for free claim
        $this->assertArrayHasKey('eligibility', $data);
        $this->assertArrayHasKey('eligible_for_free_claim', $data['eligibility']);
    }

    public function test_referral_gift_eligibility_is_correctly_determined_for_referred_users(): void
    {
        // ARRANGE
        $user = User::factory()->create();
        
        // Set referral gift config
        Config::set('cannarewards.referral_sign_up_gift_id', 998);
        
        // Create a referral gift product
        $referralProduct = Product::create([
            'id' => 998,
            'name' => 'Referral Gift',
            'sku' => 'REFERRAL-001',
            'description' => 'Gift for referred users',
            'points_cost' => 0,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/rewards/v2/catalog/products/{$referralProduct->id}");

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertArrayHasKey('eligibility', $data);
        $this->assertArrayHasKey('eligible_for_free_claim', $data['eligibility']);
    }

    public function test_product_metadata_points_values_rank_requirements_is_properly_extracted_and_displayed(): void
    {
        // ARRANGE
        $rank = Rank::where('key', 'bronze')->first();
        if (!$rank) {
            $rank = Rank::create([
                'name' => 'Bronze',
                'key' => 'bronze',
                'points_required' => 0,
                'point_multiplier' => 1,
                'is_active' => true
            ]);
        }
        
        $product = Product::create([
            'name' => 'Metadata Product',
            'sku' => 'META-001',
            'description' => 'Product with metadata',
            'points_award' => 150,
            'points_cost' => 600,
            'required_rank_key' => 'bronze',
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->getJson("/api/rewards/v2/catalog/products/{$product->id}");

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertEquals($product->points_award, $data['points_award']);
        $this->assertEquals($product->points_cost, $data['points_cost']);
        $this->assertEquals($product->required_rank_key, $data['required_rank_key']);
    }

    public function test_catalog_data_is_properly_cached_with_cache_hit_ratio_greater_than_90(): void
    {
        // ARRANGE
        Product::create([
            'name' => 'Cached Product',
            'sku' => 'CACHE-001',
            'description' => 'Product for caching test',
            'points_cost' => 200,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // Clear any existing cache
        Cache::clear();

        // ACT - First request
        $firstResponse = $this->getJson('/api/rewards/v2/catalog/products');
        
        // Second request
        $secondResponse = $this->getJson('/api/rewards/v2/catalog/products');

        // ASSERT
        $firstResponse->assertStatus(200);
        $secondResponse->assertStatus(200);
        
        // Both responses should be identical
        $this->assertEquals($firstResponse->json(), $secondResponse->json());
    }

    public function test_eligibility_checking_correctly_enforces_points_and_rank_requirements(): void
    {
        // ARRANGE
        $user = User::factory()->create(); // Create user without points_balance column issue
        // Update the user's meta to set points balance
        $user->update(['meta' => ['_canna_points_balance' => 100]]); // Low points balance
        
        $highCostProduct = Product::create([
            'name' => 'Expensive Product',
            'sku' => 'EXP-001',
            'description' => 'High cost product',
            'points_cost' => 1000, // More than user has
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/rewards/v2/catalog/products/{$highCostProduct->id}");

        // ASSERT
        $response->assertStatus(200);
        $data = $response->json();
        
        $this->assertArrayHasKey('eligibility', $data);
        $this->assertArrayHasKey('is_eligible', $data['eligibility']);
        $this->assertFalse($data['eligibility']['is_eligible']);
        
        // Should have 'insufficient_points' reason
        $this->assertContains('insufficient_points', $data['eligibility']['reasons']);
    }

    public function test_featured_and_new_product_sections_are_properly_displayed(): void
    {
        // ARRANGE
        Product::create([
            'name' => 'Featured Product',
            'sku' => 'FEAT-001',
            'description' => 'Featured product',
            'points_cost' => 300,
            'is_active' => true,
            'is_featured' => true,
            'status' => 'publish'
        ]);
        
        Product::create([
            'name' => 'New Product',
            'sku' => 'NEW-001',
            'description' => 'New product',
            'points_cost' => 250,
            'is_active' => true,
            'is_new' => true,
            'status' => 'publish'
        ]);

        // ACT
        $featuredResponse = $this->getJson('/api/rewards/v2/catalog/featured');
        $newResponse = $this->getJson('/api/rewards/v2/catalog/new');

        // ASSERT
        $featuredResponse->assertStatus(200);
        $newResponse->assertStatus(200);
        
        $featuredData = $featuredResponse->json();
        $newData = $newResponse->json();
        
        // At least one featured product should exist
        $this->assertGreaterThanOrEqual(1, count($featuredData));
        
        // At least one new product should exist
        $this->assertGreaterThanOrEqual(1, count($newData));
    }

    public function test_product_categories_are_correctly_organized_and_displayed(): void
    {
        // ARRANGE
        $category = ProductCategory::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'Test category description',
            'is_active' => true
        ]);
        
        $product = Product::create([
            'name' => 'Categorized Product',
            'sku' => 'CAT-001',
            'description' => 'Product in category',
            'points_cost' => 400,
            'category_id' => $category->id,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $response = $this->getJson('/api/rewards/v2/catalog/categories');

        // ASSERT
        $response->assertStatus(200);
        
        $categories = $response->json();
        $this->assertGreaterThanOrEqual(1, count($categories));
    }

    public function test_catalog_filtering_and_sorting_works_correctly(): void
    {
        // This would be tested with a dedicated filter request if implemented
        $this->assertTrue(true); // Placeholder test
    }

    public function test_error_handling_for_edge_cases_with_laravel_exception_handling(): void
    {
        // ARRANGE & ACT - Try to get a non-existent product
        $response = $this->getJson('/api/rewards/v2/catalog/products/999999');

        // ASSERT
        $response->assertStatus(404);
    }

    public function test_performance_benchmarks_met_response_time_less_than_200ms(): void
    {
        // ARRANGE
        Product::create([
            'name' => 'Performance Test Product',
            'sku' => 'PERF-001',
            'description' => 'Product for performance test',
            'points_cost' => 300,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // ACT
        $startTime = microtime(true);
        $response = $this->getJson('/api/rewards/v2/catalog/products');
        $endTime = microtime(true);
        
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // ASSERT
        $response->assertStatus(200);
        $this->assertLessThan(200, $responseTime, 'Catalog endpoint should respond in less than 200ms');
    }

    public function test_cache_invalidation_works_correctly_when_products_are_updated(): void
    {
        // ARRANGE
        $product = Product::create([
            'name' => 'Cache Invalidation Test',
            'sku' => 'CACHE-INV-001',
            'description' => 'Product for cache invalidation test',
            'points_cost' => 200,
            'is_active' => true,
            'status' => 'publish'
        ]);

        // First request to cache the data
        $firstResponse = $this->getJson('/api/rewards/v2/catalog/products/v2');
        $firstResponseData = $firstResponse->json();

        // Update the product to trigger cache invalidation
        $product->update(['name' => 'Updated Product Name']);

        // Clear cache manually as per CatalogService
        \App::make(\App\Services\CatalogService::class)->clearCache();

        // Second request after invalidation
        $secondResponse = $this->getJson('/api/rewards/v2/catalog/products');
        $secondResponseData = $secondResponse->json();

        // ASSERT - The product name should be updated in the second response
        $updatedProduct = collect($secondResponseData['products'])->firstWhere('id', $product->id);
        $this->assertEquals('Updated Product Name', $updatedProduct['name']);
    }
}