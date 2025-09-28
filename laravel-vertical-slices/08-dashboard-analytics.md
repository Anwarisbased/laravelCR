# Laravel Vertical Slice 08: Dashboard & Analytics

## Overview
This vertical slice implements the user dashboard and analytics system including personalized data presentation, statistics, and user insights using Laravel's native features.

## Key Components

### Laravel Components
- Eloquent DashboardData model (if needed)
- Laravel Cache for dashboard data caching
- Laravel Events for dashboard updates
- Laravel Jobs for analytics processing
- Laravel Notifications for insights
- Laravel Validation for dashboard requests
- Laravel Policies for dashboard access
- Laravel Collections for data aggregation

### Domain Entities
- DashboardData (DTO for dashboard information)
- UserId (Value Object)
- Points (Value Object)
- RankKey (Value Object)

### API Endpoints
- `GET /api/v1/users/me/dashboard` - Get user dashboard data
- `GET /api/v1/users/me/history` - Get user points history
- `GET /api/v1/users/me/analytics` - Get user analytics data
- `GET /api/v1/users/me/insights` - Get personalized user insights

### Laravel Services
- DashboardService (dashboard data aggregation)
- AnalyticsService (analytics processing)
- InsightService (personalized insights)
- HistoryService (points history)

### Laravel Models
- User (extended with dashboard relationships)
- ActionLog (for history data)

### Laravel Events
- DashboardDataUpdated
- UserMilestoneReached
- EngagementLevelChanged

### Laravel Jobs
- GenerateDashboardData
- ProcessUserAnalytics
- SendWeeklyInsights

### Laravel Notifications
- WeeklyInsightNotification
- MilestoneAchievedNotification
- EngagementUpdateNotification

### Laravel Resources
- DashboardResource (API resource for dashboard data)
- HistoryResource (API resource for history data)
- InsightResource (API resource for insights)

## Implementation Details

### Dashboard Service Implementation
```php
// app/Services/DashboardService.php
class DashboardService
{
    protected $analyticsService;
    protected $insightService;
    protected $historyService;
    protected $cacheTtl;
    
    public function __construct(
        AnalyticsService $analyticsService,
        InsightService $insightService,
        HistoryService $historyService
    ) {
        $this->analyticsService = $analyticsService;
        $this->insightService = $insightService;
        $this->historyService = $historyService;
        $this->cacheTtl = config('cache.dashboard_ttl', 300); // 5 minutes
    }
    
    public function getUserDashboardData(User $user): array
    {
        $cacheKey = "dashboard_user_{$user->id}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
            return $this->aggregateDashboardData($user);
        });
    }
    
    protected function aggregateDashboardData(User $user): array
    {
        // Get user's current rank
        $currentRank = app(RankService::class)->getUserRank($user);
        
        // Get engagement metrics
        $engagementMetrics = $this->analyticsService->getUserEngagementMetrics($user);
        
        // Get recent activity
        $recentActivity = $this->historyService->getRecentActivity($user, 5);
        
        // Get personalized insights
        $insights = $this->insightService->getPersonalizedInsights($user);
        
        // Get progress towards goals
        $goalProgress = $this->analyticsService->getUserGoalProgress($user);
        
        // Get upcoming events or promotions
        $upcomingEvents = $this->getUpcomingEvents($user);
        
        return [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'points_balance' => $user->points_balance,
                'lifetime_points' => $user->lifetime_points,
                'current_rank' => [
                    'key' => $currentRank->key,
                    'name' => $currentRank->name,
                    'points_required' => $currentRank->pointsRequired->toInt(),
                    'point_multiplier' => $currentRank->pointMultiplier,
                ],
                'referral_code' => $user->referral_code,
                'shipping_address' => [
                    'first_name' => $user->shipping_first_name,
                    'last_name' => $user->shipping_last_name,
                    'address_1' => $user->shipping_address_1,
                    'city' => $user->shipping_city,
                    'state' => $user->shipping_state,
                    'postcode' => $user->shipping_postcode,
                ],
            ],
            'engagement' => $engagementMetrics,
            'recent_activity' => $recentActivity,
            'insights' => $insights,
            'goal_progress' => $goalProgress,
            'upcoming_events' => $upcomingEvents,
            'last_updated' => now()->toISOString(),
        ];
    }
    
    protected function getUpcomingEvents(User $user): array
    {
        // This could integrate with event or promotion systems
        // For now, return placeholder data
        return [
            [
                'id' => 1,
                'title' => 'Double Points Weekend',
                'description' => 'Earn 2x points on all scans this weekend!',
                'starts_at' => now()->addDays(2)->toISOString(),
                'ends_at' => now()->addDays(4)->toISOString(),
                'is_active' => true,
            ],
        ];
    }
    
    public function clearUserCache(User $user): void
    {
        Cache::forget("dashboard_user_{$user->id}");
    }
}
```

