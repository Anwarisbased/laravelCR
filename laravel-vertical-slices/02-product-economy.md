# Laravel Vertical Slice 02: Product Economy

## Overview
This vertical slice implements the core product economy system including scanning, claiming, and point management using Laravel's native features.

## Key Components

### Laravel Components
- Eloquent Product model
- Eloquent RewardCode model
- Laravel Events for domain events
- Laravel Jobs for background processing
- Laravel Policies for authorization
- Laravel Validation for request validation

### Domain Entities
- Product (Eloquent Model)
- RewardCode (Eloquent Model)
- Points (Value Object)
- Sku (Value Object)
- ProductId (Value Object)

### API Endpoints
- `POST /api/v1/actions/claim` - Process authenticated product scan
- `POST /api/v1/unauthenticated/claim` - Process unauthenticated product scan
- `GET /api/v1/catalog/products` - Get all reward products
- `GET /api/v1/catalog/products/{id}` - Get specific product

### Laravel Services
- ProductService (product management)
- EconomyService (points economy logic)
- ScanService (scanning logic)
- ClaimService (claim processing)

### Laravel Models
- Product (Eloquent model with reward attributes)
- RewardCode (Eloquent model for QR codes)
- ActionLog (Eloquent model for user actions)

### Laravel Events
- ProductScanned
- PointsGranted
- FirstScanCompleted
- UnauthenticatedClaimProcessed

### Laravel Jobs
- ProcessProductScan
- GrantPointsForScan
- AwardFirstScanBonus

### Laravel Policies
- CanAffordRedemptionPolicy
- ProductExistsForSkuPolicy
- RewardCodeIsValidPolicy

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
        'points_award',
        'points_cost',
        'required_rank_key',
        'is_active',
        'image_url',
        'category',
        'brand',
    ];
    
    protected $casts = [
        'points_award' => 'integer',
        'points_cost' => 'integer',
        'is_active' => 'boolean',
    ];
    
    // Accessors
    public function getPointsAwardAttribute($value)
    {
        return $value ?? 0;
    }
    
    public function getPointsCostAttribute($value)
    {
        return $value ?? 0;
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeRewardable($query)
    {
        return $query->where('points_cost', '>', 0);
    }
}
```

### Reward Code Model Structure
```php
// app/Models/RewardCode.php
class RewardCode extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'code',
        'sku',
        'batch_id',
        'is_used',
        'user_id',
        'claimed_at',
    ];
    
    protected $casts = [
        'is_used' => 'boolean',
        'claimed_at' => 'datetime',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class, 'sku', 'sku');
    }
}
```

### Scanning Workflow
1. User scans QR code via mobile app
2. App sends code to `POST /api/v1/actions/claim`
3. Laravel Form Request validates code format
4. ClaimService validates reward code exists and is unused
5. ProductService finds associated product by SKU
6. ActionLog records scan event
7. ProductScanned event is fired
8. Event listeners process scan (award points, check first scan, etc.)

### Points Economy
- Points are awarded based on product MSRP (10 points per $1)
- First scan triggers welcome gift redemption
- Subsequent scans award standard points
- Rank-based multipliers applied to points
- Points balance tracked in User model

## Laravel-Native Features Utilized

### Events & Listeners
- Laravel Event system for domain events
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Validation
- Laravel Form Requests for input validation
- Custom validation rules for business logic
- Automatic error response formatting

### Authorization
- Laravel Policies for fine-grained access control
- Gates for simple authorization checks
- Middleware for route-level authorization

### Caching
- Laravel Cache facade for performance
- Model caching for frequently accessed data
- Response caching for catalog endpoints

### Queue Processing
- Laravel Jobs for background processing
- Queue workers for async operations
- Failed job handling and retry logic

## Business Logic Implementation

### First Scan Detection
```php
// In ScanService
public function isUserFirstScan(User $user): bool
{
    return $user->actionLogs()
        ->where('action_type', 'scan')
        ->count() === 0;
}
```

### Point Calculation
```php
// In EconomyService
public function calculatePointsForScan(Product $product, User $user): int
{
    $basePoints = $product->points_award;
    $rank = $this->rankService->getUserRank($user);
    return (int) ($basePoints * $rank->point_multiplier);
}
```

## Data Migration Strategy

### From WordPress to Laravel
- Migrate WooCommerce products to products table
- Migrate custom reward code tables to reward_codes table
- Preserve existing QR code associations
- Migrate product meta for points values
- Maintain scan history in action_logs table

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for queues and caching)
- Eloquent ORM

## Definition of Done
- [ ] User can scan valid QR code and receive confirmation
- [ ] System rejects invalid or used QR codes with proper error responses
- [ ] First scan triggers welcome gift redemption via background job
- [ ] Subsequent scans award appropriate points based on product and rank
- [ ] Scan history is properly recorded in action_logs table
- [ ] Events are correctly broadcast and processed by listeners
- [ ] All operations are properly logged with Laravel logging
- [ ] Adequate test coverage using Laravel testing features (100% of scan endpoints)
- [ ] Error handling for edge cases with Laravel exception handling
- [ ] Performance benchmarks met using caching (response time < 200ms for scan operations)
- [ ] Background processing via Laravel queues for optimal performance
- [ ] Proper validation using Laravel Form Requests
- [ ] Authorization policies enforced for claim operations