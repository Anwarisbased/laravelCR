<?php
namespace App\Commands;

use App\Services\UserService;
use App\Services\EconomyService;
use App\Domain\ValueObjects\UserId;
use Exception;
use Illuminate\Support\Facades\Cache;

final class RegisterWithTokenCommandHandler {
    private UserService $userService;
    private EconomyService $economyService;

    public function __construct(
        UserService $userService, 
        EconomyService $economyService
    ) {
        $this->userService = $userService;
        $this->economyService = $economyService;
    }

    /**
     * @throws Exception on failure
     */
    public function handle(RegisterWithTokenCommand $command): array {
        $cache_key = 'reg_token_' . $command->registration_token;
        $claim_code = Cache::get($cache_key);
        
        if ($claim_code === null) {
            throw new Exception('Invalid or expired registration token.', 403);
        }

        // 1. Create the user.
        $create_user_command = new \App\Commands\CreateUserCommand(
            $command->email,
            $command->password,
            $command->first_name,
            $command->last_name,
            $command->phone,
            $command->agreed_to_terms,
            $command->agreed_to_marketing,
            $command->referral_code
        );
        $create_user_result = $this->userService->handle($create_user_command);
        $new_user_id = $create_user_result['userId'];

        if (!$new_user_id) {
            throw new Exception('Failed to create user during token registration.');
        }

        // 2. Now that the user exists, dispatch the standard ProcessProductScanCommand.
        // This command is now simple and just broadcasts an event. Our new services will listen and
        // correctly identify it as a first scan.
        $process_scan_command = new \App\Commands\ProcessProductScanCommand(
            UserId::fromInt($new_user_id), 
            \App\Domain\ValueObjects\RewardCode::fromString($claim_code)
        );
        $this->economyService->handle($process_scan_command);

        // 3. All successful, delete the token.
        Cache::forget($cache_key);
        
        // 4. Log the user in.
        return $this->userService->login(
            (string) $command->email,
            $command->password->getValue()
        );
    }
}