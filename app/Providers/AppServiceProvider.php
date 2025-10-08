<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\EconomyService;
use App\Services\UserService;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Most services will use Laravel's automatic dependency injection
        // Only register services that need special handling
        
        // Register repositories as singletons (these can be needed for performance)
        $this->app->singleton(\App\Repositories\UserRepository::class);
        $this->app->singleton(\App\Repositories\ProductRepository::class);
        $this->app->singleton(\App\Repositories\RewardCodeRepository::class);
        $this->app->singleton(\App\Repositories\ActionLogRepository::class);
        $this->app->singleton(\App\Repositories\AchievementRepository::class);
        $this->app->singleton(\App\Repositories\OrderRepository::class);
        $this->app->singleton(\App\Repositories\CustomFieldRepository::class);
        
        // Register services that have complex constructors
        $this->app->singleton(EconomyService::class, function ($app) {
            return EconomyService::createWithDependencies($app);
        });
        
        $this->app->singleton(UserService::class, function ($app) {
            return new UserService(
                $app, // ContainerInterface
                [], // Empty policy_map for now
                $app->make(\App\Services\RankService::class),
                $app->make(\App\Repositories\CustomFieldRepository::class),
                $app->make(\App\Repositories\UserRepository::class),
                $app->make(\App\Services\DataCachingService::class)
            );
        });
        
        // DataCachingService can be auto-resolved by Laravel's container
    }

    public function boot(): void
    {
        // Event listeners are registered via EventServiceProvider
        // This approach uses Laravel's native event system
    }
}