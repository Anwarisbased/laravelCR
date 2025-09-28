# Laravel Vertical Slice 06: Reward Catalog

## Overview
This vertical slice implements the reward catalog system including product browsing, eligibility checking, and catalog management using Laravel's native features, replacing WooCommerce product integration.

## Key Components

### Laravel Components
- Eloquent Product model
- Laravel API Resources for response formatting
- Laravel Cache for catalog caching
- Laravel Validation for product validation
- Laravel Policies for product eligibility
- Laravel Scout for search functionality (if needed)

### Domain Entities
- Product (Eloquent Model)
- ProductId (Value Object)
- Sku (Value Object)
- Points (Value Object)
- RankKey (Value Object)

### API Endpoints
- `GET /api/v1/catalog/products` - Get all reward products (with caching)
- `GET /api/v1/catalog/products/{id}` - Get specific product
- `GET /api/v1/catalog/categories` - Get product categories
- `GET /api/v1/catalog/search` - Search products (optional)

### Laravel Services
- CatalogService (catalog management)
- ProductService (product operations)
- ProductEligibilityService (eligibility checking)
- ProductSearchService (search functionality)

### Laravel Models
- Product (Eloquent model for product data)
- ProductCategory (Eloquent model for categories)

### Laravel Resources
- ProductResource (API resource for single product)
- ProductCollection (API resource for product collections)

### Laravel Policies
- UserCanAffordProductPolicy
- UserMeetsRankRequirementPolicy
- ProductRequiresFirstScanPolicy

## Implementation Details

### Product Model Structure
```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'sku',
        'description',
        'short_description',
        'points_award',
        'points_cost',
        'required_rank_key',
        'is_active',
        'is_featured',
        'is_new',
        'category_id',
        'brand',
        'strain_type',
        'thc_content',
        'cbd_content',
        'product_form',
        'marketing_snippet',
        'image_urls',
        'tags',
        'sort_order',
        'available_from',
        'available_until',
    ];
    
    protected $casts = [
        'points_award' => 'integer',
        'points_cost' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_new' => 'boolean',
        'image_urls' => 'array',
        'tags' => 'array',
        'available_from' => 'datetime',
        'available_until' => 'datetime',
        'thc_content' => 'float',
        'cbd_content' => 'float',
    ];
    
    // Relationships
    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('available_from')
                  ->orWhere('available_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('available_until')
                  ->orWhere('available_until', '>=', now());
            });
    }
    
    public function scopeRewardable($query)
    {
        return $query->where('points_cost', '>', 0);
    }
    
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }
    
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
    
    public function scopeNew($query)
    {
        return $query->where('is_new', true);
    }
    
    // Accessors
    public function getImageUrlsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    public function setImageUrlsAttribute($value)
    {
        $this->attributes['image_urls'] = $value ? json_encode($value) : null;
    }
    
    public function getTagsAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
    }
    
    public function setTagsAttribute($value)
    {
        $this->attributes['tags'] = $value ? json_encode($value) : null;
    }
    
    // Methods
    public function isInStock(): bool
    {
        // Implement stock checking logic if needed
        return true;
    }
    
    public function isAvailable(): bool
    {
        return $this->is_active && 
               (!$this->available_from || $this->available_from <= now()) &&
               (!$this->available_until || $this->available_until >= now());
    }
}
```

### Product Category Model
```php
// app/Models/ProductCategory.php
class ProductCategory extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    // Relationships
    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }
    
    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }
}
```

### Catalog Service Implementation
```php
// app/Services/CatalogService.php
class CatalogService
{
    protected $productEligibilityService;
    protected $cacheTtl;
    
    public function __construct(ProductEligibilityService $productEligibilityService)
    {
        $this->productEligibilityService = $productEligibilityService;
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
}
```

## Product Eligibility Checking

