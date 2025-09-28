# Laravel Vertical Slice 10: Event & Notification System

## Overview
This vertical slice implements the event broadcasting and notification system including domain event handling, user notifications, and third-party integrations using Laravel's native features, replacing WordPress event system and CDP integration.

## Key Components

### Laravel Components
- Laravel Events for domain event broadcasting
- Laravel Listeners for event handling
- Laravel Notifications for user communications
- Laravel Mail for email delivery
- Laravel Broadcasting for real-time updates
- Laravel Queue for background processing
- Laravel Scheduling for periodic tasks
- Laravel Logging for event tracking

### Domain Entities
- DomainEvent (Base event class)
- UserNotification (Notification model)
- NotificationPreference (User notification settings)
- ThirdPartyEvent (External event mapping)

### Event Types
- ProductScanned
- UserRegistered
- PointsGranted
- UserRankChanged
- RewardRedeemed
- AchievementUnlocked
- ReferralConverted
- ReferralInviteeSignedUp

### Laravel Services
- EventService (Event management)
- NotificationService (Notification dispatching)
- CdpIntegrationService (Third-party integration)
- WebhookService (External webhook delivery)

### Laravel Models
- UserNotification (Eloquent model for notifications)
- NotificationPreference (Eloquent model for preferences)
- EventLog (Eloquent model for event tracking)

### Laravel Events
- All domain events listed above
- NotificationEvents (NotificationSent, NotificationFailed)
- SystemEvents (SystemHealthCheck, DataExportCompleted)

### Laravel Listeners
- AchievementUnlockListener
- RankUpdateListener
- ReferralBonusListener
- CdpEventListener
- EmailNotificationListener

### Laravel Notifications
- WelcomeNotification
- AchievementUnlockedNotification
- RankChangedNotification
- ReferralBonusNotification
- OrderConfirmationNotification
- PasswordResetNotification

### Laravel Jobs
- ProcessDomainEvent
- SendUserNotification
- DeliverWebhook
- SyncWithCdp

## Implementation Details

### Domain Events Structure
```php
// app/Events/ProductScanned.php
namespace App\Events;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductScanned
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public User $user,
        public Product $product,
        public bool $isFirstScan,
        public string $timestamp
    ) {
        //
    }
    
    public function broadcastOn(): array
    {
        return [];
    }
    
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'is_first_scan' => $this->isFirstScan,
            'timestamp' => $this->timestamp,
        ];
    }
}
```

### Event Service Implementation
```php
// app/Services/EventService.php
namespace App\Services;

use App\Events\DomainEvent;
use App\Models\EventLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService
{
    public function logEvent(DomainEvent $event): void
    {
        try {
            DB::transaction(function () use ($event) {
                EventLog::create([
                    'event_type' => class_basename($event),
                    'user_id' => $event->user->id ?? null,
                    'payload' => $this->serializeEvent($event),
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Failed to log event', [
                'event' => get_class($event),
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    protected function serializeEvent(DomainEvent $event): array
    {
        $payload = [];
        
        // Serialize public properties
        foreach ((new \ReflectionClass($event))->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();
            $payload[$propertyName] = $property->getValue($event);
        }
        
        return $payload;
    }
    
    public function getEventHistory(int $userId, int $limit = 50): array
    {
        return EventLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
```

### Notification Service Implementation
```php
// app/Services/NotificationService.php
namespace App\Services;

use App\Models\User;
use App\Models\NotificationPreference;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    public function sendToUser(User $user, Notification $notification): void
    {
        // Check user's notification preferences
        if (!$this->shouldSendNotification($user, $notification)) {
            return;
        }
        
        // Send notification
        NotificationFacade::send($user, $notification);
    }
    
    public function sendToUsers(array $users, Notification $notification): void
    {
        // Filter users based on preferences
        $filteredUsers = array_filter($users, function ($user) use ($notification) {
            return $this->shouldSendNotification($user, $notification);
        });
        
        if (!empty($filteredUsers)) {
            NotificationFacade::send($filteredUsers, $notification);
        }
    }
    
    protected function shouldSendNotification(User $user, Notification $notification): bool
    {
        $notificationType = $this->getNotificationType($notification);
        
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $notificationType)
            ->first();
            
        if (!$preference) {
            // Default to enabled if no preference set
            return true;
        }
        
        return $preference->is_enabled;
    }
    
    protected function getNotificationType(Notification $notification): string
    {
        return class_basename($notification);
    }
    
    public function updateUserPreferences(User $user, array $preferences): void
    {
        foreach ($preferences as $notificationType => $isEnabled) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $notificationType,
                ],
                [
                    'is_enabled' => (bool) $isEnabled,
                ]
            );
        }
    }
}
```

