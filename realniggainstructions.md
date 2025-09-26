Vertical Slice #1: The "Scan-First" Onboarding Flow
This is the most critical user journey from your old codebase. It's the "golden path" where a new user finds a QR code on a physical product and joins the program.
🎯 Goal: A non-logged-in user can scan a code, register, and automatically receive their welcome gift, but not the points for that first scan.
✅ Definition of Done: A new feature test, tests/Feature/OnboardingTest.php, passes. This test will simulate the entire flow from start to finish and assert the following:
A new user is created in the database.
The original reward code is marked as "used" and assigned to the new user.
An order for the Welcome Gift (Product ID 204) is created for the new user.
The new user's points balance is ZERO.
Action Plan & Implementation
Step 1: Create the "Definition of Done" Test
First, we write the test that defines success. This is our target.
code
Bash
php artisan make:test Feature/OnboardingTest
File: tests/Feature/OnboardingTest.php
code
PHP
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\RewardCode;
use App\Models\User;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_can_scan_a_code_register_and_receive_welcome_gift_with_zero_points(): void
    {
        // ARRANGE
        // 1. Seed the database with ranks and products, including the Welcome Gift (ID 204)
        // and a standard scannable product (SKU PWT-SCAN-001, awards 400 points).
        $this->seed();

        // 2. Create a valid, unused RewardCode.
        $rewardCode = RewardCode::create([
            'code' => 'GOLDEN-PATH-123',
            'sku' => 'PWT-SCAN-001', // This SKU awards points on a NORMAL scan
            'is_used' => false,
        ]);

        // ACT - STEP 1: A new user "scans" the code via the unauthenticated endpoint.
        $unauthClaimResponse = $this->postJson('/api/rewards/v2/unauthenticated/claim', [
            'code' => $rewardCode->code,
        ]);

        // ASSERT - STEP 1
        $unauthClaimResponse->assertStatus(200);
        $unauthClaimResponse->assertJsonPath('data.status', 'registration_required');
        $registrationToken = $unauthClaimResponse->json('data.registration_token');

        // ACT - STEP 2: The user uses the token to complete registration.
        $newUserEmail = 'onboarding-user@example.com';
        $registerResponse = $this->postJson('/api/auth/register-with-token', [
            'email' => $newUserEmail,
            'password' => 'password123',
            'firstName' => 'Golden',
            'agreedToTerms' => true,
            'registration_token' => $registrationToken,
        ]);

        // ASSERT - STEP 2
        $registerResponse->assertStatus(200);
        $registerResponse->assertJsonStructure(['success', 'data' => ['token']]);

        // ASSERT - FINAL OUTCOME
        $this->assertDatabaseHas('users', ['email' => $newUserEmail]);
        $newUser = User::where('email', $newUserEmail)->first();

        // 1. Assert the Welcome Gift (ID 204) order was created.
        $this->assertDatabaseHas('orders', [
            'user_id' => $newUser->id,
            'product_id' => 204,
            'is_redemption' => true,
        ]);

        // 2. CRITICAL: Assert points balance is ZERO. The first scan awards the gift, not points.
        $newUser->refresh();
        $this->assertEquals(0, $newUser->meta['_canna_points_balance']);
        $this->assertEquals(0, $newUser->meta['_canna_lifetime_points']);

        // 3. Assert the reward code was consumed correctly.
        $this->assertDatabaseHas('reward_codes', [
            'code' => $rewardCode->code,
            'is_used' => true,
            'user_id' => $newUser->id,
        ]);
    }
}
Step 2: Refactor the Scan Handler to be Event-Driven
Your old ProcessProductScanCommandHandler was doing too much. We'll refactor it to be "anti-fragile". Its only job is to validate, log the scan, and then broadcast a specific event: first_product_scanned or standard_product_scanned. This decouples the act of scanning from the consequences.
File: app/Commands/ProcessProductScanCommandHandler.php
code
PHP
<?php
namespace App\Commands;

use App\Repositories\RewardCodeRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ActionLogRepository;
use App\Services\ActionLogService;
use App\Services\ContextBuilderService;
use App\Includes\EventBusInterface;
use App\Infrastructure\WordPressApiWrapperInterface;
use Exception;

final class ProcessProductScanCommandHandler {
    // ... constructor properties ...

