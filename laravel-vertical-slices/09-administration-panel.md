# Laravel Vertical Slice 09: Administration Panel

## Overview
This vertical slice implements the administration panel including configuration management, merchant tools, and reporting dashboards using Laravel's native features, replacing WordPress admin interface.

## Key Components

### Laravel Components
- Laravel Nova for admin interface (preferred)
- Laravel Sail for development environment
- Laravel Horizon for queue monitoring
- Laravel Telescope for debugging
- Laravel Envoy for deployment
- Laravel Policies for admin authorization
- Laravel Validation for admin forms
- Laravel Notifications for admin alerts

### Domain Entities
- AdminUser (User with admin roles)
- ConfigurationSetting (Application settings)
- Report (Analytics reports)
- MerchantTool (Merchant-specific tools)

### Admin Sections
- Brand Settings Configuration
- Rank Management
- Achievement Management
- Product Catalog Management
- User Management
- Order Management
- Reporting Dashboard
- Merchant Tools
- System Configuration

### Laravel Services
- AdminService (admin operations)
- ConfigurationService (settings management)
- ReportService (analytics reporting)
- MerchantToolService (merchant tools)

### Laravel Models
- User (extended with admin relationships)
- Product (extended with admin management)
- Rank (extended with admin management)
- Achievement (extended with admin management)

### Laravel Policies
- AdminAccessPolicy
- UserManagementPolicy
- ProductManagementPolicy
- ConfigurationPolicy

### Laravel Notifications
- AdminAlertNotification
- ReportGenerationNotification
- SystemHealthNotification

## Implementation Details

### Admin Interface Options

#### Option 1: Laravel Nova (Recommended)
```php
// app/Nova/User.php
namespace App\Nova;

use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class User extends Resource
{
    public static $model = \App\Models\User::class;
    
    public static $title = 'email';
    
    public static $search = [
        'id', 'email', 'first_name', 'last_name',
    ];
    
    public function fields(NovaRequest $request)
    {
        return [
            ID::make()->sortable(),
            
            Text::make('First Name')
                ->sortable()
                ->rules('required', 'max:255'),
                
            Text::make('Last Name')
                ->sortable()
                ->rules('required', 'max:255'),
                
            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:255')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}'),
                
            Number::make('Points Balance', 'points_balance')
                ->sortable(),
                
            Number::make('Lifetime Points', 'lifetime_points')
                ->sortable(),
                
            Text::make('Current Rank Key', 'current_rank_key'),
            
            Text::make('Referral Code', 'referral_code'),
            
            Boolean::make('Marketing Consent', 'marketing_consent'),
            
            DateTime::make('Created At'),
        ];
    }
}
```

#### Option 2: Custom Admin Panel
```php
// app/Http/Controllers/Admin/DashboardController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReportService;

class DashboardController extends Controller
{
    protected $reportService;
    
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }
    
    public function index()
    {
        $stats = $this->reportService->getAdminDashboardStats();
        
        return view('admin.dashboard', compact('stats'));
    }
    
    public function users()
    {
        return view('admin.users.index');
    }
    
    public function products()
    {
        return view('admin.products.index');
    }
}
```

### Configuration Management
```php
// app/Models/ConfigurationSetting.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigurationSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'description',
        'type',
        'is_encrypted',
    ];
    
    protected $casts = [
        'is_encrypted' => 'boolean',
    ];
    
    // Accessors
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted) {
            return decrypt($value);
        }
        
        // Handle JSON values
        if ($this->type === 'json' && is_string($value)) {
            return json_decode($value, true);
        }
        
        return $value;
    }
    
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted) {
            $this->attributes['value'] = encrypt($value);
        } elseif ($this->type === 'json' && is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
```