## Event Handling Workflow

### Domain Event Processing
1. Domain operations fire Laravel Events (e.g., ProductScanned)
2. EventService logs the event to event_logs table
3. Listeners react to events:
   - AchievementUnlockListener checks for achievement criteria
   - RankUpdateListener recalculates user rank
   - ReferralBonusListener processes referral conversions
   - CdpEventListener syncs with third-party systems
4. Notifications are sent to users via NotificationService
5. Webhooks are delivered to external systems via WebhookService

### Event Listener Implementation
```php
// app/Listeners/AchievementUnlockListener.php
namespace App\Listeners;

use App\Events\ProductScanned;
use App\Events\UserRankChanged;
use App\Services\AchievementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AchievementUnlockListener implements ShouldQueue
{
    use InteractsWithQueue;
    
    protected $achievementService;
    
    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }
    
    public function handle($event): void
    {
        // Extract user and context from event
        $user = $event->user ?? null;
        if (!$user) {
            return;
        }
        
        // Build context for achievement evaluation
        $context = $this->buildContextFromEvent($event);
        
        // Evaluate achievements triggered by this event
        $this->achievementService->evaluateAchievements($user, get_class($event), $context);
    }
    
    protected function buildContextFromEvent($event): array
    {
        $context = [
            'event_type' => get_class($event),
            'timestamp' => now()->toISOString(),
        ];
        
        // Add event-specific context
        if ($event instanceof ProductScanned) {
            $context['product'] = [
                'id' => $event->product->id,
                'sku' => $event->product->sku,
                'name' => $event->product->name,
            ];
            $context['is_first_scan'] = $event->isFirstScan;
        } elseif ($event instanceof UserRankChanged) {
            $context['new_rank'] = $event->newRank->key;
            $context['previous_rank'] = $event->previousRank?->key;
        }
        
        return $context;
    }
}
```

### Notification Implementation
```php
// app/Notifications/AchievementUnlockedNotification.php
namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class AchievementUnlockedNotification extends Notification
{
    use Queueable;
    
    public function __construct(
        protected Achievement $achievement
    ) {
        //
    }
    
    public function via($notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Achievement Unlocked!')
            ->line("Congratulations! You've unlocked the '{$this->achievement->title}' achievement.")
            ->line("You've been awarded {$this->achievement->points_reward} bonus points!")
            ->action('View Achievement', url('/achievements/' . $this->achievement->key))
            ->line('Keep up the great work!');
    }
    
    public function toDatabase($notifiable): array
    {
        return [
            'achievement_key' => $this->achievement->key,
            'achievement_title' => $this->achievement->title,
            'points_reward' => $this->achievement->points_reward,
            'icon_url' => $this->achievement->icon_url,
        ];
    }
    
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'achievement_key' => $this->achievement->key,
            'achievement_title' => $this->achievement->title,
            'points_reward' => $this->achievement->points_reward,
            'icon_url' => $this->achievement->icon_url,
        ]);
    }
    
    public function toArray($notifiable): array
    {
        return [
            'achievement_key' => $this->achievement->key,
            'achievement_title' => $this->achievement->title,
            'points_reward' => $this->achievement->points_reward,
            'icon_url' => $this->achievement->icon_url,
        ];
    }
}
```

## Third-Party Integration

### CDP Integration Service
```php
// app/Services/CdpIntegrationService.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CdpIntegrationService
{
    protected $apiKey;
    protected $apiUrl;
    protected $isEnabled;
    
    public function __construct()
    {
        $this->apiKey = config('services.cdp.api_key');
        $this->apiUrl = config('services.cdp.api_url');
        $this->isEnabled = config('services.cdp.enabled', false);
    }
    
    public function trackEvent(User $user, string $eventName, array $properties = []): void
    {
        if (!$this->isEnabled || empty($this->apiKey) || empty($this->apiUrl)) {
            return;
        }
        
        try {
            $payload = [
                'user_id' => $user->id,
                'email' => $user->email,
                'event' => $eventName,
                'properties' => array_merge($properties, [
                    'timestamp' => now()->toISOString(),
                    'source' => 'cannarewards_laravel',
                ]),
            ];
            
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->apiUrl}/track", $payload);
                
            if (!$response->successful()) {
                Log::warning('CDP event tracking failed', [
                    'event' => $eventName,
                    'user_id' => $user->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CDP event tracking exception', [
                'event' => $eventName,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    public function updateUserProfile(User $user, array $traits = []): void
    {
        if (!$this->isEnabled || empty($this->apiKey) || empty($this->apiUrl)) {
            return;
        }
        
        try {
            $payload = [
                'user_id' => $user->id,
                'email' => $user->email,
                'traits' => array_merge($traits, [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'points_balance' => $user->points_balance,
                    'lifetime_points' => $user->lifetime_points,
                    'current_rank_key' => $user->current_rank_key,
                    'created_at' => $user->created_at->toISOString(),
                ]),
            ];
            
            $response = Http::withToken($this->apiKey)
                ->timeout(30)
                ->post("{$this->apiUrl}/identify", $payload);
                
            if (!$response->successful()) {
                Log::warning('CDP user profile update failed', [
                    'user_id' => $user->id,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('CDP user profile update exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

### Webhook Service
```php
// app/Services/WebhookService.php
namespace App\Services;

