# Laravel Vertical Slice 10: Events & Notifications

## Overview
This vertical slice implements the event system and notification framework including domain events, event listeners, and user communications using Laravel's native event and notification features.

## Key Components

### Laravel Components
- Laravel Events for domain events
- Laravel Listeners for event handling
- Laravel Notifications for user communications
- Laravel Jobs for background event processing
- Laravel Mail for email delivery
- Laravel Broadcasting for real-time updates
- Laravel Queues for async processing
- Laravel Cache for event deduplication

### Domain Entities
- DomainEvent (Base event class)
- UserId (Value Object)
- NotificationPreference (Eloquent Model)

### Event Types
- UserRegistered
- ProductScanned
- PointsGranted
- UserRankChanged
- RewardRedeemed
- AchievementUnlocked
- ReferralConverted
- ReferralInviteeSignedUp
- UserAchievementProgress
- UserMilestoneReached

### Laravel Services
- EventService (Event coordination)
- NotificationService (Notification management)
- EmailService (Email delivery)
- PushNotificationService (Push notifications)

### Laravel Models
- User (extended with notification preferences)
- NotificationPreference (User notification settings)

### Laravel Events
- All domain events listed above
- SystemHealthEvent
- AdminAlertEvent

### Laravel Listeners
- SendWelcomeEmailListener
- AwardFirstScanBonusListener
- GrantPointsForScanListener
- CheckForAchievementsListener
- ProcessReferralConversionListener
- NotifyRankChangeListener
- SendOrderConfirmationListener
- SendAchievementUnlockedListener

### Laravel Jobs
- ProcessDomainEvent
- SendUserNotification
- SendAdminAlert
- UpdateUserEngagementMetrics

### Laravel Notifications
- WelcomeNotification
- FirstScanBonusNotification
- PointsAwardedNotification
- RankChangedNotification
- OrderConfirmationNotification
- AchievementUnlockedNotification
- ReferralBonusAwardedNotification
- ReferralConversionNotification

## Implementation Details

### Base Event Structure
```php
// app/Events/DomainEvent.php
namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class DomainEvent
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}
    
    abstract public function getEventName(): string;
    
    public function getPayload(): array
    {
        return [
            'event_name' => $this->getEventName(),
            'occurred_at' => $this->occurredAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
```

### User Registration Event
```php
// app/Events/UserRegistered.php
namespace App\Events;

use App\Models\User;

class UserRegistered extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly ?string $referralCode = null,
        \DateTimeImmutable $occurredAt = null
    ) {
        parent::__construct($occurredAt ?? new \DateTimeImmutable());
    }
    
    public function getEventName(): string
    {
        return 'user.registered';
    }
    
    public function getPayload(): array
    {
        return array_merge(parent::getPayload(), [
            'user_id' => $this->user->id,
            'user_email' => $this->user->email,
            'referral_code' => $this->referralCode,
        ]);
    }
}
```

### Product Scan Event
```php
// app/Events/ProductScanned.php
namespace App\Events;

use App\Models\User;
use App\Models\Product;

class ProductScanned extends DomainEvent
{
    public function __construct(
        public readonly User $user,
        public readonly Product $product,
        public readonly bool $isFirstScan = false,
        public readonly ?string $rewardCode = null,
        \DateTimeImmutable $occurredAt = null
    ) {
        parent::__construct($occurredAt ?? new \DateTimeImmutable());
    }
    
    public function getEventName(): string
    {
        return 'product.scanned';
    }
    
    public function getPayload(): array
    {
        return array_merge(parent::getPayload(), [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'product_sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'is_first_scan' => $this->isFirstScan,
            'reward_code' => $this->rewardCode,
        ]);
    }
}
```

