# Laravel Development Guidelines

## Overview
This document establishes the development guidelines for building the CannaRewards Laravel application, ensuring consistency, maintainability, and adherence to best practices.

## Code Standards

### PSR Standards
- All code MUST follow PSR-1, PSR-2, and PSR-12 standards
- Use PHP_CodeSniffer with Laravel Pint for automatic formatting
- Run `pint` command before committing code
- Configure IDE to automatically format on save

### Laravel Best Practices
- Follow the Laravel naming conventions for models, controllers, and variables
- Use resource controllers for RESTful APIs
- Leverage Laravel's built-in features rather than reinventing the wheel
- Use Eloquent ORM instead of raw database queries when possible
- Use Laravel's validation features in Form Requests
- Use Laravel's authorization features (Gate/Policies) for access control
- Use Laravel's caching features for performance optimization
- Use Laravel's queue system for background jobs
- Use Laravel's event system for decoupled communication

### Domain-Driven Design
- Separate domain logic from infrastructure concerns
- Use Value Objects to encapsulate business rules
- Implement repositories for data access abstraction
- Use services for business logic coordination
- Apply CQRS patterns where appropriate
- Use domain events for state changes
- Implement the Command pattern for write operations
- Use Read Models for query operations

## Architecture Patterns

### Vertical Slice Architecture
- Organize code by business capability, not technical layers
- Each slice contains all necessary components (controllers, services, models, etc.)
- Slices are loosely coupled and independently deployable
- Shared infrastructure is centralized and reusable
- Cross-cutting concerns are implemented as middleware or services

### Hexagonal Architecture (Ports and Adapters)
- Define clear ports (interfaces) for external dependencies
- Implement adapters for concrete implementations
- Keep domain logic independent of external systems
- Use dependency inversion to facilitate testing
- Separate core business logic from infrastructure concerns

### Clean Architecture
- Separate concerns into distinct layers
- Domain layer contains business rules and entities
- Application layer contains use cases and services
- Interface adapter layer contains controllers and presenters
- Framework and drivers layer contains infrastructure implementations

## Domain Modeling

### Value Objects
- Immutable objects that are compared by value, not identity
- Encapsulate validation and business rules
- Must be valid when created
- Should be used for domain concepts that have value semantics
- Examples: EmailAddress, Points, Sku, PhoneNumber, etc.

### Entities
- Objects with persistent identity
- Mutable and can change over time
- Compared by identity, not value
- Should be rich with behavior
- Examples: User, Product, RewardCode, etc.

### Aggregates
- Cluster of entities and value objects treated as a single unit
- Have a clear boundary and consistency rules
- Managed by repositories
- Changes to aggregates should maintain consistency

### Repositories
- Provide the illusion of an in-memory collection of domain objects
- Abstract data access and persistence concerns
- One repository per aggregate
- Should not expose infrastructure concerns to domain layer

### Services
- Stateless operations that belong in the domain but aren't natural methods on any entity or value object
- Should be stateless and idempotent when possible
- Examples: RankService, EconomyService, ReferralService, etc.

## API Design

### RESTful Principles
- Use nouns, not verbs in URLs
- Use plural nouns for collections
- Use HTTP verbs for operations (GET, POST, PUT, DELETE)
- Use proper HTTP status codes
- Keep URLs resource-focused
- Use query parameters for filtering, sorting, pagination

### JSON API Compliance
- Follow JSON:API specification for consistent response format
- Include all related resources in includes
- Use sparse fieldsets to reduce payload size
- Implement proper error responses
- Use proper pagination links

### Versioning
- API endpoints should be versioned (e.g., /api/v1/)
- Major versions for breaking changes
- Minor versions for backward-compatible additions
- Use semantic versioning

### Request Validation
```php
// app/Http/Requests/Api/V1/CreateUserRequest.php
<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'agreed_to_terms' => ['required', 'accepted'],
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower(trim($this->email)),
            'first_name' => ucfirst(strtolower(trim($this->first_name))),
            'last_name' => ucfirst(strtolower(trim($this->last_name))),
        ]);
    }
}
```

### Response Formatting
```php
// app/Http/Resources/Api/V1/UserResource.php
<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => 'users',
            'id' => (string) $this->id,
            'attributes' => [
                'email' => $this->email,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'phone' => $this->phone,
                'points_balance' => $this->points_balance,
                'lifetime_points' => $this->lifetime_points,
                'current_rank_key' => $this->current_rank_key,
                'referral_code' => $this->referral_code,
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ],
            'links' => [
                'self' => route('api.v1.users.show', $this->id),
            ],
        ];
    }
}
```