use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    public function deliverWebhook(string $eventType, array $payload): void
    {
        $endpoints = WebhookEndpoint::where('is_active', true)
            ->whereJsonContains('events', $eventType)
            ->get();
            
        foreach ($endpoints as $endpoint) {
            $this->deliverToEndpoint($endpoint, $eventType, $payload);
        }
    }
    
    protected function deliverToEndpoint(WebhookEndpoint $endpoint, string $eventType, array $payload): void
    {
        try {
            $signature = $this->generateSignature($payload, $endpoint->secret);
            
            $response = Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $eventType,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($endpoint->url, $payload);
            
            if (!$response->successful()) {
                Log::warning('Webhook delivery failed', [
                    'endpoint_id' => $endpoint->id,
                    'url' => $endpoint->url,
                    'event_type' => $eventType,
                    'response_status' => $response->status(),
                ]);
                
                // Increment failure count
                $endpoint->increment('failure_count');
                
                // Disable endpoint if too many failures
                if ($endpoint->failure_count >= config('webhooks.max_failures', 10)) {
                    $endpoint->update(['is_active' => false]);
                }
            } else {
                // Reset failure count on success
                if ($endpoint->failure_count > 0) {
                    $endpoint->update(['failure_count' => 0]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Webhook delivery exception', [
                'endpoint_id' => $endpoint->id,
                'url' => $endpoint->url,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
            
            $endpoint->increment('failure_count');
        }
    }
    
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadJson = json_encode($payload);
        return hash_hmac('sha256', $payloadJson, $secret);
    }
}
```

## Laravel-Native Features Utilized

### Events & Listeners
- Laravel Event system for domain events
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Notifications
- Laravel Notifications for user communications
- Multiple channels (email, database, broadcast)
- Markdown notification templates
- Notification throttling and grouping

### Broadcasting
- Laravel Broadcasting for real-time updates
- Redis or Pusher integration
- Private channels for user-specific updates
- Presence channels for collaborative features

### Queues & Jobs
- Laravel Jobs for background processing
- Queue workers for async operations
- Failed job handling and retry logic
- Job chaining for complex workflows

### Scheduling
- Laravel Scheduler for periodic tasks
- Cron job integration
- Task monitoring and logging
- Health checks for scheduled tasks

### Logging
- Laravel Logging for event tracking
- Custom log channels for different event types
- Log rotation and archiving
- Structured logging for analytics

## Business Logic Implementation

### Event Context Building
```php
// app/Services/EventContextService.php
namespace App\Services;

use App\Models\User;
use App\Models\Product;

class EventContextService
{
    public function buildUserSnapshot(User $user): array
    {
        return [
            'identity' => [
                'user_id' => $user->id,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'created_at' => $user->created_at->toISOString(),
            ],
            'economy' => [
                'points_balance' => $user->points_balance,
                'lifetime_points' => $user->lifetime_points,
            ],
            'status' => [
                'rank_key' => $user->current_rank_key,
                'referral_code' => $user->referral_code,
            ],
            'engagement' => [
                'total_scans' => $this->getUserScanCount($user),
                'total_redemptions' => $this->getUserRedemptionCount($user),
                'total_achievements_unlocked' => $user->unlockedAchievements()->count(),
            ],
        ];
    }
    
    public function buildProductSnapshot(Product $product): array
    {
        return [
            'identity' => [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'product_name' => $product->name,
            ],
            'economy' => [
                'points_award' => $product->points_award,
                'points_cost' => $product->points_cost,
            ],
            'taxonomy' => [
                'product_line' => $product->category?->name,
                'product_form' => $product->product_form,
                'strain_name' => $product->strain_type,
            ],
        ];
    }
    
    protected function getUserScanCount(User $user): int
    {
        return $user->actionLogs()
            ->where('action_type', 'scan')
            ->count();
    }
    
    protected function getUserRedemptionCount(User $user): int
    {
        return $user->orders()
            ->where('is_canna_redemption', true)
            ->count();
    }
}
```

### Notification Preferences
```php
// app/Models/NotificationPreference.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'notification_type',
        'is_enabled',
        'channels',
    ];
    
    protected $casts = [
        'is_enabled' => 'boolean',
        'channels' => 'array',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## Event-Driven Architecture Implementation

### Event Bus Pattern
```php
// app/Services/EventBusService.php
namespace App\Services;

use Illuminate\Support\Facades\Event;

class EventBusService
{
    public function publish($event): void
    {
        Event::dispatch($event);
    }
    
    public function subscribe(string $event, $listener): void
    {
        Event::listen($event, $listener);
    }
    
    public function unsubscribe(string $event, $listener): void
    {
        // Laravel doesn't directly support unsubscribing
        // This would require custom implementation
    }
}
```

### Event Sourcing (Optional)
```php
// app/Services/EventStoreService.php
namespace App\Services;

use App\Models\EventStore;
use Illuminate\Support\Facades\DB;

class EventStoreService
{
    public function storeEvent(string $eventType, array $payload, int $userId = null): void
    {
        DB::transaction(function () use ($eventType, $payload, $userId) {
            EventStore::create([
                'event_type' => $eventType,
                'payload' => $payload,
                'user_id' => $userId,
                'occurred_at' => now(),
            ]);
        });
    }
    
    public function getEventsByUser(int $userId, int $limit = 100): array
    {
        return EventStore::where('user_id', $userId)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
    
    public function replayEvents(callable $handler, string $eventType = null): void
    {
        $query = EventStore::orderBy('occurred_at');
        
        if ($eventType) {
            $query->where('event_type', $eventType);
        }
        
        $query->chunk(1000, function ($events) use ($handler) {
            foreach ($events as $event) {
                $handler($event);
            }
        });
    }
}
```

## API Integration

### Notification API Endpoints
```php
// app/Http/Controllers/Api/NotificationController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);
            
        return NotificationResource::collection($notifications);
    }
    
    public function markAsRead(Request $request, string $notificationId)
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();
            
        $notification->markAsRead();
        
        return response()->json(['success' => true]);
    }
    
    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);
        
        return response()->json(['success' => true]);
    }
    
    public function preferences(Request $request)
    {
        $preferences = $request->user()
            ->notificationPreferences()
            ->get();
            
        return response()->json($preferences);
    }
    
    public function updatePreferences(Request $request)
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*.type' => 'required|string',
            'preferences.*.enabled' => 'required|boolean',
        ]);
        
        foreach ($validated['preferences'] as $preference) {
            $request->user()->notificationPreferences()->updateOrCreate(
                ['notification_type' => $preference['type']],
                ['is_enabled' => $preference['enabled']]
            );
        }
        
        return response()->json(['success' => true]);
    }
}
```

## Data Migration Strategy

### From WordPress Event System to Laravel
- Migrate existing event logs to event_logs table
- Convert WordPress action hooks to Laravel events
- Migrate CDP event tracking to new integration service
- Preserve existing notification history
- Convert webhook configurations to new system
- Maintain event correlation and causality

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for queues and broadcasting)
- Mail server (SMTP, SendGrid, etc.)
- Third-party CDP service (Customer.io, Segment, etc.)

