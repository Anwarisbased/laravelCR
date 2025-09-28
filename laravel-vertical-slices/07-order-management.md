# Laravel Vertical Slice 07: Order Management

## Overview
This vertical slice implements the order management system including redemption processing, order history, and shipping management using Laravel's native features, replacing WooCommerce order integration.

## Key Components

### Laravel Components
- Eloquent Order model
- Eloquent OrderItem model
- Laravel Events for order events
- Laravel Jobs for order processing
- Laravel Notifications for order communications
- Laravel Validation for order validation
- Laravel Policies for order authorization
- Laravel Queue for background processing

### Domain Entities
- Order (Eloquent Model)
- OrderId (Value Object)
- OrderItem (Eloquent Model)
- ProductId (Value Object)
- UserId (Value Object)
- Points (Value Object)

### API Endpoints
- `POST /api/v1/actions/redeem` - Redeem rewards with shipping details
- `GET /api/v1/users/me/orders` - Get user's redemption history
- `GET /api/v1/orders/{id}` - Get specific order details
- `GET /api/v1/orders/{id}/tracking` - Get order tracking information (if applicable)

### Laravel Services
- OrderService (order management)
- RedemptionService (redemption processing)
- OrderProcessingService (order fulfillment)
- ShippingService (shipping management)

### Laravel Models
- Order (Eloquent model for orders)
- OrderItem (Eloquent model for order items)
- User (extended with order relationships)

### Laravel Events
- RewardRedeemed
- OrderCreated
- OrderStatusChanged
- OrderShipped

### Laravel Jobs
- ProcessRedemption
- CreateOrderFromRedemption
- UpdateOrderStatus
- SendOrderConfirmation

### Laravel Notifications
- OrderConfirmationNotification
- OrderShippedNotification
- OrderStatusUpdateNotification

### Laravel Resources
- OrderResource (API resource for single order)
- OrderCollection (API resource for order collections)

## Implementation Details

### Order Model Structure
```php
// app/Models/Order.php
class Order extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
        'points_cost',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address_1',
        'shipping_address_2',
        'shipping_city',
        'shipping_state',
        'shipping_postcode',
        'shipping_country',
        'shipping_phone',
        'tracking_number',
        'shipped_at',
        'delivered_at',
        'is_canna_redemption',
        'notes',
        'meta_data',
    ];
    
    protected $casts = [
        'points_cost' => 'integer',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_canna_redemption' => 'boolean',
        'meta_data' => 'array',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    // Accessors
    public function getOrderNumberAttribute($value)
    {
        return $value ?? 'CR-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
    
    public function getStatusAttribute($value)
    {
        return $value ?? 'processing';
    }
    
    // Scopes
    public function scopeRedemptions($query)
    {
        return $query->where('is_canna_redemption', true);
    }
    
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
    
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    
    // Methods
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }
    
    public function getFormattedShippingAddressAttribute(): string
    {
        $parts = [
            $this->shipping_first_name . ' ' . $this->shipping_last_name,
            $this->shipping_address_1,
            $this->shipping_address_2,
            $this->shipping_city . ', ' . $this->shipping_state . ' ' . $this->shipping_postcode,
            $this->shipping_country,
        ];
        
        return implode("\n", array_filter($parts));
    }
    
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }
    
    public function markAsShipped(string $trackingNumber = null): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
        ]);
        
        event(new OrderShipped($this));
    }
}
```

### Order Item Model Structure
```php
// app/Models/OrderItem.php
class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'points_value',
        'meta_data',
    ];
    
    protected $casts = [
        'quantity' => 'integer',
        'points_value' => 'integer',
        'meta_data' => 'array',
    ];
    
    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // Methods
    public function getFormattedProductNameAttribute(): string
    {
        return $this->product_name ?? $this->product?->name ?? 'Unknown Product';
    }
}
```