### Analytics Service Implementation
```php
// app/Services/AnalyticsService.php
class AnalyticsService
{
    protected $actionLogRepository;
    protected $rankService;
    protected $achievementService;
    
    public function __construct(
        ActionLogRepository $actionLogRepository,
        RankService $rankService,
        AchievementService $achievementService
    ) {
        $this->actionLogRepository = $actionLogRepository;
        $this->rankService = $rankService;
        $this->achievementService = $achievementService;
    }
    
    public function getUserEngagementMetrics(User $user): array
    {
        $userId = $user->id;
        
        // Get total scans
        $totalScans = $this->actionLogRepository->countUserActions($userId, 'scan');
        
        // Get total redemptions
        $totalRedemptions = $this->actionLogRepository->countUserActions($userId, 'redeem');
        
        // Get total achievements unlocked
        $totalAchievements = $user->unlockedAchievements()->count();
        
        // Get days since signup
        $daysSinceSignup = $user->created_at->diffInDays(now());
        
        // Get days since last scan
        $lastScan = $this->actionLogRepository->getLastActionByType($userId, 'scan');
        $daysSinceLastScan = $lastScan ? $lastScan->created_at->diffInDays(now()) : $daysSinceSignup;
        
        // Get days since last redemption
        $lastRedemption = $this->actionLogRepository->getLastActionByType($userId, 'redeem');
        $daysSinceLastRedemption = $lastRedemption ? $lastRedemption->created_at->diffInDays(now()) : null;
        
        // Calculate engagement score (0-100)
        $engagementScore = $this->calculateEngagementScore($user, [
            'total_scans' => $totalScans,
            'days_since_last_scan' => $daysSinceLastScan,
            'total_redemptions' => $totalRedemptions,
            'total_achievements' => $totalAchievements,
        ]);
        
        // Determine if user is dormant (>30 days since last scan)
        $isDormant = $daysSinceLastScan > 30;
        
        // Determine if user is power user (top 10% of lifetime points)
        $isPowerUser = $this->isPowerUser($user);
        
        return [
            'total_scans' => $totalScans,
            'total_redemptions' => $totalRedemptions,
            'total_achievements_unlocked' => $totalAchievements,
            'days_since_signup' => $daysSinceSignup,
            'days_since_last_scan' => $daysSinceLastScan,
            'days_since_last_redemption' => $daysSinceLastRedemption,
            'engagement_score' => $engagementScore,
            'is_dormant' => $isDormant,
            'is_power_user' => $isPowerUser,
        ];
    }
    
    protected function calculateEngagementScore(User $user, array $metrics): int
    {
        $score = 0;
        
        // Base score from scans (up to 40 points)
        $score += min(40, $metrics['total_scans'] * 2);
        
        // Recency bonus (up to 20 points for recent activity)
        if ($metrics['days_since_last_scan'] <= 7) {
            $score += 20;
        } elseif ($metrics['days_since_last_scan'] <= 14) {
            $score += 10;
        }
        
        // Redemption bonus (up to 20 points)
        $score += min(20, $metrics['total_redemptions'] * 5);
        
        // Achievement bonus (up to 20 points)
        $score += min(20, $metrics['total_achievements'] * 2);
        
        return min(100, $score);
    }
    
    protected function isPowerUser(User $user): bool
    {
        // Get user's lifetime points rank percentile
        $userLifetimePoints = $user->lifetime_points;
        
        // This would typically query against all users to determine percentile
        // For demonstration, we'll use a simple threshold
        return $userLifetimePoints >= config('cannarewards.power_user_threshold', 10000);
    }
    
    public function getUserGoalProgress(User $user): array
    {
        // Get user's wishlist goals (would come from wishlist/Goal system)
        $wishlistGoals = $this->getUserWishlistGoals($user);
        
        // Get current progress towards each goal
        $progressData = [];
        
        foreach ($wishlistGoals as $goal) {
            $progressPercentage = $this->calculateProgressTowardsGoal($user, $goal);
            $progressData[] = [
                'goal_id' => $goal->id,
                'goal_name' => $goal->name,
                'goal_points_cost' => $goal->points_cost,
                'current_points' => $user->points_balance,
                'progress_percentage' => $progressPercentage,
                'points_needed' => max(0, $goal->points_cost - $user->points_balance),
                'is_achievable' => $user->points_balance >= $goal->points_cost,
            ];
        }
        
        return $progressData;
    }
    
    protected function getUserWishlistGoals(User $user): Collection
    {
        // This would integrate with a wishlist/goal system
        // For now, return placeholder data
        return collect([
            (object) [
                'id' => 1,
                'name' => 'Premium Vaporizer',
                'points_cost' => 5000,
            ],
            (object) [
                'id' => 2,
                'name' => 'Exclusive Cannabis Box',
                'points_cost' => 7500,
            ],
        ]);
    }
    
    protected function calculateProgressTowardsGoal(User $user, object $goal): int
    {
        if ($goal->points_cost <= 0) {
            return 100;
        }
        
        return min(100, ($user->points_balance / $goal->points_cost) * 100);
    }
}
```

