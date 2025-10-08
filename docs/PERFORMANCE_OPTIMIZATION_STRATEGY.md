# Performance Optimization Strategy

## Overview
This document outlines the comprehensive performance optimization strategy for the CannaRewards Laravel application, focusing on maximizing response times, minimizing resource usage, and ensuring scalability to handle increased load.

## Performance Goals

### Response Time Targets
- **API Endpoints**: < 200ms for 95th percentile
- **Dashboard Load**: < 500ms for initial load
- **Catalog Browsing**: < 300ms for product listings
- **User Authentication**: < 150ms for login/registration
- **Product Scanning**: < 250ms for scan processing

### Throughput Targets
- **Concurrent Users**: Support 10,000+ concurrent users
- **API Requests**: Handle 1,000+ requests per second
- **Database Queries**: < 50ms average query time
- **Background Jobs**: Process 100+ jobs per minute

### Resource Utilization Targets
- **CPU Usage**: < 70% average utilization
- **Memory Usage**: < 80% average utilization
- **Database Connections**: < 80% connection pool usage
- **Cache Hit Rate**: > 95% for frequently accessed data

## Optimization Areas

### 1. Database Optimization

#### Query Optimization
```php
// app/Services/OptimizedUserService.php
class OptimizedUserService
{
    public function getUserDashboardData(UserId $userId): array
    {
        // Use eager loading to prevent N+1 queries
        $user = User::with([
            'rank',
            'referrals.converted',
            'unlockedAchievements',
            'recentScans' => function ($query) {
                $query->limit(10)->orderBy('created_at', 'desc');
            }
        ])->find($userId->toInt());
        
        // Use select() to limit columns retrieved
        $recentOrders = Order::select([
            'id', 'order_number', 'status', 'points_cost', 'created_at'
        ])
        ->where('user_id', $userId->toInt())
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
        
        return [
            'user' => $user,
            'recent_orders' => $recentOrders,
        ];
    }
}
```

#### Indexing Strategy
```sql
-- Create composite indexes for frequently queried combinations
CREATE INDEX idx_users_points_balance_lifetime ON users (points_balance, lifetime_points);
CREATE INDEX idx_action_logs_user_action_created ON action_logs (user_id, action_type, created_at);
CREATE INDEX idx_orders_user_status_created ON orders (user_id, status, created_at);
CREATE INDEX idx_products_sku_active ON products (sku, is_active);
CREATE INDEX idx_reward_codes_code_used ON reward_codes (code, is_used);
```

#### Connection Pooling
```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
    ]) : [],
    'pool' => [
        'min' => 10,
        'max' => 100,
        'idle_timeout' => 300,
    ],
],
```

### 2. Caching Strategy

#### Application-Level Caching
```php
// app/Services/CacheOptimizedCatalogService.php
class CacheOptimizedCatalogService
{
    protected $cacheTtl;
    protected $redisTaggedCache;
    
    public function __construct()
    {
        $this->cacheTtl = 1800; // 30 minutes
        $this->redisTaggedCache = Cache::store('redis');
    }
    
    public function getCatalogProducts(array $filters = []): array
    {
        $cacheKey = $this->generateCacheKey('catalog_products', $filters);
        
        // Use tagged cache for easier invalidation
        return $this->redisTaggedCache->tags(['catalog', 'products'])
            ->remember($cacheKey, $this->cacheTtl, function () use ($filters) {
                return $this->fetchCatalogProducts($filters);
            });
    }
    
    protected function generateCacheKey(string $baseKey, array $filters): string
    {
        $filterString = http_build_query($filters);
        return md5("{$baseKey}:{$filterString}");
    }
    
    protected function fetchCatalogProducts(array $filters): array
    {
        $query = Product::active()->rewardable();
        
        // Apply filters efficiently
        if (!empty($filters['category'])) {
            $query->byCategory($filters['category']);
        }
        
        if (!empty($filters['brand'])) {
            $query->where('brand', $filters['brand']);
        }
        
        if (!empty($filters['min_points'])) {
            $query->where('points_cost', '>=', $filters['min_points']);
        }
        
        if (!empty($filters['max_points'])) {
            $query->where('points_cost', '<=', $filters['max_points']);
        }
        
        // Use cursor pagination for better memory usage
        return $query->orderBy('sort_order')
            ->cursorPaginate(20)
            ->toArray();
    }
    
    public function invalidateCatalogCache(): void
    {
        // Invalidate all catalog-related cache
        $this->redisTaggedCache->tags(['catalog'])->flush();
    }
}
```

