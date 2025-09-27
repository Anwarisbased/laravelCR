<?php

namespace App\Providers;

// Add these three "use" statements at the top of the file
use App\Commands\GrantPointsCommandHandler;
use App\Infrastructure\EloquentApiWrapper;
use App\Infrastructure\WordPressApiWrapperInterface;
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
        $this->app->singleton(WordPressApiWrapperInterface::class, EloquentApiWrapper::class);
        $this->app->singleton(\App\Includes\EventBusInterface::class, \App\Includes\SimpleEventBus::class);

        // --- REPOSITORIES (all depend on the wrapper) ---
        $this->app->singleton(\App\Repositories\UserRepository::class, fn($app) => new \App\Repositories\UserRepository($app->make(WordPressApiWrapperInterface::class)));
        $this->app->singleton(\App\Repositories\ProductRepository::class, fn($app) => new \App\Repositories\ProductRepository($app->make(WordPressApiWrapperInterface::class)));
        $this->app->singleton(\App\Repositories\RewardCodeRepository::class, fn($app) => new \App\Repositories\RewardCodeRepository($app->make(WordPressApiWrapperInterface::class)));
        $this->app->singleton(\App\Repositories\ActionLogRepository::class, fn($app) => new \App\Repositories\ActionLogRepository($app->make(WordPressApiWrapperInterface::class)));
        $this->app->singleton(\App\Repositories\AchievementRepository::class, fn($app) => new \App\Repositories\AchievementRepository($app->make(WordPressApiWrapperInterface::class)));
        $this->app->singleton(\App\Repositories\OrderRepository::class, fn($app) => new \App\Repositories\OrderRepository($app->make(WordPressApiWrapperInterface::class)));
        
        $this->app->singleton(\App\Repositories\CustomFieldRepository::class, fn($app) => new \App\Repositories\CustomFieldRepository($app->make(WordPressApiWrapperInterface::class)));

        // --- POLICIES ---
        $this->app->singleton(\App\Policies\UserMustBeAbleToAffordRedemptionPolicy::class);
        $this->app->singleton(\App\Policies\UserMustMeetRankRequirementPolicy::class);
        $this->app->singleton(\App\Policies\UnauthenticatedCodeIsValidPolicy::class);
        $this->app->singleton(\App\Policies\RewardCodeMustBeValidPolicy::class);
        $this->app->singleton(\App\Policies\EmailAddressMustBeUniquePolicy::class);
        $this->app->singleton(\App\Policies\RegistrationMustBeEnabledPolicy::class);

        // --- SERVICES (wired with autowiring where possible, explicit where needed) ---
        $this->app->singleton(\App\Services\RankService::class);
        $this->app->singleton(\App\Services\ActionLogService::class);
        $this->app->singleton(\App\Services\ContextBuilderService::class);
        $this->app->singleton(\App\Services\ConfigService::class, function ($app) {
            return new \App\Services\ConfigService(
                $app->make(\App\Services\RankService::class),
                $app->make(WordPressApiWrapperInterface::class),
                $app->make(\App\Settings\GeneralSettings::class) // <-- Use the new settings class
            );
        });
        $this->app->singleton(\App\Services\CDPService::class);
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
                $app->make(\App\Includes\EventBusInterface::class),
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
                $app->make(\App\Repositories\OrderRepository::class),
                $app->make(WordPressApiWrapperInterface::class)
            );
        });

        // --- EVENT-DRIVEN SERVICES ---
        // These are just registered. Their listeners will be attached in the boot method.
        $this->app->singleton(\App\Services\FirstScanBonusService::class);
        $this->app->singleton(\App\Services\StandardScanService::class);
        $this->app->singleton(\App\Services\GamificationService::class);
        $this->app->singleton(\App\Services\ReferralService::class);
    }

    public function boot(): void
    {
        $eventBus = $this->app->make(\App\Includes\EventBusInterface::class);
        $container = $this->app;

        // Lazy-load services only when their trigger event is actually fired.

        // Onboarding logic is now isolated to this single, explicit event.
        $eventBus->listen('first_product_scanned', function ($payload) use ($container) {
            $container->make(\App\Services\FirstScanBonusService::class)->awardWelcomeGift($payload);
        });

        // Standard point-earning logic is also isolated.
        $eventBus->listen('standard_product_scanned', function ($payload) use ($container) {
            $container->make(\App\Services\StandardScanService::class)->grantPointsForStandardScan($payload);
        });

        // This connects 'first_product_scanned' to the referral conversion logic.
        $eventBus->listen('first_product_scanned', function ($payload) use ($container) {
            $container->make(\App\Services\ReferralService::class)->handle_referral_conversion($payload);
        });

        // This connects the Gamification engine to all relevant events.
        $events_to_gamify = ['first_product_scanned', 'standard_product_scanned', 'user_rank_changed', 'reward_redeemed'];
        foreach ($events_to_gamify as $event_name) {
            $eventBus->listen($event_name, function ($payload, $event) use ($container) {
                 $container->make(\App\Services\GamificationService::class)->handle_event($payload, $event);
            });
        }
    }
}