## Dashboard Data Aggregation Workflow

### User Dashboard Request
1. User requests dashboard data via `GET /api/v1/users/me/dashboard`
2. DashboardController receives request and authenticates user
3. DashboardService checks cache for existing dashboard data
4. If cached data exists, return immediately
5. If no cached data, aggregate data from multiple sources:
   - User model for basic profile data
   - RankService for current rank information
   - AnalyticsService for engagement metrics
   - HistoryService for recent activity
   - InsightService for personalized insights
6. Cache aggregated data with TTL
7. Return formatted dashboard data via API Resource

### Data Aggregation Process
```php
// app/Services/DashboardAggregator.php
class DashboardAggregator
{
    public function aggregate(User $user): DashboardData
    {
        // Aggregate data in parallel where possible
        $userData = $this->getUserData($user);
        $rankData = $this->getRankData($user);
        $engagementData = $this->getEngagementData($user);
        $activityData = $this->getActivityData($user);
        $insightData = $this->getInsightData($user);
        
        return new DashboardData(
            user: $userData,
            rank: $rankData,
            engagement: $engagementData,
            recentActivity: $activityData,
            insights: $insightData,
            lastUpdated: now()
        );
    }
    
    protected function getUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'points_balance' => $user->points_balance,
            'lifetime_points' => $user->lifetime_points,
            'referral_code' => $user->referral_code,
            'shipping_address' => [
                'first_name' => $user->shipping_first_name,
                'last_name' => $user->shipping_last_name,
                'address_1' => $user->shipping_address_1,
                'city' => $user->shipping_city,
                'state' => $user->shipping_state,
                'postcode' => $user->shipping_postcode,
            ],
        ];
    }
    
    protected function getRankData(User $user): array
    {
        $currentRank = app(RankService::class)->getUserRank($user);
        
        return [
            'key' => $currentRank->key,
            'name' => $currentRank->name,
            'points_required' => $currentRank->pointsRequired->toInt(),
            'point_multiplier' => $currentRank->pointMultiplier,
        ];
    }
    
    // ... other aggregation methods
}
```

## History Service Implementation