### Event Service Implementation
```php
// app/Services/EventService.php
namespace App\Services;

use App\Events\DomainEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class EventService
{
    protected $deduplicationWindow;
    
    public function __construct()
    {
        $this->deduplicationWindow = config('events.deduplication_window', 60); // 60 seconds
    }
    
    public function dispatch(DomainEvent $event): void
    {
        // Check for duplicate events within deduplication window
        if ($this->isDuplicateEvent($event)) {
            Log::info('Duplicate event detected, skipping', [
                'event' => $event->getEventName(),
                'payload' => $event->getPayload(),
            ]);
            return;
        }
        
        // Record event for deduplication
        $this->recordEvent($event);
        
        // Dispatch event
        Event::dispatch($event);
        
        // Log event for analytics
        Log::info('Domain event dispatched', [
            'event' => $event->getEventName(),
            'payload' => $event->getPayload(),
        ]);
    }
    
    protected function isDuplicateEvent(DomainEvent $event): bool
    {
        $eventKey = $this->getEventKey($event);
        return Cache::has("event_duplicate_{$eventKey}");
    }
    
    protected function recordEvent(DomainEvent $event): void
    {
        $eventKey = $this->getEventKey($event);
        Cache::put(
            "event_duplicate_{$eventKey}", 
            true, 
            $this->deduplicationWindow
        );
    }
    
    protected function getEventKey(DomainEvent $event): string
    {
        $payload = $event->getPayload();
        unset($payload['occurred_at']); // Remove timestamp for deduplication
        
        return md5($event->getEventName() . json_encode($payload));
    }
    
    public function replayEvent(DomainEvent $event): void
    {
        // Replay event without deduplication for replay scenarios
        Event::dispatch($event);
    }
}
```

## Event Handling Workflow

### Event Dispatching
1. Domain operations fire domain events via EventService
2. EventService checks for duplicates using cache-based deduplication
3. If not duplicate, event is recorded and dispatched via Laravel Event system
4. Laravel's event dispatcher notifies all registered listeners
5. Listeners process events and may dispatch additional events
6. All events are logged for analytics and debugging

### Event Listener Implementation
```php
// app/Listeners/SendWelcomeEmailListener.php
namespace App\Listeners;

use App\Events\UserRegistered;
use App\Notifications\WelcomeNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeEmailListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    public function __construct()
    {
        $this->onQueue('notifications');
    }
    
    public function handle(UserRegistered $event): void
    {
        try {
            // Send welcome notification
            $event->user->notify(new WelcomeNotification($event->referralCode));
            
            // Log successful notification
            \Log::info('Welcome email sent', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send welcome email', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw to trigger queue retry
            throw $e;
        }
    }
}
```

### First Scan Bonus Listener
```php
// app/Listeners/AwardFirstScanBonusListener.php
namespace App\Listeners;

use App\Events\ProductScanned;
use App\Jobs\AwardFirstScanBonusJob;
use App\Services\EconomyService;

class AwardFirstScanBonusListener
{
    protected $economyService;
    
    public function __construct(EconomyService $economyService)
    {
        $this->economyService = $economyService;
    }
    
    public function handle(ProductScanned $event): void
    {
        // Only process first scans
        if (!$event->isFirstScan) {
            return;
        }
        
        // Check if user is eligible for first scan bonus
        if (!$this->isEligibleForFirstScanBonus($event->user)) {
            return;
        }
        
        // Dispatch job to award bonus
        AwardFirstScanBonusJob::dispatch(
            $event->user->id,
            $event->product->id
        )->onQueue('economy');
    }
    
    protected function isEligibleForFirstScanBonus($user): bool
    {
        // User must not have received welcome gift already
        return !$user->hasReceivedWelcomeGift();
    }
}
```

## Notification System Implementation

### Base Notification Structure
```php
// app/Notifications/BaseNotification.php
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;
    
    protected $priority = 'normal';
    protected $maxRetries = 3;
    
    public function __construct()
    {
        $this->onQueue('notifications');
    }
    
    public function via($notifiable): array
    {
        $channels = [];
        
        // Check user notification preferences
        if ($this->shouldSendViaEmail($notifiable)) {
            $channels[] = 'mail';
        }
        
        if ($this->shouldSendViaDatabase($notifiable)) {
            $channels[] = 'database';
        }
        
        if ($this->shouldSendViaPush($notifiable)) {
            $channels[] = 'firebase'; // or other push service
        }
        
        return $channels;
    }
    
    abstract protected function shouldSendViaEmail($notifiable): bool;
    abstract protected function shouldSendViaDatabase($notifiable): bool;
    abstract protected function shouldSendViaPush($notifiable): bool;
    
    public function toArray($notifiable): array
    {
        return [
            'type' => static::class,
            'data' => $this->toDatabase($notifiable),
            'created_at' => now(),
        ];
    }
    
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(30);
    }
}
```

