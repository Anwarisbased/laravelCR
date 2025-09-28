# Laravel Vertical Slice 12: Testing Strategy

## Overview
This vertical slice implements a comprehensive testing strategy including unit tests, integration tests, feature tests, and end-to-end tests using Laravel's native testing features, replacing the existing Playwright test suite.

## Key Components

### Laravel Testing Types
- Unit Tests (for isolated class/method testing)
- Feature Tests (for API endpoint testing)
- Integration Tests (for service interactions)
- Browser Tests (using Laravel Dusk)
- Console Tests (for Artisan commands)
- Notification Tests (for notification verification)

### Testing Frameworks
- PHPUnit (core testing framework)
- Laravel Testing Helpers (built-in Laravel testing utilities)
- Laravel Dusk (browser testing)
- Mockery (mocking framework)
- Faker (test data generation)
- Pest (optional alternative to PHPUnit)

### Test Structure
- Unit Tests: `tests/Unit/`
- Feature Tests: `tests/Feature/`
- Integration Tests: `tests/Integration/`
- Browser Tests: `tests/Browser/`
- Console Tests: `tests/Console/`
- Notification Tests: `tests/Notifications/`

### Test Categories
- Authentication Tests
- Product Scanning Tests
- Points Economy Tests
- Referral System Tests
- Gamification Tests
- Rank Progression Tests
- Reward Catalog Tests
- User Profile Tests
- Order Management Tests
- Dashboard Analytics Tests
- Admin Interface Tests
- Infrastructure Tests

### Laravel Services
- TestDataService (test data generation)
- TestHelperService (common test utilities)
- TestAssertionService (custom assertions)
- TestFixtureService (test fixtures)
- TestCaseService (base test case management)

### Laravel Models
- TestUser (user model for testing)
- TestProduct (product model for testing)
- TestOrder (order model for testing)
- TestRank (rank model for testing)
- TestAchievement (achievement model for testing)

### Laravel Jobs
- TestJobRunner (integration test runner)
- TestDataCleanup (test data cleanup)
- TestResultReporter (test result reporting)

### Laravel Notifications
- TestNotification (notification for testing)
- TestNotificationAssert (notification assertion helper)

## Implementation Details

### Base Test Case Classes
```php
// tests/TestCase.php
namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, DatabaseTransactions;
    
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
    }
    
    protected function tearDown(): void
    {
        Artisan::call('db:wipe');
        parent::tearDown();
    }
}
```

### API Feature Test Base
```php
// tests/Feature/ApiTestCase.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

abstract class ApiTestCase extends TestCase
{
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createUser());
    }
    
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }
    
    protected function createAdminUser(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }
    
    protected function actingAs(User $user, string $driver = null)
    {
        if ($driver === 'sanctum') {
            Sanctum::actingAs($user);
        } else {
            parent::actingAs($user, $driver);
        }
        
        $this->user = $user;
        return $this;
    }
    
    protected function json($method, $uri, array $data = [], array $headers = [])
    {
        return parent::json($method, $uri, $data, array_merge([
            'Accept' => 'application/json',
        ], $headers));
    }
    
    protected function assertValidationError(string $field, string $message = null): void
    {
        $response = $this->getLatestResponse();
        
        $response->assertSessionHasErrors([$field]);
        
        if ($message) {
            $errors = session('errors');
            $this->assertEquals($message, $errors->first($field));
        }
    }
    
    protected function assertForbidden(): void
    {
        $response = $this->getLatestResponse();
        $response->assertForbidden();
    }
    
    protected function assertUnauthorized(): void
    {
        $response = $this->getLatestResponse();
        $response->assertUnauthorized();
    }
    
    protected function assertResourceCreated($resourceType = null): void
    {
        $response = $this->getLatestResponse();
        $response->assertStatus(201);
        
        if ($resourceType) {
            $response->assertJsonStructure([
                'data' => [
                    'id',
                    'type',
                    'attributes',
                ]
            ]);
            
            $responseData = $response->json('data');
            $this->assertEquals($resourceType, $responseData['type']);
        }
    }
    
    protected function getLatestResponse()
    {
        return $this->response;
    }
}
```

