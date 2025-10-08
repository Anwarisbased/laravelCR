# Development Foundation Guidelines

## Overview

This document outlines the core architectural patterns and standards for the CannaRewards API foundation. These guidelines focus on establishing standardized plumbing and mid-level functionality that follows Laravel conventions, creating a robust base for future feature development.

## Core Architecture Principles

1. **Follow Laravel Conventions**: Adhere to Laravel's native patterns and tooling support
2. **Consistent API Responses**: Use standardized Data objects for all API responses
3. **Value Object Usage**: Wrap domain primitives in value objects for validation and consistency
4. **Dependency Injection**: Use Laravel's service container patterns consistently
5. **Error Handling**: Implement consistent error handling patterns throughout
6. **Performance**: Build with caching and performance optimization in mind

## Service Layer Architecture

### Standard Service Pattern
```php
class StandardService
{
    private RepositoryInterface $repository;
    private OtherService $otherService;
    
    public function __construct(
        RepositoryInterface $repository,
        OtherService $otherService
    ) {
        $this->repository = $repository;
        $this->otherService = $otherService;
    }
    
    public function doSomething(DomainValueObject $id): DataObject
    {
        // Implementation that returns DataObject directly
        $model = $this->repository->getModel($id);
        return DataObject::fromModel($model);
    }
}
```

### Value Object Standard
All domain-relevant primitives must be wrapped in value objects:
- `UserId::fromInt()` instead of `int`
- `EmailAddress::fromString()` instead of `string`
- `Points::fromInt()` instead of `int`
- `PlainTextPassword::fromString()` instead of `string`

## Data Flow Architecture

### From Database to API Response
```
Eloquent Model → Data Object → API Response (via ApiResponse helper)
```

All services should return Data objects directly, not DTOs or raw models.

### Data Object Pattern
```php
#[MapName(SnakeCaseMapper::class)]
class StandardData extends Data
{
    public function __construct(
        #[Validation(['required', 'integer'])]
        public int $id,
        #[Validation(['required', 'string', 'max:255'])]
        public string $name,
        // Add validation to all fields that need it
    ) {}
    
    public static function fromModel(Model $model): self
    {
        try {
            return new self(
                id: $model->id,
                name: $model->name,
                // Map other fields
            );
        } catch (\Throwable $e) {
            throw new DataTransformationException(
                Model::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}
```

## API Response Standardization

### Response Format
All API endpoints must use the standardized format:
```php
// Success responses
ApiResponse::success($data, $message = null);

// Error responses  
ApiResponse::error($message, $errors = null, $code = 400);
```

### Controller Pattern
```php
class StandardController extends Controller
{
    private StandardService $service;
    
    public function __construct(StandardService $service)
    {
        $this->service = $service;
    }
    
    public function show(Request $request, int $id): JsonResponse
    {
        $data = $this->service->getSomething(UserId::fromInt($id));
        return ApiResponse::success($data);
    }
}
```

## Validation Architecture

### FormRequest Pattern
All input validation must use FormRequest classes:
```php
class StandardRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            // Define validation rules
        ];
    }
    
    public function toCommand(): CommandObject
    {
        $validated = $this->validated();
        return new CommandObject(
            EmailAddress::fromString($validated['email'])
        );
    }
}
```

## Repository Pattern

### Standard Repository Interface
```php
interface StandardRepositoryInterface
{
    public function getModelById(StandardId $id): ?Model;
    public function createModel(ValueObject $data): int;
    public function updateModel(StandardId $id, array $data): bool;
}
```

### Repository Implementation
```php
class StandardRepository implements StandardRepositoryInterface
{
    public function getModelById(StandardId $id): ?Model
    {
        return Model::find($id->toInt());
    }
    
    public function createModel(ValueObject $data): int
    {
        $model = Model::create([
            'field' => $data->getValue(),
        ]);
        return $model->id;
    }
    
    public function updateModel(StandardId $id, array $data): bool
    {
        $model = Model::find($id->toInt());
        return $model ? $model->update($data) : false;
    }
}
```

## Error Handling Patterns

### Data Transformation Errors
All Data objects must implement error handling:
```php
public static function fromModel(Model $model): self
{
    try {
        // Transformation logic
    } catch (\Throwable $e) {
        throw new DataTransformationException(
            Model::class,
            self::class,
            $e->getMessage()
        );
    }
}
```

### Application Errors
Use consistent error responses:
```php
try {
    $result = $this->service->doSomething($input);
    return ApiResponse::success($result);
} catch (Exception $e) {
    return ApiResponse::error($e->getMessage());
}
```

## Caching Strategy

### Data Caching
Use the DataCachingService for frequently accessed data:
```php
class ServiceWithCaching
{
    private DataCachingService $cache;
    
    public function __construct(DataCachingService $cache)
    {
        $this->cache = $cache;
    }
    
    public function getCachedData(int $id): DataObject
    {
        $cached = $this->cache->getCachedData($id);
        if ($cached) {
            return $cached;
        }
        
        $data = $this->getFreshData($id);
        $this->cache->cacheData($id, $data);
        return $data;
    }
}
```

## Testing Standards

### Data Object Tests
Test all Data object transformations:
```php
public function test_data_from_model_transforms_correctly(): void
{
    $model = Model::factory()->create();
    $data = DataObject::fromModel($model);
    
    $this->assertInstanceOf(DataObject::class, $data);
    $this->assertEquals($model->id, $data->id);
}
```

### Service Layer Tests
Verify value objects and Data object returns:
```php
public function test_service_returns_data_object(): void
{
    $service = new StandardService($this->repository);
    $result = $service->doSomething(UserId::fromInt(1));
    
    $this->assertInstanceOf(DataObject::class, $result);
}
```

## Naming Conventions

### Classes
- Controllers: `ResourceController` (e.g., `UserController`)
- Services: `ResourceService` (e.g., `UserService`)  
- Repositories: `ResourceRepository` (e.g., `UserRepository`)
- Data Objects: `ResourceData` (e.g., `UserData`)
- DTOs: `ResourceDTO` (e.g., `UserDTO`)
- Requests: `ActionResourceRequest` (e.g., `CreateUserRequest`)
- Value Objects: `Resource` (e.g., `UserId`, `EmailAddress`)

### Methods
- Repository: `getModel`, `createModel`, `updateModel`, `deleteModel`
- Service: Domain-focused method names like `getUserProfile`, `processOrder`

## Performance Considerations

### Database Optimization
- Use eager loading to prevent N+1 queries
- Implement proper database indexes
- Use caching for frequently accessed data

### API Response Optimization
- Only include necessary fields in Data objects
- Use caching for expensive operations
- Implement pagination for collection endpoints

## Laravel Conventions

### Dependency Injection
Always use constructor injection, never method injection:
```php
// Correct
public function __construct(StandardService $service) { }

// Incorrect
public function someMethod(StandardService $service) { }
```

### Configuration
Use Laravel's configuration system for environment-specific settings:
```php
// config/services.php
'rewards' => [
    'api_endpoint' => env('REWARDS_API_ENDPOINT'),
],
```

These patterns ensure you're taking full advantage of Laravel's built-in tooling and conventions, creating a solid foundation for future feature development.