### Configuration Service
```php
// app/Services/ConfigurationService.php
namespace App\Services;

use App\Models\ConfigurationSetting;
use Illuminate\Support\Facades\Cache;

class ConfigurationService
{
    protected $cacheTtl;
    
    public function __construct()
    {
        $this->cacheTtl = config('cache.config_ttl', 3600); // 1 hour
    }
    
    public function get(string $key, $default = null)
    {
        return Cache::remember("config_{$key}", $this->cacheTtl, function () use ($key, $default) {
            $setting = ConfigurationSetting::where('key', $key)->first();
            
            return $setting ? $setting->value : $default;
        });
    }
    
    public function set(string $key, $value, array $options = []): void
    {
        $defaults = [
            'group' => 'general',
            'description' => '',
            'type' => 'string',
            'is_encrypted' => false,
        ];
        
        $options = array_merge($defaults, $options);
        
        ConfigurationSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $options['group'],
                'description' => $options['description'],
                'type' => $options['type'],
                'is_encrypted' => $options['is_encrypted'],
            ]
        );
        
        // Clear cache
        Cache::forget("config_{$key}");
        
        // Clear group cache if applicable
        if (!empty($options['group'])) {
            Cache::forget("config_group_{$options['group']}");
        }
    }
    
    public function getGroup(string $group): array
    {
        return Cache::remember("config_group_{$group}", $this->cacheTtl, function () use ($group) {
            return ConfigurationSetting::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });
    }
    
    public function getBrandSettings(): array
    {
        return [
            'frontend_url' => $this->get('brand.frontend_url', config('app.url')),
            'support_email' => $this->get('brand.support_email', 'support@example.com'),
            'welcome_reward_product_id' => (int) $this->get('brand.welcome_reward_product_id', 0),
            'referral_signup_gift_id' => (int) $this->get('brand.referral_signup_gift_id', 0),
            'referral_banner_text' => $this->get('brand.referral_banner_text', '🎁 Earn More By Inviting Your Friends'),
            'points_name' => $this->get('brand.points_name', 'Points'),
            'rank_name' => $this->get('brand.rank_name', 'Rank'),
            'welcome_header' => $this->get('brand.welcome_header', 'Welcome, {firstName}'),
            'scan_cta' => $this->get('brand.scan_cta', 'Scan Product'),
        ];
    }
    
    public function getThemeSettings(): array
    {
        return [
            'primary_font' => $this->get('theme.primary_font', 'Inter'),
            'radius' => $this->get('theme.radius', '0.5rem'),
            'background' => $this->get('theme.background', '0 0% 100%'),
            'foreground' => $this->get('theme.foreground', '222.2 84% 4.9%'),
            'card' => $this->get('theme.card', '0 0% 100%'),
            'primary' => $this->get('theme.primary', '222.2 47.4% 11.2%'),
            'primary_foreground' => $this->get('theme.primary_foreground', '210 40% 98%'),
            'secondary' => $this->get('theme.secondary', '210 40% 96.1%'),
            'destructive' => $this->get('theme.destructive', '0 84.2% 60.2%'),
        ];
    }
    
    public function clearCache(): void
    {
        Cache::flush();
    }
}
```

## Admin Features Implementation

### Brand Settings Management
```php
// app/Http/Controllers/Admin/BrandSettingsController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandSettingsRequest;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;

class BrandSettingsController extends Controller
{
    protected $configService;
    
    public function __construct(ConfigurationService $configService)
    {
        $this->configService = $configService;
    }
    
    public function index()
    {
        $settings = $this->configService->getBrandSettings();
        
        return view('admin.settings.brand', compact('settings'));
    }
    
    public function update(UpdateBrandSettingsRequest $request)
    {
        $validated = $request->validated();
        
        foreach ($validated as $key => $value) {
            $this->configService->set("brand.{$key}", $value);
        }
        
        return redirect()->back()->with('success', 'Brand settings updated successfully.');
    }
}
```