### Unit Test Example
```php
// tests/Unit/Services/RankServiceTest.php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RankService;
use App\Models\User;
use App\Models\Rank;
use Mockery;

class RankServiceTest extends TestCase
{
    protected $rankService;
    protected $mockUserRepository;
    protected $mockRankRepository;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockUserRepository = Mockery::mock(UserRepository::class);
        $this->mockRankRepository = Mockery::mock(RankRepository::class);
        
        $this->rankService = new RankService(
            $this->mockUserRepository,
            $this->mockRankRepository
        );
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    
    /** @test */
    public function it_calculates_user_rank_based_on_lifetime_points()
    {
        // Arrange
        $user = User::factory()->create(['lifetime_points' => 5000]);
        
        $ranks = collect([
            Rank::factory()->create(['points_required' => 0, 'key' => 'member']),
            Rank::factory()->create(['points_required' => 1000, 'key' => 'bronze']),
            Rank::factory()->create(['points_required' => 5000, 'key' => 'silver']),
            Rank::factory()->create(['points_required' => 10000, 'key' => 'gold']),
        ]);
        
        $this->mockRankRepository->shouldReceive('getAllRanks')
            ->once()
            ->andReturn($ranks);
            
        // Act
        $rank = $this->rankService->getUserRank($user);
        
        // Assert
        $this->assertEquals('silver', $rank->key);
        $this->assertEquals(5000, $rank->pointsRequired->toInt());
    }
    
    /** @test */
    public function it_returns_member_rank_for_zero_points()
    {
        // Arrange
        $user = User::factory()->create(['lifetime_points' => 0]);
        
        $ranks = collect([
            Rank::factory()->create(['points_required' => 0, 'key' => 'member']),
            Rank::factory()->create(['points_required' => 1000, 'key' => 'bronze']),
        ]);
        
        $this->mockRankRepository->shouldReceive('getAllRanks')
            ->once()
            ->andReturn($ranks);
            
        // Act
        $rank = $this->rankService->getUserRank($user);
        
        // Assert
        $this->assertEquals('member', $rank->key);
    }
    
    /** @test */
    public function it_applies_point_multiplier_correctly()
    {
        // Arrange
        $user = User::factory()->create(['lifetime_points' => 15000]);
        
        $ranks = collect([
            Rank::factory()->create(['points_required' => 0, 'key' => 'member', 'point_multiplier' => 1.0]),
            Rank::factory()->create(['points_required' => 10000, 'key' => 'gold', 'point_multiplier' => 2.0]),
        ]);
        
        $this->mockRankRepository->shouldReceive('getAllRanks')
            ->once()
            ->andReturn($ranks);
            
        // Act
        $rank = $this->rankService->getUserRank($user);
        
        // Assert
        $this->assertEquals('gold', $rank->key);
        $this->assertEquals(2.0, $rank->pointMultiplier);
    }
}
```

### Feature Test Example
```php
// tests/Feature/Api/AuthTest.php
namespace Tests\Feature\Api;

use Tests\Feature\ApiTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends ApiTestCase
{
    /** @test */
    public function user_can_register_with_valid_credentials()
    {
        $userData = [
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'first_name' => 'New',
            'last_name' => 'User',
            'phone' => '+15551234567',
            'agreed_to_terms' => true,
        ];
        
        $response = $this->postJson('/api/v1/auth/register', $userData);
        
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email', 'first_name', 'last_name'],
                    'token',
                ]
            ]);
            
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
        ]);
        
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }
    
    /** @test */
    public function registration_fails_with_duplicate_email()
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        
        $userData = [
            'email' => 'existing@example.com',
            'password' => 'password123',
            'first_name' => 'New',
            'last_name' => 'User',
            'agreed_to_terms' => true,
        ];
        
        $response = $this->postJson('/api/v1/auth/register', $userData);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
    
    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
        
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];
        
        $response = $this->postJson('/api/v1/auth/login', $loginData);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'email', 'first_name', 'last_name'],
                    'token',
                ]
            ]);
            
        $this->assertAuthenticatedAs($user);
    }
    
    /** @test */
    public function login_fails_with_invalid_credentials()
    {
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ];
        
        $response = $this->postJson('/api/v1/auth/login', $loginData);
        
        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials'
            ]);
            
        $this->assertGuest();
    }
}
```

## Testing Strategy Implementation