## Database Design

### Naming Conventions
- Use snake_case for table and column names
- Use plural table names
- Primary keys should be named `id`
- Foreign keys should be named `{referenced_table_singular}_id`
- Junction tables should be named alphabetically, plural, underscored (e.g., `role_user`)
- Timestamp columns should be `created_at` and `updated_at`
- Soft deletes should use `deleted_at` column

### Relationships
- Use Eloquent relationships for database associations
- Define foreign key constraints with proper cascade options
- Use database indexes for frequently queried columns
- Implement proper data types for columns
- Use enum columns for fixed sets of values

### Migrations
```php
// database/migrations/2023_01_01_000000_create_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->unsignedInteger('points_balance')->default(0);
            $table->unsignedInteger('lifetime_points')->default(0);
            $table->string('current_rank_key')->default('member');
            $table->string('referral_code')->unique()->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->string('shipping_first_name')->nullable();
            $table->string('shipping_last_name')->nullable();
            $table->string('shipping_address_1')->nullable();
            $table->string('shipping_address_2')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postcode')->nullable();
            $table->string('shipping_country')->default('US')->nullable();
            $table->string('shipping_phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('email');
            $table->index('referral_code');
            $table->index('points_balance');
            $table->index('lifetime_points');
            $table->index('current_rank_key');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

### Indexing Strategy
- Create composite indexes for frequently used query combinations
- Use partial indexes for conditional queries
- Monitor query performance and adjust indexes as needed
- Use database-specific features (e.g., PostgreSQL partial indexes)
- Consider covering indexes for frequently accessed columns

### Data Integrity
- Use database constraints for data validation when possible
- Implement proper foreign key constraints with cascade options
- Use check constraints for domain-specific validations
- Implement soft deletes for audit trails
- Use database triggers for complex business rules (sparingly)

## Security Considerations

### Authentication
- Use Laravel Sanctum for API authentication
- Implement proper password policies
- Use rate limiting for authentication endpoints
- Implement two-factor authentication for admin users
- Use secure password hashing with bcrypt or Argon2

### Authorization
```php
// app/Policies/UserPolicy.php
<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $user, User $model)
    {
        return $user->is($model) || $user->isAdmin();
    }

    public function update(User $user, User $model)
    {
        return $user->is($model) || $user->isAdmin();
    }

    public function delete(User $user, User $model)
    {
        return $user->isAdmin() && !$user->is($model);
    }
}
```

### Input Validation
- Validate all user input at the boundary of the system
- Use Laravel Form Requests for request validation
- Implement proper sanitization for output
- Use Laravel's built-in validation rules
- Implement custom validation rules for domain-specific logic

### Data Protection
- Encrypt sensitive data (PII, passwords, etc.)
- Use HTTPS for all communications
- Implement proper CORS headers
- Use secure headers (X-Frame-Options, X-XSS-Protection, etc.)
- Implement content security policy (CSP)

### Rate Limiting
```php
// app/Http/Middleware/ThrottleRequests.php
<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests as Middleware;