### Rank Management
```php
// app/Http/Controllers/Admin/RankController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rank;
use App\Http\Requests\Admin\StoreRankRequest;
use App\Http\Requests\Admin\UpdateRankRequest;

class RankController extends Controller
{
    public function index()
    {
        $ranks = Rank::orderBy('points_required')->get();
        
        return view('admin.ranks.index', compact('ranks'));
    }
    
    public function create()
    {
        return view('admin.ranks.create');
    }
    
    public function store(StoreRankRequest $request)
    {
        $validated = $request->validated();
        
        Rank::create($validated);
        
        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank created successfully.');
    }
    
    public function edit(Rank $rank)
    {
        return view('admin.ranks.edit', compact('rank'));
    }
    
    public function update(UpdateRankRequest $request, Rank $rank)
    {
        $validated = $request->validated();
        
        $rank->update($validated);
        
        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank updated successfully.');
    }
    
    public function destroy(Rank $rank)
    {
        if ($rank->key === 'member') {
            return redirect()->back()
                ->with('error', 'Cannot delete the base member rank.');
        }
        
        $rank->delete();
        
        return redirect()->route('admin.ranks.index')
            ->with('success', 'Rank deleted successfully.');
    }
}
```

### Achievement Management
```php
// app/Http/Controllers/Admin/AchievementController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Http\Requests\Admin\StoreAchievementRequest;

class AchievementController extends Controller
{
    public function index()
    {
        $achievements = Achievement::orderBy('sort_order')->get();
        
        return view('admin.achievements.index', compact('achievements'));
    }
    
    public function create()
    {
        return view('admin.achievements.create');
    }
    
    public function store(StoreAchievementRequest $request)
    {
        $validated = $request->validated();
        
        // Handle conditions JSON
        if (isset($validated['conditions'])) {
            $validated['conditions'] = json_encode($validated['conditions']);
        }
        
        Achievement::create($validated);
        
        return redirect()->route('admin.achievements.index')
            ->with('success', 'Achievement created successfully.');
    }
    
    public function edit(Achievement $achievement)
    {
        return view('admin.achievements.edit', compact('achievement'));
    }
    
    public function update(StoreAchievementRequest $request, Achievement $achievement)
    {
        $validated = $request->validated();
        
        // Handle conditions JSON
        if (isset($validated['conditions'])) {
            $validated['conditions'] = json_encode($validated['conditions']);
        }
        
        $achievement->update($validated);
        
        return redirect()->route('admin.achievements.index')
            ->with('success', 'Achievement updated successfully.');
    }
}
```

## Merchant Tools Implementation

### QR Code Generator
```php
// app/Http/Controllers/Admin/MerchantToolsController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MerchantToolService;
use Illuminate\Http\Request;

class MerchantToolsController extends Controller
{
    protected $merchantToolService;
    
    public function __construct(MerchantToolService $merchantToolService)
    {
        $this->merchantToolService = $merchantToolService;
    }
    
    public function qrCodeGenerator()
    {
        return view('admin.tools.qr-generator');
    }
    
    public function generateQrCodes(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
        ]);
        
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        
        $codes = $this->merchantToolService->generateQrCodes($productId, $quantity);
        
        return response()->json([
            'success' => true,
            'message' => "{$quantity} codes generated successfully.",
            'codes' => $codes,
        ]);
    }
    
    public function downloadQrCodesCsv(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:10000',
        ]);
        
        $productId = $request->input('product_id');
        $quantity = $request->input('quantity');
        
        $codes = $this->merchantToolService->generateQrCodes($productId, $quantity);
        
        $csv = $this->merchantToolService->generateQrCodesCsv($codes);
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="cannarewards-codes.csv"');
    }
}
```

