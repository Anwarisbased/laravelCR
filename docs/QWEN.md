also # Qwen Code Customization for Cannarewards API

## Project Overview
- **Project**: Cannarewards API
- **Technology**: Laravel (PHP)
- **Architecture**: Vertical slices approach with modules for different features
- **Current Focus**: Implementing features using Laravel with a vertical slice architecture

## System Prompt
Before doing anything else in each new chat instance, you must ingress the entire codebase (excluding the public/ folder) using appropriate file reading and search tools to understand the current state of the project.

## Architecture & Approach
- Using vertical slice architecture as documented in `laravel-vertical-slices/` directory
- Current implementation includes:
  - Authentication & User Management
  - Rank Progression system
- Following Laravel best practices and conventions

## Code Style & Conventions
- Follow Laravel coding standards
- Use Laravel's built-in authentication and authorization patterns
- Maintain consistency with existing codebase patterns
- Follow PSR-12 coding standards

## Key Directories & Files
- `laravel-vertical-slices/` - Contains implementation plans for different features
- `composer.json` - PHP dependencies
- `.env.example` - Environment configuration template
- `IMPLEMENTATION_PLAN.md` - Overall project plan
- `STANDARD_DEVELOPMENT_PATTERNS.md` - Standard development patterns and practices (source of truth for consistent implementation)

## Development Guidelines
- Respect existing code structure and conventions
- Write tests when implementing new features
- Follow existing naming conventions
- Use Laravel's service container and dependency injection appropriately
- Maintain security best practices, especially for user authentication
- **MANDATORY**: Follow ALL patterns defined in `STANDARD_DEVELOPMENT_PATTERNS.md` for all new code
- **MISSION CRITICAL**: Always follow Laravel's native conventions and established patterns which the framework is built around with specific tooling support:
  - Use `spatie/laravel-data` package for data objects with auto-validation, mapping, and transformation
  - Use Laravel's FormRequest classes for form validation with built-in error handling
  - Follow Laravel's service container patterns for dependency injection
  - Consider Laravel's JsonResource classes for API responses
  - Apply domain-driven design practices like Value Objects that integrate well with Laravel
  - This ensures smooth development and leverages Laravel's built-in tooling and features

## Important Notes
- This is an ongoing migration/implementation project
- The legacy codebase is referenced in various migration documents
- Use the .env.example as reference for environment variables
- Check the vertical slice documentation for feature-specific implementation details

## Debugging & Diagnostics
- **MUST** use Laravel Telescope and Xdebug to diagnose issues and failing tests
- Telescope and Xdebug are the default and only way to diagnose problems
- Always check Telescope logs first when debugging
- Use Xdebug for step-through debugging and deeper analysis
- Check Laravel logs (storage/logs/) as part of the diagnosis process

## Performance & Optimization
- Target response times: API endpoints < 200ms p95, Dashboard < 500ms
- Implement database query optimization with eager loading to prevent N+1 issues
- Use Redis for caching frequently accessed data with >95% cache hit ratio
- Implement proper database indexing strategy
- Use Laravel's queue system for background processing
- Implement HTTP caching with ETags where appropriate
- Optimize database connection pooling

## Security Considerations
- Implement comprehensive API rate limiting
- Ensure proper input validation on all endpoints
- Enforce strong password requirements
- Use Laravel Sanctum for secure API authentication
- Implement proper error handling without exposing system details
- Follow Laravel security best practices throughout implementation

## Laravel Sail & Docker Environment
- **Containerized Development**: This project uses Laravel Sail for containerized development
- **Command Execution**: Use `./vendor/bin/sail up` to start containers and `./vendor/bin/sail artisan` for Artisan commands
- **PHP Execution**: Run PHP commands inside containers using `./vendor/bin/sail php`
- **Database Access**: Database runs in Docker container, accessible via Sail
- **Redis Cache**: Redis service available in Docker environment
- **Queue Workers**: Run queue workers using `./vendor/bin/sail artisan queue:work`
- **Testing**: Execute tests via `./vendor/bin/sail artisan test` or `./vendor/bin/sail php vendor/bin/phpunit`

## Frontend Asset Building
- **Build Tool**: Using Vite instead of Laravel Mix for asset compilation
- **Configuration**: Vite configuration in `vite.config.js`
- **Frontend Dependencies**: Managed in `package.json`
- **Asset Commands**: Use `./vendor/bin/sail npm run dev` for development and `./vendor/bin/sail npm run build` for production
- **Hot Module Replacement**: Vite supports HMR during development with `./vendor/bin/sail npm run dev`

## Environment Configuration
- **Environment Variables**: Use `.env` file for local configuration (refer to `.env.example`)
- **Sail Services**: Database, Redis, and other services configured through Sail
- **Service Connections**: Services communicate through Docker network (e.g., app container connects to db container)

## Standard Development Patterns
The following patterns must be strictly followed for all new development:

### 1. Naming Conventions
- Controllers: PascalCase ending with `Controller` (e.g., `Api\AuthController`, `Api\ProfileController`)
- Services: PascalCase ending with `Service` (e.g., `UserService`, `OrderService`)
- Repositories: PascalCase ending with `Repository` (e.g., `UserRepository`, `OrderRepository`)
- DTOs: PascalCase ending with `DTO` (e.g., `SessionUserDTO`, `FullProfileDTO`)
- Data classes: PascalCase (e.g., `UserData`, `ProfileData`)
- Request classes: PascalCase ending with `Request` (e.g., `RegisterUserRequest`, `UpdateProfileRequest`)
- Value Objects: PascalCase (e.g., `UserId`, `EmailAddress`, `Points`, `ReferralCode`)
- Models: PascalCase singular (e.g., `User`, `Order`, `Product`)

### 2. Response Format Standards
- Success responses: `['success' => true, 'data' => $response_data, 'message' => optional_message]`
- Error responses: `['success' => false, 'message' => error_message, 'errors' => optional_validation_errors]`
- Use standardized response helpers: `ApiResponse::success($data, $message = null)` and `ApiResponse::error($message, $errors = null, $code = 400)`

### 3. Dependency Injection Patterns
- Always use constructor injection instead of method injection
- Define service dependencies as private properties in controllers and services

### 4. Validation Patterns
- All input validation must be performed using FormRequest classes
- FormRequest classes must have a `toCommand()` method that returns a command object with value objects

### 5. Value Object Standards
- All domain-relevant primitives must be wrapped in value objects:
  - Use `UserId::fromInt()` instead of `int`
  - Use `EmailAddress::fromString()` instead of `string`
  - Use `Points::fromInt()` instead of `int`
  - Use `PlainTextPassword::fromString()` instead of `string`

### 6. API Response Patterns
- Use Laravel Data classes throughout the service layer (e.g., `UserData`, `ProfileData`), not just at the API edge
- Services should return Data classes directly, eliminating unnecessary DTO transformations
- This approach provides better type safety, validation, and consistency throughout the application

### 7. Repository Patterns
- Follow standard repository interface patterns
- Use consistent method naming: `getUserCoreData`, `create{Resource}`, `update{Resource}`, etc.

### 8. Error Handling Patterns
- Use appropriate HTTP status codes
- Follow consistent error response formats
- Implement proper validation and business logic error handling