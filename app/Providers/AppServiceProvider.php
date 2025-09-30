<?php

namespace App\Providers;

// Add these three "use" statements at the top of the file
use App\Commands\GrantPointsCommandHandler;
use App\Infrastructure\EloquentApiWrapper;
use App\Infrastructure\WordPressApiWrapperInterface;
use App\Models\User;
use App\Observers\UserObserver;
use App\Settings\GeneralSettings;
use Illuminate\Support\ServiceProvider;
use App\Services\UserService;
use App\Services\RankService;
use App\Repositories\UserRepository;
use App\Repositories\CustomFieldRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;

// Define WordPress constants for compatibility
if (!defined('MINUTE_IN_SECONDS')) {
    define('MINUTE_IN_SECONDS', 60);
}
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
}
if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
}
if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
}
if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
}
if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);
}

// WordPress database result constants
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}
if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// Helper function to validate email addresses (WordPress equivalent)
if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // --- INTERFACE BINDINGS / SINGLETONS ---
        // No WordPress wrapper needed anymore - using pure Laravel

        // --- REPOSITORIES ---
        $this->app->singleton(\App\Repositories\UserRepository::class, \App\Repositories\UserRepository::class);
        $this->app->singleton(\App\Repositories\ProductRepository::class, \App\Repositories\ProductRepository::class);
        $this->app->singleton(\App\Repositories\RewardCodeRepository::class, \App\Repositories\RewardCodeRepository::class);
        $this->app->singleton(\App\Repositories\ActionLogRepository::class, \App\Repositories\ActionLogRepository::class);
        $this->app->singleton(\App\Repositories\AchievementRepository::class, \App\Repositories\AchievementRepository::class);
        $this->app->singleton(\App\Repositories\OrderRepository::class, \App\Repositories\OrderRepository::class);
        
        $this->app->singleton(\App\Repositories\CustomFieldRepository::class, \App\Repositories\CustomFieldRepository::class);

        // --- POLICIES ---
        $this->app->singleton(\App\Policies\UserMustBeAbleToAffordRedemptionPolicy::class);
        $this->app->singleton(\App\Policies\UserMustMeetRankRequirementPolicy::class);
        $this->app->singleton(\App\Policies\UnauthenticatedCodeIsValidPolicy::class);
        $this->app->singleton(\App\Policies\RewardCodeMustBeValidPolicy::class);
        $this->app->singleton(\App\Policies\EmailAddressMustBeUniquePolicy::class);
        $this->app->singleton(\App\Policies\RegistrationMustBeEnabledPolicy::class);

        // --- SERVICES (wired with autowiring where possible, explicit where needed) ---
        $this->app->singleton(\App\Services\RankService::class, function ($app) {
            return new \App\Services\RankService(
                $app->make(\App\Repositories\UserRepository::class)
            );
        });
        $this->app->singleton(\App\Services\ActionLogService::class);
        $this->app->singleton(\App\Services\ContextBuilderService::class, function ($app) {
            return new \App\Services\ContextBuilderService(
                $app->make(\App\Services\RankService::class),
                $app->make(\App\Repositories\ActionLogRepository::class),
                $app->make(\App\Repositories\UserRepository::class)
            );
        });
        $this->app->singleton(\App\Services\ConfigService::class, function ($app) {
            return new \App\Services\ConfigService(
                $app->make(\App\Services\RankService::class),
                $app->make(\App\Settings\GeneralSettings::class)
            );
        });
        $this->app->singleton(\App\Services\CDPService::class, function ($app) {
            return new \App\Services\CDPService(
                $app->make(\App\Services\RankService::class),
                $app->make(\App\Repositories\UserRepository::class)
            );
        });
        $this->app->singleton(\App\Services\RulesEngineService::class);
        
        $this->app->singleton(\App\Services\EconomyService::class, function ($app) {
            return new \App\Services\EconomyService(
                $app, // ContainerInterface
                [], // policy_map (populated in the service itself)
                [ // command_map
                    \App\Commands\RedeemRewardCommand::class => \App\Commands\RedeemRewardCommandHandler::class,
                    \App\Commands\GrantPointsCommand::class => \App\Commands\GrantPointsCommandHandler::class,
                    \App\Commands\ProcessProductScanCommand::class => \App\Commands\ProcessProductScanCommandHandler::class,
                    \App\Commands\ProcessUnauthenticatedClaimCommand::class => \App\Commands\ProcessUnauthenticatedClaimCommandHandler::class,
                ],
                $app->make(\App\Services\RankService::class),
                $app->make(\App\Services\ContextBuilderService::class),
                $app->make(\App\Repositories\UserRepository::class),
                $app->make(\App\Commands\GrantPointsCommandHandler::class)
            );
        });
        
        $this->app->singleton(\App\Services\UserService::class, function ($app) {
            return new \App\Services\UserService(
                $app, // ContainerInterface
                [ // policy_map
                    \App\Commands\CreateUserCommand::class => [
                        \App\Policies\EmailAddressMustBeUniquePolicy::class,
                        \App\Policies\RegistrationMustBeEnabledPolicy::class
                    ],
                ],
                $app->make(\App\Services\RankService::class),
                $app->make(\App\Repositories\CustomFieldRepository::class),
                $app->make(\App\Repositories\UserRepository::class),
                $app->make(\App\Repositories\OrderRepository::class)
            );
        });

        // --- EVENT-DRIVEN SERVICES ---
        // These are just registered. Their listeners will be attached in the boot method.
        $this->app->singleton(\App\Services\FirstScanBonusService::class);
        $this->app->singleton(\App\Services\StandardScanService::class);
        $this->app->singleton(\App\Services\GamificationService::class);
        $this->app->singleton(\App\Services\ReferralService::class, function ($app) {
            return new \App\Services\ReferralService(
                $app->make(\App\Services\CDPService::class),
                $app->make(\App\Repositories\UserRepository::class),
                $app->make(\App\Services\ReferralCodeService::class)
            );
        });
        
        // Register referral services
        $this->app->singleton(\App\Services\ReferralCodeService::class);
        $this->app->singleton(\App\Services\ReferralBonusService::class);
        $this->app->singleton(\App\Services\ReferralNudgeService::class);
        
        // Register referral services
        $this->app->singleton(\App\Services\ReferralCodeService::class);
        $this->app->singleton(\App\Services\ReferralBonusService::class);
        $this->app->singleton(\App\Services\ReferralNudgeService::class);
    }

    public function boot(): void
    {
        // Event listeners are now registered via EventServiceProvider
        // This approach uses Laravel's native event system instead of the custom event bus
    }
}