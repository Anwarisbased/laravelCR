# Laravel Vertical Slice 05: Gamification Engine

## Overview
This vertical slice implements the gamification system including achievement definitions, unlocking logic, progress tracking, and reward distribution using Laravel's native features, replacing WordPress achievement management.

## Key Components

### Laravel Components
- Eloquent Achievement model
- Eloquent UserAchievement model
- Laravel Events for achievement events
- Laravel Jobs for achievement processing
- Laravel Notifications for achievement communications
- Laravel Cache for achievement data caching
- Laravel Validation for achievement validation
- Laravel Policies for achievement conditions

### Domain Entities
- Achievement (Eloquent Model)
- UserAchievement (Eloquent Model)
- UserId (Value Object)
- AchievementKey (Value Object)
- Points (Value Object)

### API Endpoints
- `GET /api/v1/users/me/achievements` - Get user's unlocked achievements
- `GET /api/v1/users/me/achievements/locked` - Get user's locked achievements
- `GET /api/v1/achievements` - Get all available achievements

### Laravel Services
- AchievementService (achievement management)
- AchievementUnlockService (achievement unlocking logic)
- AchievementProgressService (progress tracking)
- AchievementRewardService (reward distribution)

### Laravel Models
- Achievement (Eloquent model for achievement definitions)
- UserAchievement (Eloquent model for unlocked achievements)
- User (extended with achievement relationships)

### Laravel Events
- AchievementCriteriaMet
- AchievementUnlocked
- AchievementRewardGranted

### Laravel Jobs
- EvaluateAchievementCriteria
- UnlockAchievement
- GrantAchievementReward

### Laravel Notifications
- AchievementUnlockedNotification
- AchievementProgressNotification

## Implementation Details

### Achievement Model Structure
```php
// app/Models/Achievement.php
class Achievement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'key',
        'title',
        'description',
        'points_reward',
        'rarity',
        'icon_url',
        'is_active',
        'trigger_event',
        'trigger_count',
        'conditions',
        'category',
        'sort_order',
    ];
    
    protected $casts = [
        'points_reward' => 'integer',
        'is_active' => 'boolean',
        'trigger_count' => 'integer',
        'conditions' => 'array',
        'sort_order' => 'integer',
    ];
    
    // Accessors
    public function getKeyAttribute($value)
    {
        return strtolower(str_replace('-', '_', $value));
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
    
    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }
    
    // Methods
    public function meetsConditions(array $context): bool
    {
        if (empty($this->conditions)) {
            return true;
        }
        
        return app(RulesEngineService::class)->evaluate($this->conditions, $context);
    }
}
```

### User Achievement Model Structure
```php
// app/Models/UserAchievement.php
class UserAchievement extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'achievement_key',
        'unlocked_at',
        'trigger_count',
    ];
    
    protected $casts = [
        'unlocked_at' => 'datetime',
        'trigger_count' => 'integer',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function achievement()
    {
        return $this->belongsTo(Achievement::class, 'achievement_key', 'key');
    }
}
```