### Welcome Notification
```php
// app/Notifications/WelcomeNotification.php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends BaseNotification
{
    public function __construct(
        protected ?string $referralCode = null
    ) {
        parent::__construct();
    }
    
    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name', 'CannaRewards');
        
        return (new MailMessage)
            ->subject("Welcome to {$appName}!")
            ->greeting("Welcome, {$notifiable->first_name}!")
            ->line("Thank you for joining {$appName}.")
            ->line('Start earning rewards by scanning products.')
            ->when($this->referralCode, function ($mail) {
                $mail->line("You joined using referral code: **{$this->referralCode}**");
            })
            ->action('Get Started', url('/'))
            ->line('Thank you for choosing our platform!');
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Welcome!',
            'message' => 'Welcome to CannaRewards. Start earning rewards today!',
            'icon' => '🎉',
            'action_url' => '/',
            'type' => 'welcome',
        ];
    }
    
    protected function shouldSendViaEmail($notifiable): bool
    {
        return $notifiable->prefersEmailNotifications() && 
               $notifiable->hasVerifiedEmail();
    }
    
    protected function shouldSendViaDatabase($notifiable): bool
    {
        return true; // Always send to database for in-app notifications
    }
    
    protected function shouldSendViaPush($notifiable): bool
    {
        return $notifiable->prefersPushNotifications();
    }
}
```

### Points Awarded Notification
```php
// app/Notifications/PointsAwardedNotification.php
namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class PointsAwardedNotification extends BaseNotification
{
    public function __construct(
        protected int $pointsAwarded,
        protected string $reason,
        protected ?int $newBalance = null
    ) {
        parent::__construct();
    }
    
    public function toMail($notifiable): MailMessage
    {
        $pointsName = config('cannarewards.points_name', 'Points');
        
        return (new MailMessage)
            ->subject("{$pointsName} Awarded!")
            ->greeting('Great job!')
            ->line("You've been awarded **{$this->pointsAwarded} {$pointsName}** for:")
            ->line($this->reason)
            ->when($this->newBalance, function ($mail) use ($pointsName) {
                $mail->line("Your new balance: **{$this->newBalance} {$pointsName}**");
            })
            ->action('View Account', url('/account'))
            ->line('Keep scanning to earn more rewards!');
    }
    
    public function toDatabase($notifiable): array
    {
        $pointsName = config('cannarewards.points_name', 'Points');
        
        return [
            'title' => "{$this->pointsAwarded} {$pointsName} Awarded!",
            'message' => "You earned {$this->pointsAwarded} {$pointsName} for: {$this->reason}",
            'icon' => '💰',
            'action_url' => '/account',
            'type' => 'points_awarded',
            'points_awarded' => $this->pointsAwarded,
            'new_balance' => $this->newBalance,
        ];
    }
    
    protected function shouldSendViaEmail($notifiable): bool
    {
        // Only send email for significant point awards (> 100 points)
        return $notifiable->prefersEmailNotifications() && 
               $notifiable->hasVerifiedEmail() &&
               $this->pointsAwarded > 100;
    }
    
    protected function shouldSendViaDatabase($notifiable): bool
    {
        return true; // Always send to database for in-app notifications
    }
    
    protected function shouldSendViaPush($notifiable): bool
    {
        return $notifiable->prefersPushNotifications();
    }
}
```

## Notification Preferences Management

### User Notification Preferences
```php
// app/Models/NotificationPreference.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'enabled',
        'frequency',
    ];
    
    protected $casts = [
        'enabled' => 'boolean',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### User Extension for Notifications
```php
// app/Models/User.php (extension)
class User extends Authenticatable
{
    // ... existing code ...
    
    public function notificationPreferences()
    {
        return $this->hasMany(NotificationPreference::class);
    }
    
    public function prefersEmailNotifications(): bool
    {
        return $this->notificationPreferences()
            ->where('channel', 'email')
            ->where('enabled', true)
            ->exists();
    }
    
    public function prefersPushNotifications(): bool
    {
        return $this->notificationPreferences()
            ->where('channel', 'push')
            ->where('enabled', true)
            ->exists();
    }
    
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }
    
    public function updateNotificationPreference(string $type, string $channel, bool $enabled): void
    {
        $this->notificationPreferences()->updateOrCreate(
            [
                'notification_type' => $type,
                'channel' => $channel,
            ],
            [
                'enabled' => $enabled,
            ]
        );
    }
}
```

## Laravel-Native Features Utilized

### Event System
- Laravel Event system for domain events
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Notifications
- Laravel Notifications for user communications
- Multiple channels (email, database, push, SMS)
- Markdown notification templates
- Notification throttling and grouping

### Queues & Jobs
- Laravel Jobs for background processing
- Queue workers for async operations
- Failed job handling and retry logic
- Job chaining for complex workflows

### Broadcasting
- Laravel Broadcasting for real-time event streaming
- WebSocket integration for live updates
- Private channels for user-specific events
- Presence channels for online status

### Cache
- Laravel Cache facade for event deduplication
- Cache tags for granular invalidation
- Automatic cache expiration and refresh

### Mail
- Laravel Mail for email delivery
- Multiple mail drivers (SMTP, SES, Mailgun)
- Queue-based email sending for performance
- Markdown email templates

## Business Logic Implementation

### Event Processing Rules
```php
// app/Services/EventProcessingService.php
namespace App\Services;

