# Laravel Vertical Slice 03: Rank Progression & Loyalty Tiers

## Overview
This vertical slice implements the rank progression system and loyalty tier management using Laravel's native features, replacing WordPress custom post types for rank definitions.

## Key Components

### Laravel Components
- Eloquent Rank model
- Laravel Cache for rank structure caching
- Laravel Events for rank changes
- Laravel Jobs for rank calculations
- Laravel Policies for rank-based restrictions
- Laravel Validation for rank management

### Domain Entities
- Rank (Eloquent Model)
- RankKey (Value Object)
- Points (Value Object)
- UserId (Value Object)

### API Endpoints
- `GET /api/v1/users/ranks` - Get all available ranks
- `GET /api/v1/users/{id}/rank` - Get specific user's current rank
- `GET /api/v1/users/me/rank` - Get authenticated user's current rank

### Laravel Services
- RankService (rank calculation and management)
- RankProgressionService (user rank progression logic)
- RankMultiplierService (point multiplier application)

### Laravel Models
- Rank (Eloquent model for rank definitions)
- User (extended with rank relationships)

### Laravel Events
- UserRankChanged
- RankStructureUpdated

### Laravel Jobs
- CalculateUserRank
- UpdateUserRankBenefits
- NotifyRankChange

### Laravel Policies
- UserMeetsRankRequirementPolicy

## Implementation Details

### Rank Model Structure
```php
// app/Models/Rank.php
class Rank extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'key',
        'name',
        'description',
        'points_required',
        'point_multiplier',
        'benefits',
        'is_active',
        'sort_order',
    ];
    
    protected $casts = [
        'points_required' => 'integer',
        'point_multiplier' => 'float',
        'is_active' => 'boolean',
        'benefits' => 'array',
    ];
    
    // Accessors
    public function getKeyAttribute($value)
    {
        return strtolower($value);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('points_required');
    }
    
    // Methods
    public function qualifiesFor(int $lifetimePoints): bool
    {
        return $lifetimePoints >= $this->points_required;
    }
}
```

### User Extension for Rank
```php
// In User model
class User extends Authenticatable
{
    // ... existing code ...
    
    public function getCurrentRankAttribute()
    {
        return $this->rank();
    }
    
    public function rank()
    {
        return $this->belongsTo(Rank::class, 'current_rank_key', 'key');
    }
    
    public function getNextRankAttribute()
    {
        return $this->hasOne(Rank::class, 'key', 'next_rank_key');
    }
}
```

### Rank Calculation Logic
```php
// app/Services/RankService.php
class RankService
{
    protected $rankStructure;
    
    public function __construct()
    {
        $this->loadRankStructure();
    }
    
    protected function loadRankStructure(): void
    {
        $this->rankStructure = Cache::remember('rank_structure', 3600, function () {
            return Rank::active()->ordered()->get();
        });
    }
    
    public function getUserRank(User $user): Rank
    {
        $lifetimePoints = $user->lifetime_points;
        
        // Find the highest rank the user qualifies for
        $qualifyingRanks = $this->rankStructure->filter(
            fn($rank) => $rank->qualifiesFor($lifetimePoints)
        );
        
        return $qualifyingRanks->last() ?? $this->getDefaultRank();
    }
    
    public function getDefaultRank(): Rank
    {
        return $this->rankStructure->firstWhere('key', 'member') 
            ?? $this->rankStructure->first();
    }
    
    public function getRankByKey(string $key): ?Rank
    {
        return $this->rankStructure->firstWhere('key', $key);
    }
    
    public function recalculateUserRank(User $user): Rank
    {
        $newRank = $this->getUserRank($user);
        $currentRankKey = $user->current_rank_key;
        
        if ($currentRankKey !== $newRank->key) {
            $user->current_rank_key = $newRank->key;
            $user->save();
            
            // Fire event for rank change
            event(new UserRankChanged($user, $newRank));
        }
        
        return $newRank;
    }
}
```

## Rank Progression Workflow

### Rank Determination
1. User's lifetime points are retrieved from User model
2. RankService loads cached rank structure
3. Service iterates through ranks to find highest qualifying rank
4. If different from current rank, update user's rank
5. Fire UserRankChanged event for other services

### Rank-Based Multipliers
```php
// app/Services/RankMultiplierService.php
class RankMultiplierService
{
    public function applyMultiplier(int $basePoints, User $user): int
    {
        $rank = app(RankService::class)->getUserRank($user);
        return (int) ($basePoints * $rank->point_multiplier);
    }
}
```

## Laravel-Native Features Utilized

### Caching
- Laravel Cache facade for rank structure caching
- Cache tags for granular invalidation
- Automatic cache expiration and refresh
- Redis or file-based caching drivers

### Events & Listeners
- Laravel Event system for rank changes
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Model Relationships
- Eloquent relationships between User and Rank
- Lazy/eager loading for performance
- Relationship constraints and scopes

### Collections
- Laravel Collection methods for rank filtering
- Higher-order messaging for rank operations
- Collection pipelining for complex calculations

## Business Logic Implementation

### Rank Progression Rules
- Ranks are defined by minimum lifetime points
- Higher ranks provide better point multipliers
- Rank changes automatically when lifetime points cross thresholds
- Rank-based restrictions on product redemptions

### Progress Tracking
```php
// In UserService
public function getRankProgress(User $user): array
{
    $currentRank = $this->rankService->getUserRank($user);
    $nextRank = $this->rankService->getNextHigherRank($currentRank);
    
    if (!$nextRank) {
        return [
            'current_rank' => $currentRank,
            'progress_percent' => 100,
            'points_to_next' => 0,
        ];
    }
    
    $pointsNeeded = $nextRank->points_required - $user->lifetime_points;
    $pointsRange = $nextRank->points_required - $currentRank->points_required;
    $progressPercent = ($pointsRange - $pointsNeeded) / $pointsRange * 100;
    
    return [
        'current_rank' => $currentRank,
        'next_rank' => $nextRank,
        'points_to_next' => max(0, $pointsNeeded),
        'progress_percent' => min(100, max(0, $progressPercent)),
    ];
}
```

## Data Migration Strategy

### From WordPress to Laravel
- Migrate `canna_rank` custom post types to ranks table
- Convert post meta for points_required and point_multiplier
- Preserve rank ordering and descriptions
- Migrate user rank associations to current_rank_key column
- Maintain historical rank change data

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for caching)
- Eloquent ORM

## Definition of Done
- [ ] Rank definitions can be configured with points requirements and multipliers using Laravel admin
- [ ] User lifetime points are correctly tracked and updated
- [ ] User rank is correctly calculated based on lifetime points
- [ ] Rank transitions occur automatically when lifetime points cross thresholds
- [ ] Rank-based point multipliers are correctly applied to awarded points
- [ ] Rank-based product restrictions are properly enforced during redemptions
- [ ] Rank structure is properly cached for performance (cache hit ratio > 95%)
- [ ] Rank changes are properly logged and tracked via events
- [ ] Adequate test coverage using Laravel testing features (100% of rank functionality)
- [ ] Error handling for edge cases with Laravel exception handling
- [ ] Performance benchmarks met (rank calculation < 50ms)
- [ ] Cache invalidation works correctly when rank definitions change
- [ ] Rank progression events are correctly broadcast and processed
- [ ] User rank progress tracking shows accurate percentage completion