### Product Eligibility Service
```php
// app/Services/ProductEligibilityService.php
class ProductEligibilityService
{
    protected $economyService;
    protected $rankService;
    protected $actionLogRepository;
    
    public function __construct(
        EconomyService $economyService,
        RankService $rankService,
        ActionLogRepository $actionLogRepository
    ) {
        $this->economyService = $economyService;
        $this->rankService = $rankService;
        $this->actionLogRepository = $actionLogRepository;
    }
    
    public function checkEligibility(User $user, Product $product): array
    {
        $eligibility = [
            'is_eligible' => true,
            'reasons' => [],
            'eligible_for_free_claim' => false,
        ];
        
        // Check if user can afford the product
        if (!$this->canAfford($user, $product)) {
            $eligibility['is_eligible'] = false;
            $eligibility['reasons'][] = 'insufficient_points';
        }
        
        // Check rank requirements
        if (!$this->meetsRankRequirement($user, $product)) {
            $eligibility['is_eligible'] = false;
            $eligibility['reasons'][] = 'rank_requirement_not_met';
        }
        
        // Check if eligible for free claim (welcome gift or referral gift)
        $eligibility['eligible_for_free_claim'] = $this->isEligibleForFreeClaim($user, $product);
        
        return $eligibility;
    }
    
    protected function canAfford(User $user, Product $product): bool
    {
        return $user->points_balance >= $product->points_cost;
    }
    
    protected function meetsRankRequirement(User $user, Product $product): bool
    {
        if (empty($product->required_rank_key)) {
            return true;
        }
        
        $userRank = $this->rankService->getUserRank($user);
        $requiredRank = $this->rankService->getRankByKey($product->required_rank_key);
        
        if (!$requiredRank) {
            return true; // Invalid rank requirement
        }
        
        // Check if user's rank meets or exceeds required rank
        return $userRank->pointsRequired->toInt() >= $requiredRank->pointsRequired->toInt();
    }
    
    protected function isEligibleForFreeClaim(User $user, Product $product): bool
    {
        // Check if this is a welcome gift or referral gift
        $welcomeGiftId = config('cannarewards.welcome_gift_product_id');
        $referralGiftId = config('cannarewards.referral_sign_up_gift_id');
        
        if ($product->id !== $welcomeGiftId && $product->id !== $referralGiftId) {
            return false;
        }
        
        // Check if user has scanned 0 or 1 products (eligible for free claim)
        $scanCount = $this->actionLogRepository->countUserActions($user->id, 'scan');
        return $scanCount <= 1;
    }
}
```

## Laravel-Native Features Utilized

### API Resources
- Laravel API Resources for consistent response formatting
- Collection resources for list responses
- Conditional fields based on user context
- Response transformation and serialization

### Caching
- Laravel Cache facade for performance optimization
- Cache tags for granular invalidation (when using Redis)
- Automatic cache expiration and refresh
- Multiple cache drivers (Redis, Memcached, File)

### Validation
- Laravel Form Requests for input validation
- Custom validation rules for product attributes
- Automatic error response formatting

### Eloquent ORM
- Eloquent relationships for product-category associations
- Eloquent scopes for common queries
- Eloquent accessors/mutators for data transformation
- Eloquent collections for result manipulation

### Collections
- Laravel Collection methods for product filtering
- Higher-order messaging for product operations
- Collection pipelining for complex transformations

## Business Logic Implementation