class ThrottleRequests extends Middleware
{
    protected function resolveRequestSignature($request)
    {
        if ($user = $request->user()) {
            return sha1($user->id);
        }
        
        if ($route = $request->route()) {
            return sha1($route->getDomain().'|'.$request->ip());
        }
        
        throw new BindingResolutionException('Unable to generate the request signature. Route unavailable.');
    }
}
```

### Vulnerability Management
- Regularly update Laravel and dependencies
- Monitor for security advisories
- Implement proper error handling to prevent information disclosure
- Use security scanning tools in CI/CD pipeline
- Conduct regular security audits

## Performance Optimization

### Caching Strategy
- Use Redis or Memcached for caching
- Implement cache warming for frequently accessed data
- Use cache tags for granular invalidation
- Implement proper cache expiration strategies
- Use Laravel's built-in caching features

### Database Optimization
- Use eager loading to prevent N+1 queries
- Implement proper database indexing
- Use query optimization techniques
- Monitor slow queries with Laravel Telescope
- Use database connection pooling

### Queues and Jobs
```php
// app/Jobs/ProcessUserRegistration.php
<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUserRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $pointsAwarded;

    public function __construct(User $user, int $pointsAwarded)
    {
        $this->user = $user;
        $this->pointsAwarded = $pointsAwarded;
    }

    public function handle()
    {
        // Award welcome points
        $this->user->increment('points_balance', $this->pointsAwarded);
        $this->user->increment('lifetime_points', $this->pointsAwarded);

        // Send welcome notification
        $this->user->notify(new \App\Notifications\WelcomeNotification($this->user));

        // Track in analytics
        event(new \App\Events\UserRegistered($this->user, $this->pointsAwarded));
    }
}
```

### Monitoring and Profiling
- Use Laravel Telescope for debugging and monitoring
- Implement proper logging with context
- Use performance profiling tools
- Monitor application metrics
- Implement proper alerting for performance issues

### Memory Management
- Use generators for large data sets
- Implement proper garbage collection
- Monitor memory usage in long-running processes
- Use chunking for large database operations
- Implement proper resource cleanup

## Testing Standards

### Test Pyramid
- Unit Tests: 70% of all tests
- Integration Tests: 25% of all tests
- End-to-End Tests: 5% of all tests

### Unit Testing
```php
// tests/Unit/Services/EconomyServiceTest.php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EconomyService;
use App\Models\User;
use App\Models\Product;

class EconomyServiceTest extends TestCase
{
    private $economyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->economyService = new EconomyService();
    }

    /** @test */
    public function it_calculates_points_with_rank_multiplier()
    {
        $user = User::factory()->create([
            'current_rank_key' => 'gold',
        ]);

        $product = Product::factory()->create([
            'points_award' => 400,
        ]);

        $result = $this->economyService->calculatePointsForUser($user, $product);

        // Gold rank has 2.0x multiplier
        $this->assertEquals(800, $result->toInt());
    }
}
```

### Feature Testing
```php
// tests/Feature/Api/AuthTest.php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    /** @test */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'agreed_to_terms' => true,
        ];

        $response = $this->postJson('/api/v1/auth/register', $userData);

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'user' => ['id', 'email', 'first_name', 'last_name'],
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }
}
```

### Testing Best Practices
- Use descriptive test method names
- Test one thing per test method
- Use test data factories for consistent test data
- Implement proper test isolation
- Use database transactions for test isolation
- Mock external dependencies
- Test edge cases and error conditions
- Use data providers for parameterized tests
- Implement proper test setup and teardown
- Use assertions appropriately

### Test Coverage
- Maintain 85%+ code coverage overall
- 100% coverage for critical business logic
- 95%+ coverage for services
- 90%+ coverage for models
- 80%+ coverage for controllers
- Monitor coverage trends over time
- Exclude non-testable code from coverage (configuration, etc.)

## Documentation Standards

### API Documentation
- Use OpenAPI (Swagger) for API documentation
- Document all endpoints with parameters and responses
- Include example requests and responses
- Document authentication requirements
- Document rate limits and quotas
- Include error response formats
- Document deprecation policies

### Code Documentation
```php
/**
 * Calculate user points with applicable multipliers based on current rank.
 *
 * This method applies the rank-based point multiplier to the base points
 * awarded for a product. The multiplier is retrieved from the user's
 * current rank configuration.
 *
 * @param  \App\Models\User  $user
 * @param  \App\Models\Product  $product
 * @return \App\Domain\ValueObjects\Points
 */
public function calculatePointsForUser(User $user, Product $product): Points
{
    $rank = $this->rankService->getUserRank($user);
    $basePoints = Points::fromInt($product->points_award);
    
    return $basePoints->multiplyBy($rank->pointMultiplier);
}
```

### Domain Documentation
- Document domain concepts and business rules
- Include diagrams for complex workflows
- Document decision rationale
- Include glossary of domain terms
- Document bounded contexts
- Document context mapping

## Deployment and Operations

### Continuous Integration
```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [ main, develop ]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
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

    - name: Install Dependencies
      run: composer install -q --no-ansi --no-interaction --no-scripts --no-progress --prefer-dist

    - name: Generate key
      run: php artisan key:generate

    - name: Run Migrations
      run: php artisan migrate --env=testing --database=mysql --force

    - name: Run Unit Tests
      run: vendor/bin/phpunit --testsuite=Unit

    - name: Run Feature Tests
      run: vendor/bin/phpunit --testsuite=Feature

    - name: Run Integration Tests
      run: vendor/bin/phpunit --testsuite=Integration