### Achievement Service Implementation
```php
// app/Services/AchievementService.php
class AchievementService
{
    protected $rulesEngineService;
    protected $achievementCache;
    
    public function __construct(RulesEngineService $rulesEngineService)
    {
        $this->rulesEngineService = $rulesEngineService;
        $this->achievementCache = [];
    }
    
    public function getAchievementsByTriggerEvent(string $event): Collection
    {
        if (!isset($this->achievementCache[$event])) {
            $this->achievementCache[$event] = Cache::remember(
                "achievements_trigger_{$event}", 
                3600, 
                function () use ($event) {
                    return Achievement::active()
                        ->where('trigger_event', $event)
                        ->get();
                }
            );
        }
        
        return $this->achievementCache[$event];
    }
    
    public function evaluateAchievements(User $user, string $event, array $context = []): void
    {
        $achievements = $this->getAchievementsByTriggerEvent($event);
        $unlockedKeys = $user->unlockedAchievements->pluck('key')->toArray();
        
        foreach ($achievements as $achievement) {
            // Skip already unlocked achievements
            if (in_array($achievement->key, $unlockedKeys)) {
                continue;
            }
            
            // Check if conditions are met
            if ($achievement->meetsConditions($context)) {
                // Check trigger count
                $triggerCount = $this->getTriggerCount($user, $achievement, $event);
                
                if ($triggerCount >= $achievement->trigger_count) {
                    // Unlock achievement
                    $this->unlockAchievement($user, $achievement);
                } else {
                    // Update trigger count
                    $this->updateTriggerCount($user, $achievement, $triggerCount + 1);
                    
                    // Send progress notification
                    if ($triggerCount + 1 === $achievement->trigger_count - 1) {
                        // One more to go
                        $user->notify(new AchievementProgressNotification($achievement));
                    }
                }
            }
        }
    }
    
    protected function unlockAchievement(User $user, Achievement $achievement): void
    {
        // Create user achievement record
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_key' => $achievement->key,
            'unlocked_at' => now(),
            'trigger_count' => $achievement->trigger_count,
        ]);
        
        // Grant points reward
        if ($achievement->points_reward > 0) {
            GrantAchievementReward::dispatch(
                $user->id, 
                $achievement->points_reward, 
                "Achievement unlocked: {$achievement->title}"
            );
        }
        
        // Send notification
        $user->notify(new AchievementUnlockedNotification($achievement));
        
        // Fire event
        event(new AchievementUnlocked($user, $achievement));
    }
    
    protected function getTriggerCount(User $user, Achievement $achievement, string $event): int
    {
        $userAchievement = UserAchievement::where('user_id', $user->id)
            ->where('achievement_key', $achievement->key)
            ->first();
            
        return $userAchievement ? $userAchievement->trigger_count : 0;
    }
    
    protected function updateTriggerCount(User $user, Achievement $achievement, int $count): void
    {
        UserAchievement::updateOrCreate(
            [
                'user_id' => $user->id,
                'achievement_key' => $achievement->key,
            ],
            [
                'trigger_count' => $count,
            ]
        );
    }
}
```

## Gamification Workflow

### Achievement Evaluation
1. Domain events fire (product_scanned, user_rank_changed, etc.)
2. AchievementService listens to these events via Laravel Event system
3. Service retrieves achievements triggered by that event
4. For each achievement:
   - Check if user already has it unlocked
   - Evaluate conditions using Rules Engine
   - Check trigger count
   - If all criteria met, unlock achievement
   - If partial criteria met, update progress

### Achievement Unlocking
```php
// app/Listeners/AchievementEventListener.php
class AchievementEventListener
{
    protected $achievementService;
    
    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }
    
    public function handleProductScanned(ProductScanned $event): void
    {
        $context = [
            'user' => [
                'id' => $event->user->id,
                'email' => $event->user->email,
                'lifetime_points' => $event->user->lifetime_points,
                'current_rank' => $event->user->current_rank_key,
            ],
            'product' => [
                'id' => $event->product->id,
                'sku' => $event->product->sku,
                'category' => $event->product->category,
            ],
            'scan' => [
                'is_first' => $event->isFirstScan,
                'timestamp' => $event->timestamp,
            ],
        ];
        
        $this->achievementService->evaluateAchievements(
            $event->user, 
            'product_scanned', 
            $context
        );
    }
    
    public function handleUserRankChanged(UserRankChanged $event): void
    {
        $context = [
            'user' => [
                'id' => $event->user->id,
                'email' => $event->user->email,
                'new_rank' => $event->newRank->key,
                'previous_rank' => $event->previousRank?->key,
            ],
        ];
        
        $this->achievementService->evaluateAchievements(
            $event->user, 
            'user_rank_changed', 
            $context
        );
    }
}
```

### Rules Engine Integration
```php
// app/Services/RulesEngineService.php
class RulesEngineService
{
    public function evaluate(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateSingleCondition($condition, $context)) {
                return false;
            }
        }
        
        return true;
    }
    
    protected function evaluateSingleCondition(array $condition, array $context): bool
    {
        $fieldPath = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '';
        $expectedValue = $condition['value'] ?? null;
        
        $actualValue = $this->getValueFromContext($fieldPath, $context);
        
        switch ($operator) {
            case 'is':
                return $actualValue == $expectedValue;
            case 'is_not':
                return $actualValue != $expectedValue;
            case '>':
                return (float)$actualValue > (float)$expectedValue;
            case '<':
                return (float)$actualValue < (float)$expectedValue;
            case 'contains':
                return strpos($actualValue, $expectedValue) !== false;
            default:
                return false;
        }
    }
    
    protected function getValueFromContext(string $fieldPath, array $context)
    {
        $keys = explode('.', $fieldPath);
        $value = $context;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
}
```