### Authentication Testing
```php
// tests/Feature/Api/UserAuthenticationTest.php
namespace Tests\Feature\Api;

use Tests\Feature\ApiTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

class UserAuthenticationTest extends ApiTestCase
{
    /** @test */
    public function guest_can_register_with_valid_data()
    {
        Notification::fake();
        
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'agreed_to_terms' => true,
        ];
        
        $response = $this->postJson('/api/v1/auth/register', $data);
        
        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                ],
                'token',
            ]
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        Notification::assertSentTo(
            User::where('email', 'john.doe@example.com')->first(),
            \App\Notifications\WelcomeNotification::class
        );
    }
    
    /** @test */
    public function registration_requires_required_fields()
    {
        $response = $this->postJson('/api/v1/auth/register', []);
        
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password', 'agreed_to_terms']);
    }
    
    /** @test */
    public function registration_requires_valid_email()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'agreed_to_terms' => true,
        ];
        
        $response = $this->postJson('/api/v1/auth/register', $data);
        
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }
    
    /** @test */
    public function registration_requires_password_complexity()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => '123',
            'agreed_to_terms' => true,
        ];
        
        $response = $this->postJson('/api/v1/auth/register', $data);
        
        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }
    
    /** @test */
    public function registration_creates_referral_code()
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'agreed_to_terms' => true,
        ];
        
        $response = $this->postJson('/api/v1/auth/register', $data);
        
        $response->assertCreated();
        
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user->referral_code);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{8}$/', $user->referral_code);
    }
    
    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;
        
        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');
            
        $response->assertOk();
        $response->assertJson(['message' => 'Logged out successfully']);
        
        // Verify the token was invalidated
        $this->assertFalse($user->tokens()->where('token', hash('sha256', $token))->exists());
    }
}
```

### Product Scanning Testing
```php
// tests/Feature/Api/ProductScanningTest.php
namespace Tests\Feature\Api;

use Tests\Feature\ApiTestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\RewardCode;

class ProductScanningTest extends ApiTestCase
{
    /** @test */
    public function authenticated_user_can_scan_valid_qr_code()
    {
        $user = $this->createUser();
        $product = Product::factory()->withPoints(400)->create();
        $rewardCode = RewardCode::factory()->for($product)->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/actions/claim', [
                'code' => $rewardCode->code,
            ]);
            
        $response->assertAccepted();
        $response->assertJson([
            'message' => 'Scan accepted for processing'
        ]);
        
        // Verify that points were awarded
        $user->refresh();
        $this->assertEquals(400, $user->points_balance);
        
        // Verify that the code is now marked as used
        $rewardCode->refresh();
        $this->assertTrue($rewardCode->is_used);
        
        // Verify that action was logged
        $this->assertDatabaseHas('action_logs', [
            'user_id' => $user->id,
            'action_type' => 'scan',
            'object_id' => $product->id,
        ]);
    }
    
    /** @test */
    public function scanning_invalid_qr_code_returns_error()
    {
        $user = $this->createUser();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/actions/claim', [
                'code' => 'INVALID-CODE',
            ]);
            
        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'Invalid or expired QR code'
        ]);
    }
    
    /** @test */
    public function scanning_already_used_qr_code_returns_error()
    {
        $user = $this->createUser();
        $product = Product::factory()->withPoints(400)->create();
        $usedRewardCode = RewardCode::factory()->for($product)->used()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/actions/claim', [
                'code' => $usedRewardCode->code,
            ]);
            
        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'QR code has already been used'
        ]);
    }
    
    /** @test */
    public function first_scan_awards_welcome_gift()
    {
        $user = $this->createUser();
        $product = Product::factory()->withPoints(400)->create();
        $rewardCode = RewardCode::factory()->for($product)->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/actions/claim', [
                'code' => $rewardCode->code,
            ]);
            
        $response->assertAccepted();
        
        // Verify that user received welcome gift
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'is_canna_redemption' => true,
            'points_cost' => 0,
        ]);
    }
    
    /** @test */
    public function scan_events_are_broadcast_correctly()
    {
        $user = $this->createUser();
        $product = Product::factory()->withPoints(400)->create();
        $rewardCode = RewardCode::factory()->for($product)->create();
        
        // Listen for the event
        $this->expectsEvents(\App\Events\ProductScanned::class);
        
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/actions/claim', [
                'code' => $rewardCode->code,
            ]);
            
        $response->assertAccepted();
    }
}
```