### Points History Tracking
```php
// app/Services/HistoryService.php
class HistoryService
{
    protected $actionLogRepository;
    
    public function __construct(ActionLogRepository $actionLogRepository)
    {
        $this->actionLogRepository = $actionLogRepository;
    }
    
    public function getUserPointsHistory(User $user, int $limit = 50): Collection
    {
        $historyRecords = $this->actionLogRepository->getUserActionHistory(
            $user->id, 
            ['points_granted', 'redeem', 'achievement_unlocked'],
            $limit
        );
        
        return $historyRecords->map(function ($record) {
            return [
                'id' => $record->log_id,
                'action_type' => $record->action_type,
                'points_change' => $this->extractPointsChange($record),
                'description' => $this->formatDescription($record),
                'created_at' => $record->created_at->toISOString(),
            ];
        });
    }
    
    public function getRecentActivity(User $user, int $limit = 10): Collection
    {
        $recentActions = $this->actionLogRepository->getUserRecentActions($user->id, $limit);
        
        return $recentActions->map(function ($action) {
            return [
                'id' => $action->log_id,
                'type' => $action->action_type,
                'description' => $this->formatActionDescription($action),
                'points_change' => $this->extractPointsChange($action),
                'timestamp' => $action->created_at->toISOString(),
                'time_ago' => $action->created_at->diffForHumans(),
            ];
        });
    }
    
    protected function extractPointsChange($record): int
    {
        $metaData = json_decode($record->meta_data, true);
        
        if (isset($metaData['points_change'])) {
            return (int) $metaData['points_change'];
        }
        
        if (isset($metaData['points_awarded'])) {
            return (int) $metaData['points_awarded'];
        }
        
        if ($record->action_type === 'redeem') {
            // For redemptions, points change is negative
            return -(int) ($metaData['points_cost'] ?? 0);
        }
        
        return 0;
    }
    
    protected function formatDescription($record): string
    {
        $metaData = json_decode($record->meta_data, true);
        
        switch ($record->action_type) {
            case 'points_granted':
                return $metaData['description'] ?? 'Points awarded';
            case 'redeem':
                return "Redeemed: " . ($metaData['product_name'] ?? 'Reward item');
            case 'achievement_unlocked':
                return "Achievement unlocked: " . ($metaData['achievement_name'] ?? 'Unknown achievement');
            default:
                return ucfirst(str_replace('_', ' ', $record->action_type));
        }
    }
    
    protected function formatActionDescription($action): string
    {
        $metaData = json_decode($action->meta_data, true);
        
        switch ($action->action_type) {
            case 'scan':
                return "Scanned product: " . ($metaData['product_name'] ?? 'Unknown product');
            case 'points_granted':
                $points = $this->extractPointsChange($action);
                return "Earned {$points} points";
            case 'redeem':
                return "Redeemed: " . ($metaData['product_name'] ?? 'Reward item');
            case 'achievement_unlocked':
                return "Unlocked achievement: " . ($metaData['achievement_name'] ?? 'Unknown achievement');
            default:
                return ucfirst(str_replace('_', ' ', $action->action_type));
        }
    }
}
```

## Insight Service Implementation