    public function __construct(
        // ... existing dependencies ...
        private ContextBuilderService $contextBuilder,
        private WordPressApiWrapperInterface $wp
    ) {
        // ... assignments ...
        $this->contextBuilder = $contextBuilder;
        $this->wp = $wp;
    }

    public function handle(ProcessProductScanCommand $command): array {
        $code_data = $this->rewardCodeRepo->findValidCode($command->code);
        if (!$code_data) { 
            throw new Exception('This code is invalid or has already been used.'); 
        }
        
        $product_id = $this->productRepo->findIdBySku(\App\Domain\ValueObjects\Sku::fromString($code_data->sku));
        if (!$product_id) { 
            throw new Exception('The product associated with this code could not be found.'); 
        }
        
        // --- ANTI-FRAGILE REFACTOR ---

        // 1. Log the scan to establish its history and count.
        $this->logService->record($command->userId->toInt(), 'scan', $product_id->toInt());
        $scan_count = $this->logRepo->countUserActions($command->userId->toInt(), 'scan');
        $is_first_scan = ($scan_count === 1);

        // 2. Mark the code as used immediately.
        $this->rewardCodeRepo->markCodeAsUsed($code_data->id, $command->userId);
        
        // 3. Build the rich context for the event.
        $product_post = (object)['ID' => $product_id->toInt()];
        $context = $this->contextBuilder->build_event_context($command->userId->toInt(), $product_post);

        // 4. BE EXPLICIT: Dispatch a different event based on the business context.
        if ($is_first_scan) {
            \Illuminate\Support\Facades\Log::info('Dispatching "first_product_scanned" event');
            $this->eventBus->dispatch('first_product_scanned', $context);
        } else {
            \Illuminate\Support\Facades\Log::info('Dispatching "standard_product_scanned" event');
            $this->eventBus->dispatch('standard_product_scanned', $context);
        }
        
        // 5. Return a generic, immediate success message.
        $product = $this->wp->getProduct($product_id->toInt());
        return [
            'success' => true,
            'message' => ($product ? $product->name : 'Product') . ' scanned successfully!',
        ];
    }
}
Step 3: Create the Event Listener Services
Now we create small, single-purpose services that listen for the events dispatched in Step 2.
File: app/Services/FirstScanBonusService.php
code
PHP
<?php
namespace App\Services;

use App\Commands\RedeemRewardCommand;
use App\Commands\RedeemRewardCommandHandler;
use App\Domain\ValueObjects\UserId;

final class FirstScanBonusService {
    public function __construct(
        private ConfigService $configService,
        private RedeemRewardCommandHandler $redeemHandler
    ) {}

    public function awardWelcomeGift(array $payload): void {
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        
        if ($user_id > 0) {
            $welcome_reward_id = $this->configService->getWelcomeRewardProductId();
            if ($welcome_reward_id > 0) {
                // This re-uses your existing redemption logic to "purchase" the gift for 0 points.
                $this->redeemHandler->handle(new RedeemRewardCommand(
                    UserId::fromInt($user_id), 
                    \App\Domain\ValueObjects\ProductId::fromInt($welcome_reward_id), 
                    []
                ));
            }
        }
    }
}
File: app/Services/StandardScanService.php
code
PHP
<?php
namespace App\Services;

use App\Commands\GrantPointsCommand;
use App\Commands\GrantPointsCommandHandler;
use App\Repositories\ProductRepository;

final class StandardScanService {
    public function __construct(
        private ProductRepository $productRepo,
        private GrantPointsCommandHandler $grantPointsHandler
    ) {}

    public function grantPointsForStandardScan(array $payload): void {
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        $product_id = $payload['product_snapshot']['identity']['product_id'] ?? 0;
        $product_name = $payload['product_snapshot']['identity']['product_name'] ?? 'product';

        if ($user_id > 0 && $product_id > 0) {
            $base_points = $this->productRepo->getPointsAward(\App\Domain\ValueObjects\ProductId::fromInt($product_id));
            if ($base_points > 0) {
                $command = new GrantPointsCommand(
                    \App\Domain\ValueObjects\UserId::fromInt($user_id),
                    \App\Domain\ValueObjects\Points::fromInt($base_points),
                    'Product Scan: ' . $product_name
                );
                $this->grantPointsHandler->handle($command);
            }
        }
    }
}
Step 4: Implement the register-with-token Flow
This is the glue that connects the unauthenticated scan to the new user account.
File: app/Commands/RegisterWithTokenCommandHandler.php
code
PHP
<?php
namespace App\Commands;

