no# Standard Development Patterns for cannarewards-api

This document establishes the standard development patterns and practices to be followed throughout the cannarewards-api project. All developers must adhere to these patterns to ensure consistency and maintainability.

## Mission Critical: Laravel Native Conventions

It is mission critical that we follow Laravel's native conventions and established patterns which the framework is built around with specific tooling support:

1. **Laravel Data vs DTOs**: Use the `spatie/laravel-data` package for data objects, which provides auto-validation, mapping, and transformation features.

2. **FormRequest for validation**: Use Laravel's FormRequest classes for handling form validation with built-in error handling.

3. **Service Providers and Dependency Injection**: Follow Laravel's service container patterns.

4. **Resource Classes vs DTOs**: Consider Laravel's JsonResource classes for API responses.

5. **Value Objects**: Apply domain-driven design practices that integrate well with Laravel.

Following these conventions ensures smooth development and leverages Laravel's built-in tooling and features.

## 1. Naming Conventions

### Controller Naming
- Use PascalCase ending with `Controller`
- For API controllers: `Api\{Resource}Controller` (e.g., `Api\AuthController`, `Api\ProfileController`)
- Group related API endpoints in the same controller when possible

### Service Naming
- Use PascalCase ending with `Service` (e.g., `UserService`, `OrderService`)
- Services should follow the Single Responsibility Principle

### Repository Naming
- Use PascalCase ending with `Repository` (e.g., `UserRepository`, `OrderRepository`)

### DTO Naming
- Use PascalCase ending with `DTO` (e.g., `SessionUserDTO`, `FullProfileDTO`)
- For Laravel Data classes: Use PascalCase (e.g., `UserData`, `ProfileData`)

### Request Class Naming
- Use PascalCase ending with `Request` (e.g., `RegisterUserRequest`, `UpdateProfileRequest`)

### Value Object Naming
- Use PascalCase (e.g., `UserId`, `EmailAddress`, `Points`, `ReferralCode`)

### Model Naming
- Use PascalCase singular (e.g., `User`, `Order`, `Product`)

## 2. Response Format Standards

### Success Responses
Use standard Laravel API responses:
```php
return response()->json($data, 200);
// For created resources:
return response()->json($data, 201);
// For data with metadata:
return response()->json([
    'data' => $data,
    'meta' => $metadata
], 200);
```

### Error Responses
Use standard Laravel API responses with appropriate HTTP status codes:
```php
// For validation errors, Laravel FormRequest handles these automatically

// For general errors:
return response()->json([
    'message' => 'Error message',
    'errors' => $validation_errors // optional
], 422); // appropriate status code

// For not found:
return response()->json([
    'message' => 'Resource not found'
], 404);

// For server errors:
return response()->json([
    'message' => 'An error occurred'
], 500);
```

### Resource Classes
Leverage Laravel's JsonResource classes for consistent API responses when needed:
```php
return UserResource::make($user);
// or
return UserCollection::make($users);
```

## 3. Dependency Injection Patterns

### Controllers
```php
class UserController extends Controller
{
    private UserService $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
}
```

### Services
```php
class UserService
{
    private UserRepository $userRepository;
    
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
}
```

Always use constructor injection instead of method injection.

## 4. Validation Patterns

### FormRequest Classes
All input validation must be performed using FormRequest classes:

```php
class CreateUserRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:8'],
        ];
    }

    public function toCommand(): CreateUserCommand
    {
        $validated = $this->validated();
        return new CreateUserCommand(
            EmailAddress::fromString($validated['email']),
            PlainTextPassword::fromString($validated['password']),
            // ...
        );
    }
}
```

### Controller Usage
```php
public function store(CreateUserRequest $request, UserService $userService)
{
    $command = $request->toCommand();
    $result = $userService->handle($command);
    
    return response()->json($result);
}
```

## 5. Value Object Standards

### Consistent Usage
All domain-relevant primitives must be wrapped in value objects:
- Use `UserId::fromInt()` instead of `int`
- Use `EmailAddress::fromString()` instead of `string`
- Use `Points::fromInt()` instead of `int`
- Use `PlainTextPassword::fromString()` instead of `string`

### Service Method Signatures
```php
public function getUserData(UserId $userId): ?UserData
{
    // Implementation using value objects
}

public function grantReward(UserId $userId, Points $points, string $reason): void
{
    // Implementation using value objects where applicable
}
```

### Repository Method Signatures
```php
public function getUserCoreData(UserId $userId): ?User
{
    return User::find($userId->toInt());
}

public function createUser(EmailAddress $email, PlainTextPassword $password, string $firstName): int
{
    $user = User::create([
        'email' => $email->value,
        'password' => $password->value,
        // ...
    ]);
    return $user->id;
}
```

## 6. API Response Patterns

### Data Class Usage Throughout Application
- Use Laravel Data classes (e.g., `UserData`, `ProfileData`) throughout the service layer, not just at the API edge
- Services should return Data classes directly, eliminating unnecessary DTO transformations
- This approach provides better type safety, validation, and consistency throughout the application