### Personalized Insights
```php
// app/Services/InsightService.php
class InsightService
{
    protected $analyticsService;
    protected $historyService;
    protected $rankService;
    
    public function __construct(
        AnalyticsService $analyticsService,
        HistoryService $historyService,
        RankService $rankService
    ) {
        $this->analyticsService = $analyticsService;
        $this->historyService = $historyService;
        $this->rankService = $rankService;
    }
    
    public function getPersonalizedInsights(User $user): array
    {
        $insights = [];
        
        // Get user engagement metrics
        $engagementMetrics = $this->analyticsService->getUserEngagementMetrics($user);
        
        // Add insight based on engagement level
        if ($engagementMetrics['engagement_score'] < 30) {
            $insights[] = [
                'type' => 'encouragement',
                'title' => 'Start Earning Points!',
                'message' => 'Scan products to start earning points and unlock rewards.',
                'priority' => 'high',
                'action' => 'scan_product',
            ];
        } elseif ($engagementMetrics['engagement_score'] > 80) {
            $insights[] = [
                'type' => 'recognition',
                'title' => 'You\'re Doing Great!',
                'message' => 'Your high engagement is helping you earn awesome rewards.',
                'priority' => 'medium',
                'action' => null,
            ];
        }
        
        // Add insight based on dormancy
        if ($engagementMetrics['is_dormant']) {
            $insights[] = [
                'type' => 're_engagement',
                'title' => 'We Miss You!',
                'message' => 'Come back and scan a product to get back in the game.',
                'priority' => 'high',
                'action' => 'scan_product',
            ];
        }
        
        // Add insight based on power user status
        if ($engagementMetrics['is_power_user']) {
            $insights[] = [
                'type' => 'recognition',
                'title' => 'Power User Status!',
                'message' => 'You\'re among the top earners in our community.',
                'priority' => 'low',
                'action' => null,
            ];
        }
        
        // Add insight based on rank progression
        $currentRank = $this->rankService->getUserRank($user);
        $nextRank = $this->rankService->getNextHigherRank($currentRank);
        
        if ($nextRank) {
            $pointsNeeded = $nextRank->pointsRequired->toInt() - $user->lifetime_points;
            if ($pointsNeeded <= 1000) {
                $insights[] = [
                    'type' => 'motivation',
                    'title' => 'Almost There!',
                    'message' => "Earn {$pointsNeeded} more points to reach {$nextRank->name} rank!",
                    'priority' => 'high',
                    'action' => 'scan_product',
                ];
            }
        }
        
        // Add insight based on goal progress
        $goalProgress = $this->analyticsService->getUserGoalProgress($user);
        foreach ($goalProgress as $goal) {
            if ($goal['progress_percentage'] >= 90 && !$goal['is_achievable']) {
                $insights[] = [
                    'type' => 'motivation',
                    'title' => 'So Close!',
                    'message' => "You're just {$goal['points_needed']} points away from {$goal['goal_name']}!",
                    'priority' => 'high',
                    'action' => 'scan_product',
                ];
            } elseif ($goal['is_achievable']) {
                $insights[] = [
                    'type' => 'celebration',
                    'title' => 'Ready to Redeem!',
                    'message' => "You can now redeem {$goal['goal_name']} with your points.",
                    'priority' => 'medium',
                    'action' => 'redeem_reward',
                ];
            }
        }
        
        return $insights;
    }
}
```

## Laravel-Native Features Utilized

### Caching
- Laravel Cache facade for dashboard data caching
- Cache tags for granular invalidation
- Automatic cache expiration and refresh
- Redis or file-based caching drivers

### Collections
- Laravel Collection methods for data aggregation
- Higher-order messaging for data transformation
- Collection pipelining for complex operations

### Events & Listeners
- Laravel Event system for dashboard updates
- Event discovery for automatic listener registration
- Queued event listeners for performance

### Jobs & Queues
- Laravel Jobs for background analytics processing
- Queue workers for async operations
- Failed job handling and retry logic

### Notifications
- Laravel Notifications for user insights
- Multiple channels (email, SMS, database, push)
- Markdown notification templates

### Validation
- Laravel Form Requests for dashboard requests
- Custom validation rules for dashboard parameters
- Automatic error response formatting

## Business Logic Implementation

