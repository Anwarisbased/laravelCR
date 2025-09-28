# Qwen Code Customization for Cannarewards API

## Project Overview
- **Project**: Cannarewards API
- **Technology**: Laravel 12 (PHP)
- **Architecture**: Vertical slices approach with modules for different features
- **Current Focus**: Implementing features using Laravel with a vertical slice architecture
- **Framework Dependencies**: Laravel 12, Filament 3.2, Laravel Sanctum for API authentication
- **Containerization**: Laravel Sail with Docker containers

## Architecture & Approach
- Migrating away from WordPress to Laravel
- Using vertical slice architecture as documented in `laravel-vertical-slices/` directory
- Current implementation includes:
  - Authentication & User Management
  - Rank Progression system
- Following Laravel best practices and conventions, not WordPress patterns
- Pure Laravel implementation approach (Domain-Driven Design with Laravel native features)
- Leveraging Eloquent ORM, Laravel Events, Queues, Cache, and Authentication instead of WordPress equivalents

## Code Style & Conventions
- Follow Laravel coding standards
- Use Laravel's built-in authentication and authorization patterns
- Maintain consistency with existing codebase patterns
- Follow PSR-12 coding standards
- Implement everything in a way that's in line with Laravel's opinions and conventions
- Our business logic isn't unique - do everything the conventional Laravel way
- Use Eloquent models instead of WordPress database abstractions
- Use Laravel Events instead of custom event buses
- Use Laravel Queues for background processing
- Use Laravel Cache for performance optimization
- Use Laravel's testing framework for comprehensive test coverage

## Key Directories & Files
- `laravel-vertical-slices/` - Contains implementation plans for different features
- `app/` - Laravel application structure (Models, Controllers, Services, etc.)
- `routes/` - API and web routes
- `config/` - Laravel configuration files
- `database/` - Migrations, seeds, and database-related code
- `resources/` - Views, assets, and frontend resources
- `tests/` - Laravel test suite
- `composer.json` - PHP dependencies
- `package.json` - Frontend dependencies and Vite configuration
- `vite.config.js` - Vite asset building configuration
- `.env.example` - Environment configuration template
- `IMPLEMENTATION_PLAN.md` - Overall project plan
- `LARAVEL_ARCHITECTURE_APPROACH.md` - Architecture approach document
- `LARAVEL_MIGRATION_STRATEGY.md` - Migration strategy document
- `TECHNICAL_DEBT_REGISTER.md` - Technical debt tracking
- `PERFORMANCE_OPTIMIZATION_PLAN.md` - Performance optimization plan
- `@oldcodebase-repomix-output.xml` - Legacy WordPress codebase reference
- `README.md` - Laravel project documentation
- `compose.yaml` - Docker container configuration
- `artisan` - Laravel Artisan CLI tool

## Development Guidelines
- Respect existing code structure and conventions
- Write tests when implementing new features
- Follow existing naming conventions
- Use Laravel's service container and dependency injection appropriately
- Maintain security best practices, especially for user authentication
- Replace all WordPress-specific logic with conventional Laravel implementations
- Avoid WordPress patterns and embrace Laravel patterns instead
- Implement API endpoints using Laravel API Resources for consistent responses
- Use Laravel Form Requests for validation
- Use Eloquent relationships for data modeling
- Implement background jobs with Laravel's queue system
- Use Laravel's built-in caching mechanisms (Redis recommended)
- Follow DDD principles with Laravel's MVC architecture
- Use Vite for asset building and frontend development
- Follow Filament admin panel conventions for admin interfaces

## Important Notes
- This is an ongoing migration/implementation project
- The legacy codebase should always be used as a reference and is found at `@oldcodebase-repomix-output.xml`
- Migrating away from WordPress: all WordPress-specific ways of implementing logic must go away
- Do everything in a way that's in line with Laravel's opinions and conventions
- Use conventional Laravel approaches instead of WordPress patterns
- The old codebase should always be used as a reference and is found at `@oldcodebase-repomix-output.xml`
- Follow the migration strategy outlined in `LARAVEL_MIGRATION_STRATEGY.md`
- Address technical debt as outlined in `TECHNICAL_DEBT_REGISTER.md`
- Optimize for performance as specified in `PERFORMANCE_OPTIMIZATION_PLAN.md`
- Maintain API compatibility with existing clients during migration
- Implement comprehensive testing (unit, feature, and end-to-end tests)
- This is a Laravel Sail project running in Docker containers - use `sail` commands instead of local PHP/Artisan
- All development and debugging should happen within the container context
- Frontend assets are built using Vite, not Laravel Mix

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