use App\Events\DomainEvent;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EventProcessingService
{
    public function processUserEvent(DomainEvent $event, User $user): void
    {
        // Log event for analytics
        $this->logEvent($event, $user);
        
        // Update user engagement metrics
        $this->updateUserEngagement($user, $event);
        
        // Check for user milestones
        $this->checkForMilestones($user, $event);
        
        // Update user activity timestamp
        $this->updateUserActivity($user);
    }
    
    protected function logEvent(DomainEvent $event, User $user): void
    {
        Log::channel('events')->info('User event processed', [
            'event_name' => $event->getEventName(),
            'user_id' => $user->id,
            'payload' => $event->getPayload(),
            'processed_at' => now()->toISOString(),
        ]);
    }
    
    protected function updateUserEngagement(User $user, DomainEvent $event): void
    {
        // Increment engagement counter
        $user->increment('engagement_score', $this->getEngagementValue($event));
        
        // Update last activity timestamp
        $user->touch();
    }
    
    protected function getEngagementValue(DomainEvent $event): int
    {
        return match($event->getEventName()) {
            'product.scanned' => 10,
            'points.granted' => 5,
            'achievement.unlocked' => 20,
            'reward.redeemed' => 15,
            'user.rank.changed' => 25,
            default => 1,
        };
    }
    
    protected function checkForMilestones(User $user, DomainEvent $event): void
    {
        // Check for significant milestones
        $milestones = [
            100 => 'first_hundred_points',
            1000 => 'thousand_points_earned',
            10 => 'tenth_scan',
            50 => 'fiftieth_scan',
        ];
        
        foreach ($milestones as $threshold => $milestone) {
            if ($this->userReachedMilestone($user, $threshold, $event)) {
                event(new UserMilestoneReached($user, $milestone, $threshold));
            }
        }
    }
    
    protected function userReachedMilestone(User $user, int $threshold, DomainEvent $event): bool
    {
        // Implementation depends on the specific milestone type
        return match($threshold) {
            100, 1000 => $user->lifetime_points >= $threshold && 
                         ($user->lifetime_points - $this->getEventPoints($event)) < $threshold,
            10, 50 => $this->getUserScanCount($user) >= $threshold &&
                      ($this->getUserScanCount($user) - $this->getEventScans($event)) < $threshold,
            default => false,
        };
    }
    
    protected function getEventPoints(DomainEvent $event): int
    {
        if ($event instanceof \App\Events\PointsGranted) {
            return $event->pointsAwarded;
        }
        return 0;
    }
    
    protected function getEventScans(DomainEvent $event): int
    {
        if ($event instanceof \App\Events\ProductScanned) {
            return 1;
        }
        return 0;
    }
    
    protected function getUserScanCount(User $user): int
    {
        return $user->actionLogs()->where('action_type', 'scan')->count();
    }
    
    protected function updateUserActivity(User $user): void
    {
        $user->forceFill([
            'last_activity_at' => now(),
        ])->save();
    }
}
```

### Notification Throttling
```php
// app/Services/NotificationThrottlingService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationThrottlingService
{
    protected $throttleLimits;
    
    public function __construct()
    {
        $this->throttleLimits = config('notifications.throttle_limits', [
            'points_awarded' => 5, // Max 5 points notifications per hour
            'achievement_unlocked' => 3, // Max 3 achievement notifications per hour
        ]);
    }
    
    public function canSendNotification(User $user, string $notificationType): bool
    {
        $key = "notification_throttle_{$user->id}_{$notificationType}";
        $limit = $this->throttleLimits[$notificationType] ?? 10;
        
        $count = Cache::get($key, 0);
        
        if ($count >= $limit) {
            return false;
        }
        
        // Increment count and set expiration
        if ($count === 0) {
            Cache::put($key, 1, 3600); // 1 hour
        } else {
            Cache::increment($key);
        }
        
        return true;
    }
    
    public function recordNotificationSent(User $user, string $notificationType): void
    {
        $key = "notification_sent_{$user->id}_{$notificationType}";
        Cache::put($key, now()->toISOString(), 86400); // 24 hours
    }
    
    public function wasRecentlyNotified(User $user, string $notificationType): bool
    {
        $key = "notification_sent_{$user->id}_{$notificationType}";
        return Cache::has($key);
    }
}
```

## Event Listeners Implementation

### Achievement Unlocking Listener
```php
// app/Listeners/CheckForAchievementsListener.php
namespace App\Listeners;