### Redemption Service Implementation
```php
// app/Services/RedemptionService.php
class RedemptionService
{
    protected $economyService;
    protected $productRepository;
    protected $orderService;
    protected $userPolicyService;
    
    public function __construct(
        EconomyService $economyService,
        ProductRepository $productRepository,
        OrderService $orderService,
        UserPolicyService $userPolicyService
    ) {
        $this->economyService = $economyService;
        $this->productRepository = $productRepository;
        $this->orderService = $orderService;
        $this->userPolicyService = $userPolicyService;
    }
    
    public function processRedemption(User $user, int $productId, array $shippingDetails): Order
    {
        // Validate shipping details
        $this->validateShippingDetails($shippingDetails);
        
        // Find product
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new ModelNotFoundException("Product with ID {$productId} not found.");
        }
        
        // Check user eligibility
        $this->validateUserEligibility($user, $product);
        
        // Check policy requirements
        $this->enforcePolicies($user, $product);
        
        // Deduct points from user
        $this->deductPoints($user, $product);
        
        // Create order
        $order = $this->createOrderFromRedemption($user, $product, $shippingDetails);
        
        // Fire event
        event(new RewardRedeemed($user, $product, $order));
        
        return $order;
    }
    
    protected function validateShippingDetails(array $shippingDetails): void
    {
        $requiredFields = ['first_name', 'last_name', 'address_1', 'city', 'state', 'postcode'];
        
        foreach ($requiredFields as $field) {
            if (empty($shippingDetails[$field])) {
                throw new ValidationException("Shipping {$field} is required.");
            }
        }
        
        // Validate postcode format (simple US format)
        if (!preg_match('/^\d{5}(-\d{4})?$/', $shippingDetails['postcode'])) {
            throw new ValidationException("Invalid postcode format.");
        }
        
        // Validate state format (2-letter US state)
        if (!preg_match('/^[A-Z]{2}$/', $shippingDetails['state'])) {
            throw new ValidationException("Invalid state format.");
        }
    }
    
    protected function validateUserEligibility(User $user, Product $product): void
    {
        // Check if user can afford redemption
        if ($user->points_balance < $product->points_cost) {
            throw new ValidationException('Insufficient points for redemption.', 402);
        }
        
        // Check rank requirements
        if (!$this->userPolicyService->meetsRankRequirement($user, $product)) {
            throw new ValidationException("You must be rank '{$product->required_rank_key}' or higher to redeem this item.", 403);
        }
    }
    
    protected function enforcePolicies(User $user, Product $product): void
    {
        // Check if user can afford redemption (duplicate check for extra safety)
        if (!$this->userPolicyService->canAffordRedemption($user, $product)) {
            throw new ValidationException('Insufficient points.', 402);
        }
        
        // Check rank requirement (duplicate check for extra safety)
        if (!$this->userPolicyService->meetsRankRequirement($user, $product)) {
            throw new ValidationException("You must be rank '{$product->required_rank_key}' or higher to redeem this item.", 403);
        }
    }
    
    protected function deductPoints(User $user, Product $product): void
    {
        $command = new GrantPointsCommand(
            UserId::fromInt($user->id),
            Points::fromInt(-$product->points_cost),
            "Redeemed: {$product->name}"
        );
        
        $this->economyService->handle($command);
    }
    
    protected function createOrderFromRedemption(User $user, Product $product, array $shippingDetails): Order
    {
        return DB::transaction(function () use ($user, $product, $shippingDetails) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'processing',
                'points_cost' => $product->points_cost,
                'shipping_first_name' => $shippingDetails['first_name'],
                'shipping_last_name' => $shippingDetails['last_name'],
                'shipping_address_1' => $shippingDetails['address_1'],
                'shipping_address_2' => $shippingDetails['address_2'] ?? null,
                'shipping_city' => $shippingDetails['city'],
                'shipping_state' => $shippingDetails['state'],
                'shipping_postcode' => $shippingDetails['postcode'],
                'shipping_country' => $shippingDetails['country'] ?? 'US',
                'shipping_phone' => $shippingDetails['phone'] ?? null,
                'is_canna_redemption' => true,
                'notes' => 'Redeemed with CannaRewards points.',
            ]);
            
            // Create order item
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => 1,
                'points_value' => $product->points_cost,
            ]);
            
            // Update user's shipping address
            $user->update([
                'shipping_first_name' => $shippingDetails['first_name'],
                'shipping_last_name' => $shippingDetails['last_name'],
                'shipping_address_1' => $shippingDetails['address_1'],
                'shipping_address_2' => $shippingDetails['address_2'] ?? null,
                'shipping_city' => $shippingDetails['city'],
                'shipping_state' => $shippingDetails['state'],
                'shipping_postcode' => $shippingDetails['postcode'],
                'shipping_country' => $shippingDetails['country'] ?? 'US',
            ]);
            
            return $order;
        });
    }
}
```