### Merchant Tool Service
```php
// app/Services/MerchantToolService.php
namespace App\Services;

use App\Models\Product;
use App\Models\RewardCode;
use Illuminate\Support\Str;

class MerchantToolService
{
    public function generateQrCodes(int $productId, int $quantity): array
    {
        $product = Product::findOrFail($productId);
        $sku = $product->sku;
        
        $generatedCodes = [];
        
        for ($i = 0; $i < $quantity; $i++) {
            $uniquePart = strtoupper(Str::random(8));
            $newCode = strtoupper($sku) . '-' . $uniquePart;
            
            RewardCode::create([
                'code' => $newCode,
                'sku' => $sku,
                'is_used' => false,
            ]);
            
            $generatedCodes[] = $newCode;
        }
        
        return $generatedCodes;
    }
    
    public function generateQrCodesCsv(array $codes): string
    {
        $csv = "unique_code,full_url\n";
        
        $frontendUrl = config('app.frontend_url', config('app.url'));
        
        foreach ($codes as $code) {
            $fullUrl = $frontendUrl . '/claim?code=' . urlencode($code);
            $csv .= "\"{$code}\",\"{$fullUrl}\"\n";
        }
        
        return $csv;
    }
}
```

## Reporting Dashboard

### Analytics Reporting
```php
// app/Services/ReportService.php
namespace App\Services;

use App\Models\User;
use App\Models\Order;
use App\Models\ActionLog;
use Illuminate\Support\Carbon;

class ReportService
{
    public function getAdminDashboardStats(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        
        return [
            'total_users' => $this->getTotalUsers(),
            'new_users_this_month' => $this->getNewUsersThisMonth($startDate, $endDate),
            'total_scans' => $this->getTotalScans(),
            'scans_this_month' => $this->getScansThisMonth($startDate, $endDate),
            'total_redemptions' => $this->getTotalRedemptions(),
            'redemptions_this_month' => $this->getRedemptionsThisMonth($startDate, $endDate),
            'total_points_earned' => $this->getTotalPointsEarned(),
            'points_earned_this_month' => $this->getPointsEarnedThisMonth($startDate, $endDate),
            'average_engagement_score' => $this->getAverageEngagementScore(),
        ];
    }
    
    protected function getTotalUsers(): int
    {
        return User::count();
    }
    
    protected function getNewUsersThisMonth(Carbon $startDate, Carbon $endDate): int
    {
        return User::whereBetween('created_at', [$startDate, $endDate])->count();
    }
    
    protected function getTotalScans(): int
    {
        return ActionLog::where('action_type', 'scan')->count();
    }
    
    protected function getScansThisMonth(Carbon $startDate, Carbon $endDate): int
    {
        return ActionLog::where('action_type', 'scan')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }
    
    protected function getTotalRedemptions(): int
    {
        return Order::redemptions()->count();
    }
    
    protected function getRedemptionsThisMonth(Carbon $startDate, Carbon $endDate): int
    {
        return Order::redemptions()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }
    
    protected function getTotalPointsEarned(): int
    {
        return ActionLog::where('action_type', 'points_granted')
            ->sum('points_change');
    }
    
    protected function getPointsEarnedThisMonth(Carbon $startDate, Carbon $endDate): int
    {
        return ActionLog::where('action_type', 'points_granted')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('points_change');
    }
    
    protected function getAverageEngagementScore(): float
    {
        // This would integrate with the engagement scoring system
        // For now, return placeholder
        return 65.5;
    }
    
    public function getMonthlyUserGrowth(): array
    {
        $months = [];
        $counts = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');
            $counts[] = User::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }
        
        return [
            'labels' => $months,
            'data' => $counts,
        ];
    }
    
    public function getMonthlyActivity(): array
    {
        $months = [];
        $scans = [];
        $redemptions = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $months[] = $month->format('M Y');
            
            $scans[] = ActionLog::where('action_type', 'scan')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
                
            $redemptions[] = Order::redemptions()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }
        
        return [
            'labels' => $months,
            'scans' => $scans,
            'redemptions' => $redemptions,
        ];
    }
}
```

## Laravel-Native Features Utilized

### Authentication & Authorization
- Laravel Authentication for admin login
- Laravel Policies for fine-grained access control
- Laravel Gates for simple authorization checks
- Laravel Middleware for route-level protection