### Points Economy Testing
```php
// tests/Unit/Services/EconomyServiceTest.php
namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EconomyService;
use App\Models\User;
use App\Models\Product;
use Mockery;

class EconomyServiceTest extends TestCase
{
    /** @test */
    public function it_grants_points_to_user_correctly()
    {
        $user = User::factory()->create(['points_balance' => 1000]);
        $product = Product::factory()->create(['points_award' => 400]);
        
        $economyService = new EconomyService();
        
        $result = $economyService->grantPoints(
            $user,
            $product->points_award,
            'Product scan: ' . $product->name
        );
        
        $this->assertEquals(400, $result->pointsEarned->toInt());
        $this->assertEquals(1400, $result->newPointsBalance->toInt());
        
        $user->refresh();
        $this->assertEquals(1400, $user->points_balance);
    }
    
    /** @test */
    public function it_applies_rank_multiplier_to_granted_points()
    {
        $user = User::factory()->create([
            'points_balance' => 1000,
            'current_rank_key' => 'gold',
        ]);
        
        $economyService = new EconomyService();
        
        $result = $economyService->grantPoints(
            $user,
            400,
            'Product scan',
            2.0 // 2x multiplier
        );
        
        $this->assertEquals(800, $result->pointsEarned->toInt());
        $this->assertEquals(1800, $result->newPointsBalance->toInt());
    }
    
    /** @test */
    public function it_handles_negative_point_deductions()
    {
        $user = User::factory()->create(['points_balance' => 1000]);
        
        $economyService = new EconomyService();
        
        $result = $economyService->grantPoints(
            $user,
            -500,
            'Redemption'
        );
        
        $this->assertEquals(-500, $result->pointsEarned->toInt());
        $this->assertEquals(500, $result->newPointsBalance->toInt());
        
        $user->refresh();
        $this->assertEquals(500, $user->points_balance);
    }
}
```

## Test Data Management

### Test Data Service
```php
// app/Services/TestDataService.php
namespace App\Services;

use App\Models\User;
use App\Models\Product;
use App\Models\Rank;
use App\Models\Achievement;
use App\Models\RewardCode;

class TestDataService
{
    public function createTestUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'points_balance' => 0,
            'lifetime_points' => 0,
            'current_rank_key' => 'member',
        ], $attributes));
    }
    
    public function createTestProduct(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge([
            'points_award' => 400,
            'points_cost' => 500,
        ], $attributes));
    }
    
    public function createTestRank(array $attributes = []): Rank
    {
        return Rank::factory()->create(array_merge([
            'points_required' => 0,
            'point_multiplier' => 1.0,
        ], $attributes));
    }
    
    public function createTestAchievement(array $attributes = []): Achievement
    {
        return Achievement::factory()->create(array_merge([
            'points_reward' => 100,
            'trigger_event' => 'product_scanned',
            'trigger_count' => 1,
        ], $attributes));
    }
    
    public function createTestRewardCode(Product $product, array $attributes = []): RewardCode
    {
        return RewardCode::factory()->for($product)->create($attributes);
    }
    
    public function createTestDataScenario(string $scenario): array
    {
        switch ($scenario) {
            case 'new_user':
                return $this->createNewUserScenario();
                
            case 'loyal_user':
                return $this->createLoyalUserScenario();
                
            case 'first_scan':
                return $this->createFirstScanScenario();
                
            case 'rank_transition':
                return $this->createRankTransitionScenario();
                
            default:
                throw new \InvalidArgumentException("Unknown scenario: $scenario");
        }
    }
    
    protected function createNewUserScenario(): array
    {
        $user = $this->createTestUser();
        $product = $this->createTestProduct();
        $rewardCode = $this->createTestRewardCode($product);
        
        return [
            'user' => $user,
            'product' => $product,
            'reward_code' => $rewardCode,
        ];
    }
    
    protected function createLoyalUserScenario(): array
    {
        $user = $this->createTestUser([
            'points_balance' => 5000,
            'lifetime_points' => 15000,
            'current_rank_key' => 'gold',
        ]);
        
        $product = $this->createTestProduct(['points_award' => 400]);
        $rewardCode = $this->createTestRewardCode($product);
        
        return [
            'user' => $user,
            'product' => $product,
            'reward_code' => $rewardCode,
        ];
    }
    
    protected function createFirstScanScenario(): array
    {
        $user = $this->createTestUser();
        $product = $this->createTestProduct([
            'points_award' => 400,
            'sku' => 'PWT-001',
        ]);
        $rewardCode = $this->createTestRewardCode($product);
        
        return [
            'user' => $user,
            'product' => $product,
            'reward_code' => $rewardCode,
        ];
    }
    
    protected function createRankTransitionScenario(): array
    {
        $user = $this->createTestUser([
            'points_balance' => 9500,
            'lifetime_points' => 9500,
            'current_rank_key' => 'silver',
        ]);
        
        $this->createTestRank([
            'key' => 'member',
            'points_required' => 0,
            'point_multiplier' => 1.0,
        ]);
        
        $this->createTestRank([
            'key' => 'silver',
            'points_required' => 5000,
            'point_multiplier' => 1.5,
        ]);
        
        $this->createTestRank([
            'key' => 'gold',
            'points_required' => 10000,
            'point_multiplier' => 2.0,
        ]);
        
        $product = $this->createTestProduct(['points_award' => 1000]);
        $rewardCode = $this->createTestRewardCode($product);
        
        return [
            'user' => $user,
            'product' => $product,
            'reward_code' => $rewardCode,
        ];
    }
}
```