## Order Processing Workflow

### Redemption Processing
1. User submits redemption request via `POST /api/v1/actions/redeem`
2. Laravel Form Request validates shipping details
3. RedemptionService validates user eligibility and product availability
4. Policy enforcement ensures user can afford and meets rank requirements
5. Points are deducted from user's balance via EconomyService
6. Order is created with status "processing"
7. Order item is created linking to redeemed product
8. User's shipping address is updated
9. RewardRedeemed event is fired
10. User receives confirmation via API response

### Order Confirmation
```php
// app/Jobs/SendOrderConfirmation.php
class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $order;
    
    public function __construct(Order $order)
    {
        $this->order = $order;
    }
    
    public function handle(): void
    {
        // Send order confirmation to user
        $this->order->user->notify(new OrderConfirmationNotification($this->order));
        
        // Log order creation for analytics
        Log::info('Order confirmation sent', [
            'order_id' => $this->order->id,
            'user_id' => $this->order->user->id,
            'points_cost' => $this->order->points_cost,
        ]);
    }
}
```

### Order Status Management
```php
// app/Services/OrderService.php
class OrderService
{
    public function getUserOrders(User $user, int $limit = 50): Collection
    {
        return Order::redemptions()
            ->byUser($user)
            ->with('items.product')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }
    
    public function getOrderDetails(User $user, int $orderId): Order
    {
        $order = Order::redemptions()
            ->byUser($user)
            ->with('items.product')
            ->findOrFail($orderId);
            
        return $order;
    }
    
    public function updateOrderStatus(Order $order, string $newStatus, string $trackingNumber = null): void
    {
        $oldStatus = $order->status;
        
        $order->update([
            'status' => $newStatus,
            'tracking_number' => $trackingNumber,
        ]);
        
        if ($newStatus === 'shipped' && !$order->shipped_at) {
            $order->update(['shipped_at' => now()]);
        }
        
        if ($newStatus === 'delivered' && !$order->delivered_at) {
            $order->update(['delivered_at' => now()]);
        }
        
        // Fire event
        event(new OrderStatusChanged($order, $oldStatus, $newStatus));
        
        // Send notification to user
        if (in_array($newStatus, ['shipped', 'delivered'])) {
            $order->user->notify(new OrderStatusUpdateNotification($order));
        }
    }
}
```

## Laravel-Native Features Utilized

### Database Transactions
- Laravel DB transactions for order creation atomicity
- Rollback on failure for data consistency
- Nested transactions for complex operations

### Events & Listeners
- Laravel Event system for order lifecycle
- Event discovery for automatic listener registration
- Queued event listeners for performance
- Event broadcasting for real-time updates

### Jobs & Queues
- Laravel Jobs for background order processing
- Queue workers for async operations
- Failed job handling and retry logic
- Job chaining for complex workflows

### Notifications
- Laravel Notifications for user communications
- Multiple channels (email, SMS, database)
- Markdown notification templates
- Notification throttling

### Validation
- Laravel Form Requests for order validation
- Custom validation rules for shipping addresses
- Automatic error response formatting

### Policies
- Laravel Policies for order authorization
- Fine-grained access control
- Resource-based permissions