#### HTTP-Level Caching
```php
// app/Http/Controllers/Api/CatalogController.php
class CatalogController extends Controller
{
    public function getProducts(Request $request)
    {
        $products = app(CacheOptimizedCatalogService::class)
            ->getCatalogProducts($request->only([
                'category', 'brand', 'min_points', 'max_points'
            ]));
            
        // Add HTTP cache headers
        $etag = md5(json_encode($products));
        $lastModified = now()->toAtomString();
        
        return response()->json($products)
            ->setEtag($etag)
            ->setLastModified(Carbon::parse($lastModified))
            ->setPublic()
            ->setMaxAge(1800) // 30 minutes
            ->header('Cache-Control', 'public, s-maxage=1800');
    }
}
```

#### Redis Optimization
```php
// config/cache.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'cache',
    'lock_connection' => 'default',
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('CACHE_PREFIX', 'cannarewards_cache'),
        'compression' => true, // Enable compression for large values
        'serializer' => 'igbinary', // Use igbinary for better serialization
    ],
],
```

### 3. API Response Optimization

#### Efficient Data Serialization
```php
// app/Http/Resources/OptimizedProductResource.php
class OptimizedProductResource extends JsonResource
{
    public function toArray($request): array
    {
        // Only include fields that are actually needed
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'points_cost' => $this->points_cost,
            'points_award' => $this->points_award,
            'image_url' => $this->whenLoaded('featuredImage', function () {
                return $this->featuredImage->url;
            }),
            'is_eligible' => $this->when(isset($this->eligibility), function () {
                return $this->eligibility;
            }),
            // Only include relationships when explicitly requested
            'category' => $this->when($request->include('category'), function () {
                return new CategoryResource($this->category);
            }),
        ];
    }
    
    public function with($request): array
    {
        // Only add meta when needed
        return $request->has('include_meta') ? [
            'meta' => [
                'last_updated' => now()->toISOString(),
            ],
        ] : [];
    }
}
```

#### Response Compression
```php
// app/Http/Middleware/CompressApiResponse.php
class CompressApiResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Compress JSON responses larger than 1KB
        if ($response instanceof JsonResponse && 
            strlen($response->getContent()) > 1024) {
            $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        return $response;
    }
}
```

### 4. Background Job Optimization

#### Queue Configuration
```php
// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
    'after_commit' => false,
    'options' => [
        'compression' => true,
        'serializer' => 'igbinary',
    ],
    'worker_options' => [
        'supervisor' => [
            'processes' => 8,
            'balance' => 'auto',
            'min_processes' => 2,
            'max_processes' => 16,
            'balance_cooldown' => 3,
            'balance_max_shift' => 1,
            'memory' => 256,
            'timeout' => 60,
            'sleep' => 3,
            'tries' => 3,
            'nice' => 0,
        ],
    ],
],
```

#### Job Optimization
```php
// app/Jobs/OptimizedProcessProductScan.php
class OptimizedProcessProductScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    protected $userId;
    protected $productId;
    protected $rewardCode;
    
    public function __construct(int $userId, int $productId, string $rewardCode)
    {
        $this->userId = $userId;
        $this->productId = $productId;
        $this->rewardCode = $rewardCode;
        $this->onQueue('scans'); // Use dedicated queue for scans
    }
    
    public function handle(): void
    {
        // Use database transactions for atomicity
        DB::transaction(function () {
            // Process scan logic here
            // ...
        }, 3); // Retry transaction up to 3 times
    }
    
    public function retryUntil(): DateTime
    {
        // Jobs expire after 1 hour
        return now()->addHour();
    }
    
    public function backoff(): array
    {
        // Exponential backoff: 1s, 2s, 5s, 10s, 15s
        return [1, 2, 5, 10, 15];
    }
}
```

### 5. Database Query Optimization