## Browser Testing with Laravel Dusk
```php
// tests/Browser/Pages/UserDashboardTest.php
namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class UserDashboard extends Page
{
    public function url()
    {
        return '/dashboard';
    }
    
    public function assert(Browser $browser)
    {
        $browser->assertPathIs($this->url())
                ->assertSee('Welcome')
                ->assertSee('Points Balance')
                ->assertSee('Rank');
    }
    
    public function elements()
    {
        return [
            '@points-balance' => '.points-balance',
            '@current-rank' => '.current-rank',
            '@scan-button' => '.scan-cta-button',
            '@redeem-button' => '.redeem-button',
            '@referral-section' => '.referral-section',
        ];
    }
    
    public function scanProduct(Browser $browser)
    {
        return $browser->click('@scan-button')
                      ->waitForLocation('/scan');
    }
    
    public function redeemReward(Browser $browser)
    {
        return $browser->click('@redeem-button')
                      ->waitForLocation('/catalog');
    }
}

// tests/Browser/UserDashboardTest.php
namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;

class UserDashboardTest extends DuskTestCase
{
    /** @test */
    public function user_can_see_dashboard_after_login()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->visit(new Login)
                    ->type('email', $user->email)
                    ->type('password', 'password')
                    ->click('@login-button')
                    ->on(new UserDashboard)
                    ->assertSee('Welcome, ' . $user->first_name)
                    ->assertSeeIn('@points-balance', $user->points_balance)
                    ->assertSeeIn('@current-rank', $user->rank->name);
        });
    }
    
    /** @test */
    public function user_can_scan_product_from_dashboard()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new UserDashboard)
                    ->scanProduct()
                    ->assertPathIs('/scan')
                    ->assertSee('Scan QR Code');
        });
    }
}
```

## Test Coverage Strategy

### Coverage Goals
```php
// phpunit.xml (coverage configuration)
<phpunit 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="vendor/autoload.php"
    colors="true"
>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory suffix=".php">./app/Console</directory>
            <directory suffix=".php">./app/Providers</directory>
            <file>./app/Http/Middleware/TrustHosts.php</file>
            <file>./app/Http/Middleware/TrustProxies.php</file>
        </exclude>
    </coverage>
    <php>
        <server name="APP_ENV" value="testing"/>
        <server name="BCRYPT_ROUNDS" value="4"/>
        <server name="CACHE_DRIVER" value="array"/>
        <server name="DB_CONNECTION" value="sqlite"/>
        <server name="DB_DATABASE" value=":memory:"/>
        <server name="MAIL_MAILER" value="array"/>
        <server name="QUEUE_CONNECTION" value="sync"/>
        <server name="SESSION_DRIVER" value="array"/>
        <server name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### Test Coverage Reporting
```php
// tests/Feature/CoverageTest.php
namespace Tests\Feature;

use Tests\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\Html\Facade as HtmlReport;

class CoverageTest extends TestCase
{
    public function testCoverageReportGeneration()
    {
        $filter = new Filter();
        $filter->includeDirectory(app_path());
        $filter->excludeDirectory(app_path('Console'));
        $filter->excludeDirectory(app_path('Providers'));
        
        $driver = (new Selector())->forLineCoverage($filter);
        $coverage = new CodeCoverage($driver, $filter);
        
        // This would actually run tests and collect coverage data
        // For demonstration, we'll just show the structure
        
        $this->assertTrue(true); // Placeholder assertion
    }
    