## Laravel-Native Features Utilized

### Events & Listeners
- Laravel Event system for achievement triggers
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Jobs & Queues
- Laravel Jobs for background achievement processing
- Queue workers for async operations
- Failed job handling and retry logic
- Job batching for complex workflows

### Notifications
- Laravel Notifications for user communications
- Multiple channels (email, SMS, database, push)
- Markdown notification templates
- Notification throttling and grouping

### Caching
- Laravel Cache facade for achievement data
- Cache tags for granular invalidation
- Automatic cache expiration and refresh
- Redis or file-based caching drivers

### Collections
- Laravel Collection methods for achievement filtering
- Higher-order messaging for achievement operations
- Collection pipelining for complex calculations

## Business Logic Implementation

### Achievement Categories
- Scan-based achievements (first scan, 10 scans, etc.)
- Rank-based achievements (reach Bronze, Gold, etc.)
- Referral achievements (refer 5 friends, etc.)
- Point-based achievements (earn 1000 points, etc.)
- Special achievements (holiday events, etc.)

### Rarity System
```php
// app/Enums/AchievementRarity.php
enum AchievementRarity: string
{
    case COMMON = 'common';
    case UNCOMMON = 'uncommon';
    case RARE = 'rare';
    case EPIC = 'epic';
    case LEGENDARY = 'legendary';
    
    public function getPointsMultiplier(): float
    {
        return match($this) {
            self::COMMON => 1.0,
            self::UNCOMMON => 1.2,
            self::RARE => 1.5,
            self::EPIC => 2.0,
            self::LEGENDARY => 3.0,
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::COMMON => 'gray',
            self::UNCOMMON => 'green',
            self::RARE => 'blue',
            self::EPIC => 'purple',
            self::LEGENDARY => 'orange',
        };
    }
}
```

### Achievement Progress Tracking
```php
// app/Services/AchievementProgressService.php
class AchievementProgressService
{
    public function getUserAchievementProgress(User $user, Achievement $achievement): array
    {
        $currentCount = $this->getCurrentTriggerCount($user, $achievement);
        $requiredCount = $achievement->trigger_count;
        
        return [
            'achievement' => $achievement,
            'current_count' => $currentCount,
            'required_count' => $requiredCount,
            'progress_percent' => min(100, ($currentCount / $requiredCount) * 100),
            'is_unlocked' => $user->unlockedAchievements->contains('key', $achievement->key),
        ];
    }
    
    public function getUserOverallProgress(User $user): array
    {
        $totalAchievements = Achievement::active()->count();
        $unlockedAchievements = $user->unlockedAchievements()->count();
        
        return [
            'total_achievements' => $totalAchievements,
            'unlocked_achievements' => $unlockedAchievements,
            'completion_percentage' => $totalAchievements > 0 ? 
                ($unlockedAchievements / $totalAchievements) * 100 : 0,
        ];
    }
}
```

## Data Migration Strategy

### From WordPress to Laravel
- Migrate `canna_achievement` custom post types to achievements table
- Convert post meta for achievement attributes
- Migrate user achievement unlocks to user_achievements table
- Preserve achievement conditions as JSON
- Maintain achievement categorization and sorting

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for queues and caching)
- Eloquent ORM

## Definition of Done
- [ ] Achievements can be defined with trigger events and conditions using Laravel admin
- [ ] Achievement conditions are correctly evaluated using Rules Engine
- [ ] Achievements are properly unlocked when conditions are met
- [ ] Duplicate achievements are not unlocked for the same user
- [ ] Point rewards are correctly granted for unlocked achievements
- [ ] Achievement unlocks are properly logged and tracked via database records
- [ ] Achievement notifications are correctly sent to users
- [ ] Achievement events are correctly broadcast and processed by listeners
- [ ] Adequate test coverage using Laravel testing features (100% of gamification functionality)
- [ ] Error handling for edge cases with Laravel exception handling
- [ ] Performance benchmarks met (achievement evaluation < 50ms)
- [ ] Background processing via Laravel queues for reward granting
- [ ] Cache efficiency for achievement data (hit ratio > 95%)
- [ ] Achievement progress tracking shows accurate completion percentages
- [ ] Different achievement rarities provide appropriate rewards and recognition
- [ ] Achievement categories allow for organized user progression tracking
- [ ] Rules engine correctly evaluates complex achievement conditions