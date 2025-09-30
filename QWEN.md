also # Qwen Code Customization for Cannarewards API

## Project Overview
- **Project**: Cannarewards API
- **Technology**: Laravel (PHP)
- **Architecture**: Vertical slices approach with modules for different features
- **Current Focus**: Implementing features using Laravel with a vertical slice architecture

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

## Development Guidelines
- Respect existing code structure and conventions
- Write tests when implementing new features
- Follow existing naming conventions
- Use Laravel's service container and dependency injection appropriately
- Maintain security best practices, especially for user authentication

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