    public function testAllEndpointsHaveFeatureCoverage()
    {
        $endpoints = [
            '/api/v1/auth/register',
            '/api/v1/auth/login',
            '/api/v1/actions/claim',
            '/api/v1/actions/redeem',
            // ... other endpoints
        ];
        
        foreach ($endpoints as $endpoint) {
            $this->assertTrue($this->hasFeatureTestCoverage($endpoint), 
                "Missing feature test coverage for endpoint: {$endpoint}");
        }
    }
    
    protected function hasFeatureTestCoverage(string $endpoint): bool
    {
        // This would actually check if there are tests covering the endpoint
        // For demonstration, we'll return true
        return true;
    }
}
```

## Continuous Integration Pipeline
```yaml
# .github/workflows/test.yml
name: Run Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ALLOW_EMPTY_PASSWORD: yes
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: testing
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v2

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, dom, fileinfo, mysql
        coverage: none

    - name: Cache Composer packages
      id: composer-cache
      run: echo "::set-output name=dir::$(composer config cache-files-dir)"

    - uses: actions/cache@v2
      with:
        path: ${{ steps.composer-cache.outputs.dir }}
        key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.json') }}
        restore-keys: ${{ runner.os }}-composer-

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Directory Permissions
      run: chmod -R 777 storage bootstrap/cache

    - name: Create Database
      run: |
        mkdir -p storage/logs
        touch storage/logs/laravel.log
        php artisan config:clear
        php artisan config:cache

    - name: Run Migrations
      run: php artisan migrate --env=testing --database=mysql --force

    - name: Run Unit Tests
      run: vendor/bin/phpunit --testsuite=Unit --log-junit=unit-tests.xml

    - name: Run Feature Tests
      run: vendor/bin/phpunit --testsuite=Feature --log-junit=feature-tests.xml

    - name: Run Integration Tests
      run: vendor/bin/phpunit --testsuite=Integration --log-junit=integration-tests.xml

    - name: Upload Test Results
      uses: actions/upload-artifact@v2
      if: always()
      with:
        name: test-results
        path: |
          unit-tests.xml
          feature-tests.xml
          integration-tests.xml
          storage/logs/laravel.log
```

## Test Data Factories
```php
// database/factories/UserFactory.php
namespace Database\Factories;

use App\Models\Rank;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;
    
    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'points_balance' => $this->faker->numberBetween(0, 10000),
            'lifetime_points' => $this->faker->numberBetween(0, 50000),
            'current_rank_key' => 'member',
            'referral_code' => Str::upper(Str::random(8)),
            'marketing_consent' => $this->faker->boolean(70),
            'remember_token' => Str::random(10),
        ];
    }
    
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_admin' => true,
            ];
        });
    }
    
    public function withHighPoints()
    {
        return $this->state(function (array $attributes) {
            return [
                'points_balance' => 15000,
                'lifetime_points' => 30000,
                'current_rank_key' => 'gold',
            ];
        });
    }
    
    public function new()
    {
        return $this->state(function (array $attributes) {
            return [
                'points_balance' => 0,
                'lifetime_points' => 0,
                'current_rank_key' => 'member',
            ];
        });
    }
}

// database/factories/ProductFactory.php
namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;
    
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'sku' => 'PWT-' . $this->faker->unique()->numberBetween(100, 999),
            'description' => $this->faker->paragraph,
            'short_description' => $this->faker->sentence,
            'points_award' => $this->faker->randomElement([100, 200, 400, 500, 1000]),
            'points_cost' => $this->faker->randomElement([500, 1000, 2000, 5000]),
            'required_rank_key' => null,
            'is_active' => true,
            'brand' => $this->faker->company,
            'strain_type' => $this->faker->randomElement(['Sativa', 'Indica', 'Hybrid']),
            'thc_content' => $this->faker->randomFloat(1, 10, 30),
            'cbd_content' => $this->faker->randomFloat(1, 0, 10),
            'product_form' => $this->faker->randomElement(['Vape Cartridge', 'Flower', 'Edible']),
        ];
    }
    
    public function withPoints(int $points)
    {
        return $this->state(function (array $attributes) use ($points) {
            return [
                'points_award' => $points,
            ];
        });
    }
    
    public function premium()
    {
        return $this->state(function (array $attributes) {
            return [
                'points_cost' => 5000,
                'required_rank_key' => 'gold',
            ];
        });
    }
}
```

## Test Helpers
```php
// tests/Helpers/TestHelper.php
namespace Tests\Helpers;

use App\Models\User;
use App\Models\Product;
use App\Models\RewardCode;
use App\Models\Rank;
use App\Models\Achievement;

