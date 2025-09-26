<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Common gate that Filament checks for panel access
        Gate::define('viewFilament', function ($user) {
            return $user !== null && ($user->is_admin ?? true);
        });
        
        // Panel-specific access gate for the 'admin' panel
        Gate::define('viewAdminPanel', function ($user) {
            return $user !== null && ($user->is_admin ?? true);
        });
        
        // Additional common gate
        Gate::define('admin', function ($user) {
            return $user !== null && ($user->is_admin ?? true);
        });
    }
}