## Definition of Done
- [ ] Domain events are properly fired for all user actions
- [ ] Event listeners correctly process events and trigger appropriate actions
- [ ] User notifications are sent via multiple channels (email, in-app, push)
- [ ] Notification preferences are properly respected and enforced
- [ ] Third-party CDP integration correctly receives and processes events
- [ ] Webhook delivery works reliably with signature verification
- [ ] Event logging properly tracks all domain events with context
- [ ] Event replay functionality works for rebuilding state from events
- [ ] Adequate test coverage for event handling and notification logic
- [ ] Error handling for edge cases with proper fallbacks
- [ ] Performance benchmarks met (event processing < 100ms)
- [ ] Background processing via Laravel queues for notifications
- [ ] Proper validation using Laravel Form Requests for API endpoints
- [ ] Authorization policies enforced for notification management
- [ ] Event deduplication prevents duplicate processing
- [ ] Event ordering maintained for causal relationships
- [ ] Dead letter queue handling for failed event processing
- [ ] Event schema versioning for backward compatibility
- [ ] Real-time updates work via Laravel Broadcasting
- [ ] Notification delivery receipts tracked for reliability
- [ ] Webhook delivery retries work with exponential backoff
- [ ] CDP integration handles rate limiting and retries
- [ ] Event context includes complete user and product snapshots
- [ ] Notification templates are properly localized and branded
- [ ] User notification history is properly maintained and queryable
- [ ] System health monitoring for event processing subsystems
- [ ] Audit logging for all notification and event operations
- [ ] GDPR compliance for user notification data handling