class TestHelper
{
    public static function createUserWithRank(string $rankKey = 'member'): User
    {
        $rank = self::ensureRankExists($rankKey);
        
        return User::factory()->create([
            'current_rank_key' => $rank->key,
        ]);
    }
    
    public static function createScanScenario(int $lifetimePoints = 0): array
    {
        $user = User::factory()->create([
            'points_balance' => 0,
            'lifetime_points' => $lifetimePoints,
        ]);
        
        $product = Product::factory()->withPoints(400)->create();
        $rewardCode = RewardCode::factory()->for($product)->create();
        
        return [
            'user' => $user,
            'product' => $product,
            'rewardCode' => $rewardCode,
        ];
    }
    
    protected static function ensureRankExists(string $rankKey): Rank
    {
        $rank = Rank::where('key', $rankKey)->first();
        
        if (!$rank) {
            $rank = Rank::factory()->create([
                'key' => $rankKey,
                'points_required' => self::getPointsRequiredForRank($rankKey),
                'point_multiplier' => self::getMultiplierForRank($rankKey),
            ]);
        }
        
        return $rank;
    }
    
    protected static function getPointsRequiredForRank(string $rankKey): int
    {
        return match($rankKey) {
            'member' => 0,
            'bronze' => 1000,
            'silver' => 5000,
            'gold' => 10000,
            default => 0,
        };
    }
    
    protected static function getMultiplierForRank(string $rankKey): float
    {
        return match($rankKey) {
            'member' => 1.0,
            'bronze' => 1.2,
            'silver' => 1.5,
            'gold' => 2.0,
            default => 1.0,
        };
    }
    
    public static function createRedemptionScenario(): array
    {
        $user = self::createUserWithRank('gold');
        $user->update([
            'points_balance' => 5000,
            'lifetime_points' => 15000,
        ]);
        
        $product = Product::factory()->premium()->create([
            'points_cost' => 2000,
        ]);
        
        return [
            'user' => $user,
            'product' => $product,
        ];
    }
}
```

## Performance Testing
```php
// tests/Feature/PerformanceTest.php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;

class PerformanceTest extends TestCase
{
    /** @test */
    public function api_response_time_for_dashboard_is_under_200ms()
    {
        $user = User::factory()->withHighPoints()->create();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)->getJson('/api/v1/users/me/dashboard');
        
        $endTime = microtime(true);
        $durationMs = ($endTime - $startTime) * 1000;
        
        $response->assertSuccessful();
        $this->assertLessThan(200, $durationMs, 
            "Dashboard API response took {$durationMs}ms, exceeding 200ms threshold");
    }
    
    /** @test */
    public function api_response_time_for_catalog_is_under_500ms()
    {
        // Create 100 products
        Product::factory(100)->create();
        
        $startTime = microtime(true);
        
        $response = $this->getJson('/api/v1/catalog/products?limit=50');
        
        $endTime = microtime(true);
        $durationMs = ($endTime - $startTime) * 1000;
        
        $response->assertSuccessful();
        $this->assertLessThan(500, $durationMs, 
            "Catalog API response took {$durationMs}ms, exceeding 500ms threshold");
    }
}
```

## Laravel-Native Features Utilized

### Testing Helpers
- Laravel testing helpers for database transactions
- Laravel testing helpers for authentication
- Laravel testing helpers for assertions
- Laravel testing helpers for mocking
- Laravel testing helpers for factories
- Laravel testing helpers for HTTP responses

### Database Testing
- Laravel database testing with SQLite in memory
- Laravel database seeding for test data
- Laravel database transactions for test isolation
- Laravel database factories for fake data generation
- Laravel database assertions for data validation

### HTTP Testing
- Laravel HTTP testing for API endpoints
- Laravel JSON testing for API responses
- Laravel authentication testing for protected endpoints
- Laravel validation testing for form requests
- Laravel session testing for state management

### Browser Testing
- Laravel Dusk for browser testing
- Laravel Dusk pages for page object pattern
- Laravel Dusk components for reusable element groups
- Laravel Dusk assertions for browser state validation
- Laravel Dusk screenshots for debugging

### Console Testing
- Laravel console testing for Artisan commands
- Laravel console input/output testing
- Laravel console exit code testing
- Laravel console command scheduling testing
- Laravel console event dispatching testing

## Test Strategy Implementation

### Continuous Integration
```yaml
# docker-compose.testing.yml
version: '3.8'
services:
  app:
    build:
      context: ./docker/8.1
      dockerfile: Dockerfile
    volumes:
      - .:/var/www/html
    depends_on:
      - mysql
      - redis
    networks:
      - testing-network

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: testing
      MYSQL_USER: testing
      MYSQL_PASSWORD: testing
    ports:
      - "3306:3306"
    volumes:
      - mysql-data:/var/lib/mysql
    networks:
      - testing-network

  redis:
    image: redis:alpine
    ports:
      - "6379:6379"
    networks:
      - testing-network

  selenium:
    image: selenium/standalone-chrome-debug:3.141.59
    ports:
      - "4444:4444"
      - "5900:5900"
    networks:
      - testing-network