```

### Deployment Strategy
- Use blue-green deployment for zero-downtime deployments
- Implement proper rollback procedures
- Use feature flags for gradual rollouts
- Implement canary releases for major changes
- Use infrastructure as code (Terraform, CloudFormation)
- Implement proper monitoring and alerting
- Use automated deployment pipelines

### Monitoring and Alerting
- Implement application performance monitoring (APM)
- Use logging aggregation (ELK, Datadog, etc.)
- Implement proper error tracking (Sentry, Bugsnag, etc.)
- Use infrastructure monitoring (Prometheus, Grafana, etc.)
- Implement business metric tracking
- Set up proper alerting thresholds
- Implement incident response procedures

### Backup and Recovery
- Implement regular database backups
- Use incremental backups for large databases
- Implement proper backup retention policies
- Test backup restoration regularly
- Implement point-in-time recovery
- Use cloud provider backup services
- Implement disaster recovery procedures

## Development Environment

### Local Development
- Use Laravel Sail for containerized development
- Implement proper `.env` file management
- Use Xdebug for debugging
- Implement proper IDE configuration
- Use database seeds for consistent test data
- Implement proper development database management

### Development Workflow
1. Create feature branch from develop
2. Implement feature with tests
3. Run full test suite locally
4. Create pull request with description
5. Request code review
6. Address review feedback
7. Merge after approval
8. Delete feature branch

### Code Review Standards
- Review all pull requests before merging
- Check for adherence to coding standards
- Ensure proper test coverage
- Verify proper error handling
- Check for security vulnerabilities
- Review performance implications
- Ensure proper documentation
- Verify proper logging and monitoring

### Branching Strategy
- Use GitFlow branching strategy
- Main branch for production-ready code
- Develop branch for integration
- Feature branches for new features
- Release branches for version releases
- Hotfix branches for urgent fixes
- Proper branch naming conventions
- Regular branch cleanup

## Quality Assurance

### Static Analysis
- Use PHPStan for static analysis
- Configure strict analysis levels
- Integrate with CI/CD pipeline
- Address all reported issues
- Use Psalm as alternative analyzer
- Implement proper baseline management

### Code Quality Tools
- Use PHP_CodeSniffer with Laravel Pint
- Implement proper editor configuration
- Use static analysis tools
- Use security scanning tools
- Implement proper code review process
- Use automated code quality checks

### Performance Testing
- Implement load testing with tools like k6 or JMeter
- Monitor application performance regularly
- Implement proper performance baselines
- Use profiling tools for optimization
- Monitor database query performance
- Implement proper caching strategies

### Security Testing
- Use security scanning tools (RIPS, SonarQube, etc.)
- Implement proper penetration testing
- Monitor for security vulnerabilities
- Use OWASP guidelines for security testing
- Implement proper security controls
- Regular security audits and assessments

### Accessibility Testing
- Implement accessibility testing tools
- Follow WCAG guidelines
- Test with screen readers
- Implement proper ARIA attributes
- Test keyboard navigation
- Regular accessibility audits

## Dependencies Management

### Package Management
- Use Composer for dependency management
- Implement proper version constraints
- Regular dependency updates
- Monitor for security vulnerabilities
- Use lock files for reproducible builds
- Implement proper autoloading

### Framework Updates
- Keep Laravel updated to latest LTS
- Regular security patches
- Monitor for breaking changes
- Implement proper upgrade procedures
- Test thoroughly after updates
- Keep dependencies updated

### Third-Party Services
- Document all third-party services
- Implement proper service health checks
- Monitor service performance
- Implement proper error handling
- Plan for service outages
- Implement fallback strategies

## Compliance and Governance

### Data Privacy
- Implement GDPR compliance measures
- Implement proper data retention policies
- Use encryption for sensitive data
- Implement proper data access controls
- Document data processing activities
- Implement privacy by design principles

### Regulatory Compliance
- Follow industry regulations
- Document compliance measures
- Implement proper audit trails
- Regular compliance assessments
- Implement proper controls
- Monitor regulatory changes

### Internal Governance
- Implement proper change management
- Document architectural decisions
- Implement proper incident management
- Regular architecture reviews
- Implement proper documentation standards
- Monitor and measure governance effectiveness

This development guideline provides a comprehensive framework for building the CannaRewards Laravel application while maintaining code quality, security, and maintainability. All developers must adhere to these standards to ensure consistency across the codebase.