### Engagement Scoring Algorithm
```php
// app/Services/EngagementScoringService.php
class EngagementScoringService
{
    public function calculateUserEngagementScore(User $user): int
    {
        // Get user activity data
        $activityData = $this->getUserActivityData($user);
        
        // Calculate weighted score
        $score = 0;
        
        // Activity frequency weight (40%)
        $score += $this->calculateFrequencyScore($activityData) * 0.4;
        
        // Activity diversity weight (30%)
        $score += $this->calculateDiversityScore($activityData) * 0.3;
        
        // Recency weight (20%)
        $score += $this->calculateRecencyScore($activityData) * 0.2;
        
        // Achievement weight (10%)
        $score += $this->calculateAchievementScore($user) * 0.1;
        
        return min(100, max(0, round($score)));
    }
    
    protected function calculateFrequencyScore(array $activityData): int
    {
        $weeklyAverage = $activityData['weekly_actions'] / 4; // Average per week
        $monthlyMax = 20; // Max actions per week for perfect score
        
        return min(100, ($weeklyAverage / $monthlyMax) * 100);
    }
    
    protected function calculateDiversityScore(array $activityData): int
    {
        $actionTypes = count($activityData['unique_action_types']);
        $maxTypes = 5; // Scan, redeem, refer, achieve, social (hypothetical)
        
        return min(100, ($actionTypes / $maxTypes) * 100);
    }
    
    protected function calculateRecencyScore(array $activityData): int
    {
        $daysSinceLastAction = $activityData['days_since_last_action'];
        
        if ($daysSinceLastAction <= 1) {
            return 100;
        } elseif ($daysSinceLastAction <= 3) {
            return 80;
        } elseif ($daysSinceLastAction <= 7) {
            return 60;
        } elseif ($daysSinceLastAction <= 14) {
            return 40;
        } elseif ($daysSinceLastAction <= 30) {
            return 20;
        } else {
            return 0;
        }
    }
    
    protected function calculateAchievementScore(User $user): int
    {
        $unlockedAchievements = $user->unlockedAchievements()->count();
        $totalAchievements = Achievement::active()->count();
        
        if ($totalAchievements === 0) {
            return 0;
        }
        
        return min(100, ($unlockedAchievements / $totalAchievements) * 100);
    }
}
```

## API Resources Implementation

### Dashboard API Resource
```php
// app/Http/Resources/DashboardResource.php
class DashboardResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user' => [
                'id' => $this->user['id'],
                'first_name' => $this->user['first_name'],
                'last_name' => $this->user['last_name'],
                'email' => $this->user['email'],
                'points_balance' => $this->user['points_balance'],
                'lifetime_points' => $this->user['lifetime_points'],
                'current_rank' => $this->user['current_rank'],
                'referral_code' => $this->user['referral_code'],
                'shipping_address' => $this->user['shipping_address'],
            ],
            'engagement' => $this->engagement,
            'recent_activity' => $this->recent_activity,
            'insights' => $this->insights,
            'goal_progress' => $this->goal_progress,
            'upcoming_events' => $this->upcoming_events,
            'last_updated' => $this->last_updated,
        ];
    }
}
```

### History API Resource
```php
// app/Http/Resources/HistoryResource.php
class HistoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'action_type' => $this->action_type,
            'points_change' => $this->points_change,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'formatted_date' => Carbon::parse($this->created_at)->format('M j, Y'),
            'time_ago' => Carbon::parse($this->created_at)->diffForHumans(),
        ];
    }
}
```

## Data Migration Strategy

### From WordPress to Laravel
- Migrate user action logs to action_logs table
- Convert action log meta to structured format
- Preserve historical points changes
- Migrate achievement unlock history
- Maintain redemption history
- Convert timestamps to consistent format

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for caching and queues)
- Eloquent ORM
- Laravel Collections
- Laravel Cache

## Definition of Done
- [ ] User dashboard data is correctly aggregated and formatted
- [ ] User points history is properly retrieved and formatted
- [ ] Engagement metrics are correctly calculated using scoring algorithm
- [ ] Personalized insights are properly generated based on user behavior
- [ ] Goal progress tracking shows accurate completion percentages
- [ ] Dashboard data is properly cached for performance (cache hit ratio > 95%)
- [ ] Recent activity feed shows accurate chronological user actions
- [ ] User rank information is correctly displayed with progression details
- [ ] Adequate test coverage using Laravel testing features (100% of dashboard endpoints)
- [ ] Error handling for edge cases with Laravel exception handling
- [ ] Performance benchmarks met (dashboard response time < 500ms)
- [ ] Cache invalidation works correctly when user data changes
- [ ] Background processing via Laravel queues for analytics calculations
- [ ] Proper validation using Laravel Form Requests
- [ ] Personalized insights provide meaningful recommendations
- [ ] Engagement scoring algorithm provides accurate user classification
- [ ] Dashboard data refreshes appropriately based on TTL settings
- [ ] Historical data visualization shows meaningful trends
- [ ] User milestone achievements are properly recognized and celebrated