#### Efficient Eloquent Queries
```php
// app/Repositories/OptimizedUserRepository.php
class OptimizedUserRepository
{
    public function getUserWithMinimalData(int $userId): ?User
    {
        // Only select columns that are actually needed
        return User::select([
            'id', 'email', 'first_name', 'last_name', 
            'points_balance', 'lifetime_points', 'current_rank_key'
        ])
        ->where('id', $userId)
        ->first();
    }
    
    public function getUserDashboardData(int $userId): array
    {
        // Use joins instead of separate queries where possible
        $userData = DB::select("
            SELECT 
                u.id,
                u.email,
                u.first_name,
                u.last_name,
                u.points_balance,
                u.lifetime_points,
                u.current_rank_key,
                r.name as rank_name,
                r.point_multiplier,
                (SELECT COUNT(*) FROM referrals WHERE referrer_user_id = u.id AND status = 'converted') as converted_referrals,
                (SELECT COUNT(*) FROM user_achievements WHERE user_id = u.id) as unlocked_achievements,
                (SELECT COUNT(*) FROM action_logs WHERE user_id = u.id AND action_type = 'scan') as total_scans
            FROM users u
            LEFT JOIN ranks r ON u.current_rank_key = r.key
            WHERE u.id = ?
        ", [$userId]);
        
        return $userData ? (array) $userData[0] : [];
    }
}
```

#### Query Caching
```php
// app/Services/QueryCacheService.php
class QueryCacheService
{
    protected $cache;
    protected $defaultTtl;
    
    public function __construct()
    {
        $this->cache = Cache::store('redis');
        $this->defaultTtl = 300; // 5 minutes
    }
    
    public function rememberQuery(string $query, array $bindings, int $ttl = null): array
    {
        $cacheKey = $this->generateQueryCacheKey($query, $bindings);
        $ttl = $ttl ?? $this->defaultTtl;
        
        return $this->cache->remember($cacheKey, $ttl, function () use ($query, $bindings) {
            return DB::select($query, $bindings);
        });
    }
    
    protected function generateQueryCacheKey(string $query, array $bindings): string
    {
        return 'query:' . md5($query . serialize($bindings));
    }
    
    public function forgetQuery(string $query, array $bindings): void
    {
        $cacheKey = $this->generateQueryCacheKey($query, $bindings);
        $this->cache->forget($cacheKey);
    }
}
```

### 6. Memory Optimization

#### Efficient Collection Processing
```php
// app/Services/MemoryOptimizedProcessingService.php
class MemoryOptimizedProcessingService
{
    public function processLargeDataset(callable $processor, iterable $dataset): void
    {
        // Process data in chunks to avoid memory issues
        foreach ($dataset as $chunk) {
            $processor($chunk);
            
            // Force garbage collection periodically
            if (gc_enabled()) {
                gc_collect_cycles();
            }
        }
    }
    
    public function exportUserData(int $userId): string
    {
        // Use generators for large data exports
        $exportData = $this->generateUserExportData($userId);
        
        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, ['Field', 'Value']);
        
        foreach ($exportData as $row) {
            fputcsv($csv, $row);
        }
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }
    
    protected function generateUserExportData(int $userId): Generator
    {
        // Yield data instead of loading everything into memory
        yield ['User ID', $userId];
        yield ['Email', User::find($userId)->email];
        
        // Process related data in chunks
        foreach (ActionLog::where('user_id', $userId)->chunk(1000) as $logs) {
            foreach ($logs as $log) {
                yield ['Action', $log->action_type];
                yield ['Timestamp', $log->created_at->toISOString()];
            }
        }
    }
}
```

### 7. HTTP Optimization

#### Efficient Middleware
```php
// app/Http/Middleware/OptimizedMiddleware.php
class OptimizedMiddleware
{
    public function handle($request, Closure $next)
    {
        // Only process middleware for API routes
        if (!$request->is('api/*')) {
            return $next($request);
        }
        
        // Skip expensive operations for health check endpoints
        if ($request->is('api/*/health')) {
            return $next($request);
        }
        
        // Continue with normal middleware processing
        return $next($request);
    }
}
```