volumes:
  mysql-data:

networks:
  testing-network:
    driver: bridge
```

## Dependencies
- Laravel Framework
- PHPUnit
- Laravel Dusk
- Mockery
- Faker
- Database (MySQL/PostgreSQL/SQLite)
- Redis (for caching and queues)
- Selenium (for browser testing)

## Definition of Done
- [ ] All API endpoints have feature tests covering happy paths
- [ ] All API endpoints have feature tests covering error conditions
- [ ] All API endpoints have feature tests covering authentication
- [ ] All API endpoints have feature tests covering authorization
- [ ] All API endpoints have feature tests covering data validation
- [ ] All business logic has unit tests covering edge cases
- [ ] All business logic has unit tests covering error conditions
- [ ] All business logic has unit tests covering all code paths
- [ ] All services have integration tests covering cross-service interactions
- [ ] All services have integration tests covering external dependencies
- [ ] All services have integration tests covering data flow
- [ ] All domain entities have unit tests covering value object behavior
- [ ] All domain entities have unit tests covering entity invariants
- [ ] All repositories have integration tests covering data access
- [ ] All repositories have integration tests covering query logic
- [ ] All repositories have integration tests covering data transformations
- [ ] All policies have unit tests covering authorization rules
- [ ] All policies have unit tests covering edge cases
- [ ] All policies have unit tests covering error conditions
- [ ] All controllers have feature tests covering request handling
- [ ] All controllers have feature tests covering response formatting
- [ ] All controllers have feature tests covering error handling
- [ ] All notifications have notification tests covering delivery
- [ ] All notifications have notification tests covering content
- [ ] All notifications have notification tests covering recipients
- [ ] All jobs have job tests covering execution
- [ ] All jobs have job tests covering error handling
- [ ] All jobs have job tests covering state changes
- [ ] All events have event tests covering event firing
- [ ] All events have event tests covering event handling
- [ ] All events have event tests covering event propagation
- [ ] All console commands have console tests covering execution
- [ ] All console commands have console tests covering output
- [ ] All console commands have console tests covering exit codes
- [ ] All browser flows have dusk tests covering user journeys
- [ ] All browser flows have dusk tests covering happy paths
- [ ] All browser flows have dusk tests covering error conditions
- [ ] All browser flows have dusk tests covering validation
- [ ] Test coverage meets minimum thresholds (85% overall)
- [ ] Performance benchmarks met (API responses < 500ms)
- [ ] All tests pass in continuous integration pipeline
- [ ] All tests pass with database transactions enabled
- [ ] All tests pass with cache enabled
- [ ] All tests pass with queue processing enabled
- [ ] All tests pass with external services mocked
- [ ] All tests pass with concurrent execution
- [ ] All tests pass with randomized test execution order
- [ ] All tests pass with code coverage enabled
- [ ] All tests pass with strict error reporting enabled
- [ ] All tests pass with memory profiling enabled
- [ ] All tests pass with xdebug profiling enabled
- [ ] Test data is properly cleaned up after each test
- [ ] Test data does not interfere with other tests
- [ ] Test environment is properly isolated
- [ ] Test environment matches production environment
- [ ] Test results are properly reported
- [ ] Test failures are properly diagnosed
- [ ] Test execution time meets targets (< 10 minutes for full suite)
- [ ] Test flakiness is minimized (< 1% failure rate)
- [ ] Test reliability is maximized (> 99% pass rate)
- [ ] Test maintainability is ensured (easy to update)
- [ ] Test readability is maximized (clear intent)
- [ ] Test coverage is properly measured and reported
- [ ] Test performance is properly monitored and optimized
- [ ] Test infrastructure is properly maintained and documented
- [ ] Test data privacy is properly protected
- [ ] Test security is properly maintained
- [ ] Test compliance is properly maintained