use App\Events\ProductScanned;
use App\Events\PointsGranted;
use App\Events\UserRankChanged;
use App\Services\AchievementService;

class CheckForAchievementsListener
{
    protected $achievementService;
    
    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }
    
    public function handle($event): void
    {
        $user = null;
        $eventType = null;
        $context = [];
        
        // Determine event type and extract context
        switch (get_class($event)) {
            case ProductScanned::class:
                $user = $event->user;
                $eventType = 'product_scanned';
                $context = [
                    'user' => [
                        'id' => $user->id,
                        'lifetime_points' => $user->lifetime_points,
                        'current_rank' => $user->current_rank_key,
                    ],
                    'product' => [
                        'id' => $event->product->id,
                        'sku' => $event->product->sku,
                    ],
                    'is_first_scan' => $event->isFirstScan,
                ];
                break;
                
            case PointsGranted::class:
                $user = $event->user;
                $eventType = 'points_granted';
                $context = [
                    'user' => [
                        'id' => $user->id,
                        'lifetime_points' => $user->lifetime_points,
                        'current_rank' => $user->current_rank_key,
                    ],
                    'points_awarded' => $event->pointsAwarded,
                    'new_balance' => $event->newBalance,
                ];
                break;
                
            case UserRankChanged::class:
                $user = $event->user;
                $eventType = 'user_rank_changed';
                $context = [
                    'user' => [
                        'id' => $user->id,
                        'new_rank' => $event->newRank->key,
                        'previous_rank' => $event->previousRank?->key,
                    ],
                ];
                break;
        }
        
        if ($user && $eventType) {
            $this->achievementService->evaluateAchievements($user, $eventType, $context);
        }
    }
}
```

### Referral Processing Listener
```php
// app/Listeners/ProcessReferralConversionListener.php
namespace App\Listeners;

use App\Events\ProductScanned;
use App\Services\ReferralService;

class ProcessReferralConversionListener
{
    protected $referralService;
    
    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }
    
    public function handle(ProductScanned $event): void
    {
        // Only process first scans for referrals
        if (!$event->isFirstScan) {
            return;
        }
        
        // Check if user was referred
        if (!$event->user->referredBy) {
            return;
        }
        
        // Process referral conversion
        $this->referralService->processConversion($event->user);
    }
}
```

## Data Migration Strategy

### From WordPress Events to Laravel Events
- Migrate existing event logs to Laravel-compatible format
- Convert WordPress action hooks to Laravel events
- Preserve event timestamps and metadata
- Maintain event relationships and causality
- Convert WordPress cron jobs to Laravel scheduled tasks

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for queues and caching)
- Email service (SMTP, SES, Mailgun)
- Eloquent ORM

## Definition of Done
- [ ] Domain events are properly fired for all business operations
- [ ] Event listeners correctly process domain events and trigger appropriate actions
- [ ] User notifications are properly sent via multiple channels (email, push, in-app)
- [ ] Notification preferences are respected for all communication channels
- [ ] Event deduplication prevents duplicate processing
- [ ] Event logging provides comprehensive audit trail for debugging
- [ ] Background job processing via Laravel queues for optimal performance
- [ ] Adequate test coverage for all event types and listeners (100% event coverage)
- [ ] Error handling for edge cases with proper exception handling
- [ ] Performance benchmarks met (event processing < 50ms)
- [ ] Notification throttling prevents spamming users
- [ ] Real-time event broadcasting works for connected clients
- [ ] Event replay capability allows for reprocessing historical events
- [ ] Proper validation using Laravel Form Requests
- [ ] Cache efficiency for event deduplication (hit ratio > 95%)
- [ ] Email delivery works correctly with proper templates
- [ ] Push notifications work correctly with proper formatting
- [ ] Database notifications are properly stored and retrievable
- [ ] Event-based achievement unlocking works correctly
- [ ] Referral conversion processing works via events
- [ ] Rank progression is properly triggered by events
- [ ] User engagement metrics are updated via events
- [ ] Admin alerts are properly sent for system events
- [ ] User milestone achievements are properly detected and notified
- [ ] Event correlation allows for reconstructing user journeys
- [ ] Event schema evolution allows for future event additions
- [ ] Event monitoring provides visibility into system health
- [ ] Failed event processing has proper retry and alerting mechanisms