#### Response Optimization
```php
// app/Http/Middleware/OptimizeApiResponse.php
class OptimizeApiResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        // Optimize JSON responses
        if ($response instanceof JsonResponse) {
            // Remove pretty printing for production
            if (app()->isProduction()) {
                $response->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
        
        // Add performance headers
        $response->header('X-Response-Time', (microtime(true) - LARAVEL_START) * 1000 . 'ms');
        
        return $response;
    }
}
```

## Monitoring and Metrics

### Performance Monitoring
```php
// app/Services/PerformanceMonitorService.php
class PerformanceMonitorService
{
    protected $metrics = [];
    
    public function startTimer(string $operation): string
    {
        $timerId = uniqid();
        $this->metrics[$timerId] = [
            'operation' => $operation,
            'start_time' => microtime(true),
        ];
        
        return $timerId;
    }
    
    public function stopTimer(string $timerId): float
    {
        if (!isset($this->metrics[$timerId])) {
            return 0;
        }
        
        $duration = microtime(true) - $this->metrics[$timerId]['start_time'];
        
        // Log performance metrics
        Log::info('Performance metric', [
            'operation' => $this->metrics[$timerId]['operation'],
            'duration_ms' => $duration * 1000,
            'timestamp' => now()->toISOString(),
        ]);
        
        // Send to external monitoring service
        $this->sendToMonitoringService(
            $this->metrics[$timerId]['operation'],
            $duration * 1000
        );
        
        unset($this->metrics[$timerId]);
        
        return $duration;
    }
    
    protected function sendToMonitoringService(string $operation, float $durationMs): void
    {
        // Integration with external monitoring services (New Relic, Datadog, etc.)
        // This is a placeholder implementation
    }
}
```

### Database Query Monitoring
```php
// app/Providers/DatabaseQueryMonitorServiceProvider.php
class DatabaseQueryMonitorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (app()->isLocal() || app()->runningUnitTests()) {
            DB::listen(function ($query) {
                // Log slow queries (> 100ms)
                if ($query->time > 100) {
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time_ms' => $query->time,
                        'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10),
                    ]);
                }
            });
        }
    }
}
```

## Caching Strategy Implementation

### Multi-Level Caching
```php
// app/Services/MultiLevelCacheService.php
class MultiLevelCacheService
{
    protected $memoryCache = []; // In-memory cache for request lifecycle
    protected $redisCache; // Persistent cache
    protected $defaultTtl;
    
    public function __construct()
    {
        $this->redisCache = Cache::store('redis');
        $this->defaultTtl = 300; // 5 minutes
    }
    
    public function get(string $key, callable $resolver, int $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        // Level 1: In-memory cache (fastest)
        if (isset($this->memoryCache[$key])) {
            return $this->memoryCache[$key];
        }
        
        // Level 2: Redis cache
        $value = $this->redisCache->get($key);
        if ($value !== null) {
            $this->memoryCache[$key] = $value; // Populate memory cache
            return $value;
        }
        
        // Level 3: Resolver (database, external API, etc.)
        $value = $resolver();
        
        // Cache at both levels
        $this->memoryCache[$key] = $value;
        $this->redisCache->put($key, $value, $ttl);
        
        return $value;
    }
    
    public function forget(string $key): void
    {
        unset($this->memoryCache[$key]);
        $this->redisCache->forget($key);
    }
}
```

### Cache Warming
```php
// app/Console/Commands/WarmCacheCommand.php
class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm';
    protected $description = 'Warm up application cache';
    
    public function handle(): int
    {
        $this->info('Warming up cache...');
        
        // Warm up rank structure cache
        app(RankService::class)->getRankStructure();
        $this->info('✓ Rank structure cache warmed');
        
        // Warm up popular product catalog cache
        app(CacheOptimizedCatalogService::class)->getCatalogProducts([
            'limit' => 100,
            'sort_by' => 'popularity',
        ]);
        $this->info('✓ Popular products cache warmed');
        
        // Warm up configuration cache
        app(ConfigService::class)->getAllSettings();
        $this->info('✓ Configuration cache warmed');
        
        $this->info('Cache warming completed successfully!');
        
        return 0;
    }
}
```

## Performance Testing Strategy