### Validation
- Laravel Form Requests for admin form validation
- Custom validation rules for admin-specific requirements
- Automatic error response formatting

### File Storage
- Laravel Storage for file uploads and downloads
- Multiple disk drivers (local, S3, etc.)
- File validation and security

### Queues & Jobs
- Laravel Jobs for long-running admin operations
- Queue workers for background processing
- Failed job handling and retry logic

### Notifications
- Laravel Notifications for admin alerts
- Multiple channels (email, database, Slack)
- Markdown notification templates

### Caching
- Laravel Cache for admin dashboard data
- Cache tags for granular invalidation
- Automatic cache expiration and refresh

### Logging
- Laravel Logging for admin actions
- Custom log channels for admin activities
- Log rotation and archiving

## Business Logic Implementation

### Admin Access Control
```php
// app/Policies/AdminAccessPolicy.php
namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminAccessPolicy
{
    use HandlesAuthorization;
    
    public function viewAdminPanel(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }
    
    public function manageUsers(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }
    
    public function manageProducts(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }
    
    public function manageConfiguration(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}
```

### Role-Based Access Control
```php
// app/Models/User.php (extension)
class User extends Authenticatable
{
    // ... existing code ...
    
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }
    
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }
    
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }
    
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }
}
```

### Admin Activity Logging
```php
// app/Services/AdminActivityLogger.php
namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminActivityLogger
{
    public function log(string $action, User $user, array $metadata = []): void
    {
        Log::channel('admin')->info("Admin Action: {$action}", [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
        ]);
    }
    
    public function logUserManagement(string $action, User $admin, User $targetUser, array $changes = []): void
    {
        $this->log("User Management: {$action}", $admin, [
            'target_user_id' => $targetUser->id,
            'target_user_email' => $targetUser->email,
            'changes' => $changes,
        ]);
    }
}
```

## Data Migration Strategy

### From WordPress Admin to Laravel Admin
- Migrate WordPress options to configuration_settings table
- Convert custom post types to Eloquent models
- Migrate user roles and capabilities
- Preserve existing admin user accounts
- Convert WordPress meta boxes to Laravel form components
- Migrate WordPress admin menus to Laravel navigation

## Dependencies
- Laravel Framework
- Laravel Nova (if chosen)
- Database (MySQL/PostgreSQL)
- Redis (for caching and queues)
- Eloquent ORM

## Definition of Done
- [ ] Admin panel provides comprehensive configuration management for brand settings
- [ ] Rank definitions can be created, edited, and managed through admin interface
- [ ] Achievement definitions can be configured with trigger events and conditions
- [ ] Product catalog management allows for full product lifecycle management
- [ ] User management provides tools for user account administration
- [ ] Order management allows for viewing and managing redemption orders
- [ ] Reporting dashboard displays meaningful business metrics and KPIs
- [ ] Merchant tools provide QR code generation and management capabilities
- [ ] System configuration allows for fine-tuning of application behavior
- [ ] Admin access control properly enforces role-based permissions
- [ ] Admin activity is properly logged for audit purposes
- [ ] Adequate test coverage for admin functionality
- [ ] Error handling for edge cases with proper error messages
- [ ] Performance benchmarks met for admin operations
- [ ] Responsive design works across different device sizes
- [ ] Proper validation using Laravel Form Requests
- [ ] Authorization policies enforce appropriate access controls
- [ ] File uploads and downloads work correctly for admin assets
- [ ] Data exports (CSV, Excel) function properly for reporting
- [ ] Admin notifications provide timely alerts for important events
- [ ] Cache invalidation works correctly when admin settings change
- [ ] Background processing via Laravel queues for long-running operations
- [ ] Admin interface is intuitive and user-friendly
- [ ] Help documentation is available for complex features
- [ ] Search functionality works correctly for large datasets
- [ ] Bulk operations (bulk edit, bulk delete) function properly
- [ ] Audit trail tracks all significant admin actions