Example:
```php
public function getProfile(Request $request): JsonResponse
{
    $userId = UserId::fromInt($request->user()->id);
    // Service returns Data class directly, no conversion needed
    $profileData = $this->userService->getFullProfileData($userId);

    return ApiResponse::success($profileData);
}
```

## 7. Repository Patterns

### Standard Repository Interface
All repositories should follow this pattern:

```php
class UserRepository
{
    /**
     * Retrieves the core user object.
     * This returns the Laravel User model.
     */
    public function getUserCoreData(UserId $userId): ?User
    {
        return User::find($userId->toInt());
    }
    
    /**
     * Creates a new user.
     */
    public function createUser(EmailAddress $email, PlainTextPassword $password, string $firstName): int
    {
        $user = User::create([
            'email' => $email->value,
            'password' => $password->value,
            // ...
        ]);

        if (!$user) {
            throw new \Exception('Failed to create user', 500);
        }

        return $user->id;
    }
    
    /**
     * Updates a user's core data.
     */
    public function updateUserData(UserId $userId, array $data): bool
    {
        $user = User::find($userId->toInt());
        if (!$user) {
            return false;
        }

        return $user->update($data);
    }
}
```

### Repository Method Naming
- `getUserCoreData` - retrieve the main model
- `create{Resource}` - create a new record
- `update{Resource}` - update an existing record
- `delete{Resource}` - soft delete a record
- `get{Resource}By{Field}` - retrieve by specific field

## 8. Error Handling Patterns

### Validation Errors
FormRequest classes will handle validation errors automatically.

### Data Transformation Errors
All Data classes must implement proper error handling in their transformation methods using the DataTransformationException:

```php
public static function fromModel(\App\Models\User $user): self
{
    try {
        return new self(
            // ... data mapping
        );
    } catch (\Throwable $e) {
        throw new \App\Exceptions\DataTransformationException(
            \App\Models\User::class,
            self::class,
            $e->getMessage()
        );
    }
}
```

### Application Errors
```php
try {
    $result = $this->userService->someOperation($userId);
} catch (Exception $e) {
    return response()->json([
        'success' => false,
        'message' => 'An error occurred while processing your request.'
    ], 500);
}
```

### Validation Policy Errors
```php
public function check($value): void
{
    if (!$value instanceof EmailAddress) {
        throw new \InvalidArgumentException('This policy requires an EmailAddress object.');
    }

    $user = User::where('email', (string) $value)->first();
    if ($user) {
        throw new Exception('An account with that email already exists.');
    }
}
```

## 9. Caching Standards

### Data Caching
Use the DataCachingService for caching Data objects to improve performance:

```php
use App\Services\DataCachingService;

class ProductService 
{
    private DataCachingService $dataCachingService;
    
    public function __construct(DataCachingService $dataCachingService) 
    {
        $this->dataCachingService = $dataCachingService;
    }
    
    public function getCachedProductData(int $productId): ?ProductData
    {
        $cached = $this->dataCachingService->getCachedProductData($productId);
        if ($cached) {
            return $cached;
        }
        
        // Fetch from database
        $product = Product::find($productId);
        $productData = ProductData::fromModel($product);
        
        // Cache for 1 hour
        $this->dataCachingService->cacheProductData($productId, $productData, 60);
        
        return $productData;
    }
}
```

### Cache Key Naming
Cache keys should follow the pattern: `data_objects:{data_class_name}:{identifier}`

## 10. Testing Standards

### Unit Tests
- Name test methods descriptively: `testUserCanRegisterSuccessfully()`
- Follow the Given-When-Then pattern
- Use appropriate mocks and stubs

### Data Object Tests
- Test `fromModel` transformations
- Verify validation attributes work correctly
- Test error handling with DataTransformationException

### Service Layer Tests
- Test the happy path and error conditions
- Verify that value objects are properly validated

## 11. Documentation Standards

### PHPDoc Comments
All public methods must have PHPDoc comments:

```php
/**
 * Retrieves the user's complete profile data.
 * 
 * @param UserId $userId The user ID to retrieve profile data for
 * @return FullProfileDTO The user's profile data
 * @throws Exception If user is not found
 */
public function getFullProfileData(UserId $userId): FullProfileDTO
{
    // Implementation
}
```

### Inline Comments
Use inline comments only when the code is complex and not self-explanatory.

## 12. Commit Message Standards

- Use present tense: "Add user authentication" not "Added user authentication"
- Start with a capital letter
- Use imperative mood: "Fix bug" not "Fixes bug"
- Limit first line to 50 characters
- If needed, add a blank line followed by a detailed description

Example:
```
Add user authentication endpoints

This commit adds the necessary API endpoints for user login and registration
functionality, including validation and error handling.
```

## Enforcement

This standard will be enforced through:
- Code reviews
- Documentation during onboarding
- Regular team discussions about best practices
- Automated tooling where applicable (coding standards, etc.)

All developers are expected to be familiar with and follow these patterns.