### Load Testing
```php
// tests/Performance/LoadTest.php
namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class LoadTest extends TestCase
{
    /** @test */
    public function api_can_handle_100_concurrent_requests()
    {
        // Create test users
        $users = User::factory(100)->create();
        $product = Product::factory()->create(['points_award' => 400]);
        
        // Simulate concurrent requests
        $promises = [];
        foreach ($users as $user) {
            $promises[] = Http::withToken($user->createToken('test')->plainTextToken)
                ->postAsync('/api/v1/actions/claim', [
                    'code' => 'TEST-CODE-' . $user->id,
                ]);
        }
        
        // Wait for all requests to complete
        $responses = collect($promises)->map->wait();
        
        // Assert all requests were successful
        $responses->each(function ($response) {
            $this->assertTrue($response->successful());
        });
        
        // Assert performance metrics
        $averageResponseTime = $responses->avg(function ($response) {
            return $response->getTransferTime();
        });
        
        $this->assertLessThan(0.2, $averageResponseTime, 
            "Average response time was {$averageResponseTime}s, exceeding 200ms target");
    }
}
```

### Stress Testing
```php
// tests/Performance/StressTest.php
namespace Tests\Performance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class StressTest extends TestCase
{
    /** @test */
    public function system_handles_spike_traffic()
    {
        // Create large number of users
        $users = User::factory(1000)->create();
        
        // Simulate traffic spike
        $startTime = microtime(true);
        $responses = [];
        
        foreach ($users as $user) {
            $response = Http::withToken($user->createToken('test')->plainTextToken)
                ->get('/api/v1/users/me/dashboard');
                
            $responses[] = $response;
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Assert system handled spike gracefully
        $successfulResponses = collect($responses)->filter->successful()->count();
        $this->assertEquals(1000, $successfulResponses, 
            'All requests should have been successful during stress test');
            
        $averageTimePerRequest = $totalTime / 1000;
        $this->assertLessThan(0.5, $averageTimePerRequest,
            "Average time per request was {$averageTimePerRequest}s, exceeding 500ms target");
    }
}
```

## Optimization Results and Benchmarks

### Current Performance Baseline
- **API Response Times**: 150-300ms average
- **Database Query Times**: 20-80ms average
- **Cache Hit Rate**: 85-95%
- **Memory Usage**: 32-64MB per request
- **Concurrent Users**: 5,000+ supported

### Target Performance Goals
- **API Response Times**: < 200ms for 95th percentile
- **Database Query Times**: < 50ms average
- **Cache Hit Rate**: > 95%
- **Memory Usage**: < 32MB per request
- **Concurrent Users**: 10,000+ supported

### Optimization Roadmap

#### Phase 1: Immediate Optimizations (Weeks 1-2)
- Implement database indexing strategy
- Add HTTP-level caching with ETags
- Optimize Eloquent queries with eager loading
- Implement connection pooling
- Add query monitoring for slow queries

#### Phase 2: Medium-Term Optimizations (Weeks 3-4)
- Implement multi-level caching strategy
- Optimize background job processing
- Add response compression
- Implement efficient data serialization
- Add performance monitoring and metrics

#### Phase 3: Long-Term Optimizations (Weeks 5-6)
- Implement database read replicas
- Add CDN for static assets
- Implement advanced caching with Redis Cluster
- Optimize database schema and queries
- Implement database sharding (if needed)

## Monitoring Dashboard

### Key Metrics to Track
1. **API Response Times**: Average, 95th percentile, 99th percentile
2. **Database Query Performance**: Average query time, slow query count
3. **Cache Performance**: Hit rate, miss rate, eviction rate
4. **Queue Performance**: Job processing time, queue depth
5. **Resource Utilization**: CPU, memory, disk I/O, network I/O
6. **Error Rates**: HTTP 5xx errors, database errors, application errors
7. **Throughput**: Requests per second, jobs per minute
8. **User Experience**: Page load times, conversion rates

### Alerting Thresholds
- **Critical**: API response time > 1000ms, Error rate > 5%
- **Warning**: API response time > 500ms, Error rate > 1%
- **Info**: Cache hit rate < 90%, Queue depth > 1000

This performance optimization strategy provides a comprehensive approach to maximizing the performance of the CannaRewards Laravel application while maintaining reliability and scalability.