### Product Metadata Management
```php
// app/Services/ProductMetadataService.php
class ProductMetadataService
{
    public function getFormattedProductData(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'points_award' => $product->points_award,
            'points_cost' => $product->points_cost,
            'required_rank' => $product->required_rank_key,
            'category' => $product->category ? [
                'id' => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'brand' => $product->brand,
            'strain_type' => $product->strain_type,
            'thc_content' => $product->thc_content,
            'cbd_content' => $product->cbd_content,
            'product_form' => $product->product_form,
            'marketing_snippet' => $product->marketing_snippet,
            'images' => $this->formatImages($product->image_urls),
            'tags' => $product->tags,
            'is_featured' => $product->is_featured,
            'is_new' => $product->is_new,
            'availability_dates' => [
                'from' => $product->available_from,
                'until' => $product->available_until,
            ],
        ];
    }
    
    protected function formatImages(array $imageUrls): array
    {
        return array_map(function ($url) {
            return [
                'url' => $url,
                'thumbnail' => $this->generateThumbnailUrl($url),
                'alt' => 'Product image',
            ];
        }, $imageUrls);
    }
    
    protected function generateThumbnailUrl(string $originalUrl): string
    {
        // Implement thumbnail generation logic
        return str_replace('/images/', '/images/thumbnails/', $originalUrl);
    }
}
```

### Catalog Filtering and Sorting
```php
// app/Http/Requests/CatalogFilterRequest.php
class CatalogFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'exists:product_categories,id'],
            'brand' => ['sometimes', 'string'],
            'strain_type' => ['sometimes', 'string'],
            'min_points' => ['sometimes', 'integer', 'min:0'],
            'max_points' => ['sometimes', 'integer', 'min:0'],
            'rank_key' => ['sometimes', 'string', 'exists:ranks,key'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'sort_by' => ['sometimes', 'in:name,points_cost,created_at'],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
    
    public function prepareForValidation(): void
    {
        $this->merge([
            'limit' => $this->limit ?? 20,
            'page' => $this->page ?? 1,
            'sort_by' => $this->sort_by ?? 'name',
            'sort_direction' => $this->sort_direction ?? 'asc',
        ]);
    }
}
```

## API Resources Implementation

### Product API Resource
```php
// app/Http/Resources/ProductResource.php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'points_award' => $this->points_award,
            'points_cost' => $this->points_cost,
            'required_rank_key' => $this->required_rank_key,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'brand' => $this->brand,
            'strain_type' => $this->strain_type,
            'thc_content' => $this->thc_content,
            'cbd_content' => $this->cbd_content,
            'product_form' => $this->product_form,
            'marketing_snippet' => $this->marketing_snippet,
            'images' => $this->image_urls,
            'tags' => $this->tags,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
        
        // Add category if available
        if ($this->relationLoaded('category')) {
            $data['category'] = $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null;
        }
        
        // Add eligibility info if available
        if (isset($this->eligibility)) {
            $data['eligibility'] = $this->eligibility;
        }
        
        return $data;
    }
}
```

## Data Migration Strategy

### From WordPress/WooCommerce to Laravel
- Migrate WooCommerce products to products table
- Convert product meta for points_award and points_cost
- Migrate custom taxonomy for categories and tags
- Preserve product images and media library references
- Migrate product variations if applicable
- Convert WooCommerce attributes to product fields
- Maintain product availability dates
- Preserve SEO metadata and descriptions

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for caching)
- Eloquent ORM
- Laravel API Resources

## Definition of Done
- [ ] All reward products are correctly listed in catalog with proper pagination
- [ ] Product details are properly formatted for API response with all metadata
- [ ] Product images are correctly handled and formatted with thumbnails
- [ ] Welcome gift eligibility is correctly determined for new users
- [ ] Referral gift eligibility is correctly determined for referred users
- [ ] Product metadata (points values, rank requirements) is properly extracted and displayed
- [ ] Catalog data is properly cached for performance (cache hit ratio > 90%)
- [ ] Eligibility checking correctly enforces points and rank requirements
- [ ] Featured and new product sections are properly displayed
- [ ] Product categories are correctly organized and displayed
- [ ] Catalog filtering and sorting works correctly
- [ ] Adequate test coverage for all catalog endpoints (100% API coverage)
- [ ] Error handling for edge cases (invalid product IDs, etc.)
- [ ] Performance benchmarks met (response time < 200ms for catalog operations)
- [ ] Cache invalidation works correctly when products are updated
- [ ] Product search functionality works (if implemented)
- [ ] Category hierarchy is properly maintained and displayed