use App\Services\UserService;
use App\Services\EconomyService;
use App\Infrastructure\WordPressApiWrapperInterface;
use App\Domain\ValueObjects\UserId;
use Exception;

final class RegisterWithTokenCommandHandler {
    public function __construct(
        private UserService $userService, 
        private EconomyService $economyService,
        private WordPressApiWrapperInterface $wp
    ) {}

    public function handle(RegisterWithTokenCommand $command): array {
        $claim_code = $this->wp->getTransient('reg_token_' . $command->registration_token);
        if (false === $claim_code) {
            throw new Exception('Invalid or expired registration token.', 403);
        }

        // 1. Create the user.
        $createUserCmd = new \App\Commands\CreateUserCommand(
            $command->email, $command->password, $command->first_name, $command->last_name,
            $command->phone, $command->agreed_to_terms, $command->agreed_to_marketing, $command->referral_code
        );
        $createResult = $this->userService->handle($createUserCmd);
        $new_user_id = $createResult['userId'];

        if (!$new_user_id) {
            throw new Exception('Failed to create user during token registration.');
        }

        // 2. Now that the user exists, dispatch the standard ProcessProductScanCommand.
        // The refactored handler will correctly see this is the first scan and fire the right event.
        $processScanCmd = new ProcessProductScanCommand(
            UserId::fromInt($new_user_id), 
            \App\Domain\ValueObjects\RewardCode::fromString($claim_code)
        );
        $this->economyService->handle($processScanCmd);

        // 3. Clean up and log in.
        $this->wp->deleteTransient('reg_token_' . $command->registration_token);
        return $this->userService->login(
            (string) $command->email,
            $command->password->getValue()
        );
    }
}
You will also need to create the RegisterWithTokenCommand DTO and the RegisterWithTokenRequest form request class, similar to your existing CreateUser... classes.
Step 5: Wire Everything Up
Finally, update your service provider to register the new services and event listeners.
File: app/Providers/AppServiceProvider.php
code
PHP
// In register() method:
$this->app->singleton(\App\Services\FirstScanBonusService::class);
$this->app->singleton(\App\Services\StandardScanService::class);

// In boot() method:
$eventBus = $this->app->make(\App\Includes\EventBusInterface::class);
$container = $this->app;

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
Now, run php artisan test --filter=OnboardingTest. Once it passes, this entire critical feature is done.
Vertical Slice #2: Password Reset
🎯 Goal: Users can securely reset their password via email.
✅ Definition of Done: A new feature test, tests/Feature/PasswordResetTest.php, passes. It will:
Create a user.
Hit the request-password-reset endpoint.
Fish the reset token out of the log (since we don't send real emails in tests).
Hit the perform-password-reset endpoint with the token and a new password.
Attempt to log in with the new password and assert success.
Action Plan:
This is a more straightforward slice. The code you need is already in the previous answer.
Create the Test: php artisan make:test Feature/PasswordResetTest and implement the logic described above.
Update UserService: Add the request_password_reset and perform_password_reset methods.
Update AuthController: Add the requestPasswordReset and performPasswordReset methods.
Update routes/api.php: Add the two new routes to the public /auth group.
Run php artisan test --filter=PasswordResetTest. When it passes, you're done.
Vertical Slice #3: Admin QR Code Generator
🎯 Goal: An admin can generate a CSV of unique QR codes for a specific product.
✅ Definition of Done: A new feature test, tests/Feature/Admin/QrCodeGenerationTest.php, passes. It will:
Create an admin user.
Seed products into the database.
Use actingAs to authenticate as the admin.
Hit the Filament page's Livewire generate action directly with a valid product ID and quantity.
Assert that the response is a StreamedResponse and contains the correct CSV headers.
Assert that the correct number of reward_codes now exist in the database.
Action Plan:
Create the Test: php artisan make:test Feature/Admin/QrCodeGenerationTest and implement the logic.
Create the Filament Page: Follow the plan from the previous response to create the GenerateQrCodes.php page class and its associated blade view.
Register the Page: Add the custom page to your ProductResource::getPages().
Run the test. When it passes, your admin feature is ported.
This vertical slice approach, with each slice defined by a passing test, will get you to full feature parity quickly and reliably. It also leaves you with a comprehensive, end-to-end test suite that will protect you from regressions in the future.