## Business Logic Implementation

### Order Status Management
```php
// app/Enums/OrderStatus.php
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    
    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::SHIPPED => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }
}
```

### Order Processing Rules
```php
// app/Services/OrderProcessingService.php
class OrderProcessingService
{
    public function processNewRedemption(Order $order): void
    {
        // Validate order
        if (!$order->is_canna_redemption) {
            throw new InvalidArgumentException('Order is not a CannaRewards redemption.');
        }
        
        // Validate items
        if ($order->items->isEmpty()) {
            throw new InvalidArgumentException('Order must have at least one item.');
        }
        
        // Process fulfillment (could integrate with shipping provider APIs)
        $this->processFulfillment($order);
        
        // Update order status
        $order->update(['status' => 'processing']);
        
        // Send confirmation
        SendOrderConfirmation::dispatch($order);
        
        // Log for analytics
        Log::info('Redemption order processed', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'points_cost' => $order->points_cost,
        ]);
    }
    
    protected function processFulfillment(Order $order): void
    {
        // This would integrate with actual shipping providers in a real implementation
        // For now, we just log that fulfillment would happen
        
        Log::info('Order fulfillment initiated', [
            'order_id' => $order->id,
            'fulfillment_partner' => config('cannarewards.fulfillment_partner', 'manual'),
        ]);
    }
}
```

## API Resources Implementation

### Order API Resource
```php
// app/Http/Resources/OrderResource.php
class OrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_display' => OrderStatus::from($this->status)?->getDisplayName(),
            'points_cost' => $this->points_cost,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'shipping_address' => [
                'first_name' => $this->shipping_first_name,
                'last_name' => $this->shipping_last_name,
                'address_1' => $this->shipping_address_1,
                'address_2' => $this->shipping_address_2,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postcode' => $this->shipping_postcode,
                'country' => $this->shipping_country,
                'phone' => $this->shipping_phone,
            ],
            'tracking_number' => $this->tracking_number,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Order Item API Resource
```php
// app/Http/Resources/OrderItemResource.php
class OrderItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->formatted_product_name,
            'product_sku' => $this->product_sku,
            'quantity' => $this->quantity,
            'points_value' => $this->points_value,
            'product_image' => $this->product?->image_urls[0] ?? null,
        ];
    }
}
```

## Data Migration Strategy

### From WordPress/WooCommerce to Laravel
- Migrate WooCommerce orders to orders table
- Convert order meta for CannaRewards-specific data
- Migrate order items to order_items table
- Preserve order status and timestamps
- Maintain user order associations
- Convert shipping address data to normalized format
- Preserve tracking information if available

## Dependencies
- Laravel Framework
- Database (MySQL/PostgreSQL)
- Redis (for queues)
- Eloquent ORM
- Laravel Events
- Laravel Notifications

## Definition of Done
- [ ] User can successfully redeem reward products with appropriate points deduction
- [ ] System properly validates user has sufficient points before redemption
- [ ] System properly enforces rank requirements for product redemptions
- [ ] Orders are correctly created in database with proper status tracking
- [ ] User's shipping address is properly updated during redemption
- [ ] Order history is correctly retrievable via API endpoints
- [ ] Order details are properly formatted with all relevant information
- [ ] Order status changes are properly tracked and logged
- [ ] User receives appropriate notifications for order events
- [ ] All operations are properly logged for analytics and debugging
- [ ] Adequate test coverage using Laravel testing features (100% of order endpoints)
- [ ] Error handling for edge cases with proper HTTP status codes
- [ ] Performance benchmarks met (redemption processing < 200ms)
- [ ] Background processing via Laravel queues for order confirmation emails
- [ ] Proper validation using Laravel Form Requests
- [ ] Authorization policies enforced for order access
- [ ] Database transactions ensure data consistency during order creation
- [ ] Order numbering follows consistent format
- [ ] Shipping address validation properly rejects invalid addresses
- [ ] Points deduction and order creation are atomic operations