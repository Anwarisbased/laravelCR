<?php

namespace App\Services;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Domain\MetaKeys;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\PhoneNumber;
use App\Domain\ValueObjects\ReferralCode;
use App\DTO\FullProfileDTO;
use App\DTO\RankDTO;
use App\DTO\SessionUserDTO;
use App\DTO\ShippingAddressDTO;
use App\Repositories\CustomFieldRepository;
use App\Repositories\UserRepository;
use App\Repositories\OrderRepository;
use Psr\Container\ContainerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Auth\Passwords\PasswordBroker;

/**
 * User Service (Command Bus & Data Fetcher)
 */
final class UserService {
    private array $command_map = [];
    private ContainerInterface $container; // We still need this to instantiate handlers and policies
    private array $policy_map = [];
    private RankService $rankService;
    private CustomFieldRepository $customFieldRepo;
    private UserRepository $userRepo;
    private ?OrderRepository $orderRepo = null;

    public function __construct(
        ContainerInterface $container, // Keep container for lazy-loading handlers/policies
        array $policy_map,
        RankService $rankService,
        CustomFieldRepository $customFieldRepo,
        UserRepository $userRepo,
        OrderRepository $orderRepo = null
    ) {
        $this->container = $container;
        $this->policy_map = $policy_map;
        $this->rankService = $rankService;
        $this->customFieldRepo = $customFieldRepo;
        $this->userRepo = $userRepo;
        $this->orderRepo = $orderRepo;
        
        $this->registerCommandHandlers();
    }

    private function registerCommandHandlers(): void {
        $this->command_map = [
            \App\Commands\CreateUserCommand::class => \App\Commands\CreateUserCommandHandler::class,
            \App\Commands\UpdateProfileCommand::class => \App\Commands\UpdateProfileCommandHandler::class,
            \App\Commands\RegisterWithTokenCommand::class => \App\Commands\RegisterWithTokenCommandHandler::class,
        ];
    }

    public function handle($command) {
        $command_class = get_class($command);
        
        $policies_for_command = $this->policy_map[$command_class] ?? [];
        foreach ($policies_for_command as $policy_class) {
            $policy = $this->container->get($policy_class);
            
            // Check which type of policy this is and call it appropriately
            if ($policy instanceof \App\Policies\AuthorizationPolicyInterface) {
                // Authorization policies need a user ID and the command
                // For now, we'll pass a dummy user ID for registration commands
                $user_id = new \App\Domain\ValueObjects\UserId(0);
                $policy->check($user_id, $command);
            } elseif ($policy instanceof \App\Policies\ValidationPolicyInterface) {
                // Validation policies need specific values from the command
                if ($command instanceof \App\Commands\CreateUserCommand && 
                    $policy instanceof \App\Policies\EmailAddressMustBeUniquePolicy) {
                    // For email uniqueness, pass the email address from the command
                    $policy->check($command->email);
                } else {
                    // For other validation policies, pass the entire command for now
                    // This might need to be refined based on specific policy requirements
                    $policy->check($command);
                }
            }
        }

        if (!isset($this->command_map[$command_class])) {
            throw new Exception("No handler registered for user command: {$command_class}");
        }
        
        $handler_class = $this->command_map[$command_class];
        $handler = $this->container->get($handler_class);

        return $handler->handle($command);
    }
    
    public function get_user_session_data(UserId $userId): SessionUserDTO {
        $user_data = $this->userRepo->getUserCoreData($userId);
        if (!$user_data) {
            throw new Exception("User with ID {$userId->toInt()} not found.");
        }

        $rank_dto = $this->rankService->getUserRank($userId);
        $referral_code = $this->userRepo->getReferralCode($userId);

        // Get first and last name from user meta
        $first_name = $user_data->meta['first_name'] ?? '';
        $last_name = $user_data->meta['last_name'] ?? '';

        $session_dto = new SessionUserDTO(
            id: $userId,
            firstName: $first_name,
            lastName: $last_name,
            email: \App\Domain\ValueObjects\EmailAddress::fromString($user_data->email),
            pointsBalance: \App\Domain\ValueObjects\Points::fromInt($this->userRepo->getPointsBalance($userId)),
            rank: $rank_dto,
            shippingAddress: $this->userRepo->getShippingAddressDTO($userId),
            referralCode: $referral_code,
            featureFlags: new \stdClass()
        );

        return $session_dto;
    }
    
    public function get_current_user_session_data(): SessionUserDTO {
        $current_user = auth()->user();
        if (!$current_user) {
            throw new Exception("User not authenticated.", 401);
        }
        return $this->get_user_session_data(UserId::fromInt($current_user->id));
    }
    
    public function get_full_profile_data(UserId $userId): FullProfileDTO {
        $user_data = $this->userRepo->getUserCoreData($userId);
        if (!$user_data) {
            throw new Exception("User with ID {$userId->toInt()} not found.");
        }

        $custom_fields_definitions = $this->customFieldRepo->getFieldDefinitions();
        $custom_fields_values      = [];
        foreach ($custom_fields_definitions as $field) {
            $value = $this->userRepo->getUserMeta($userId, $field['key'], true);
            if (!empty($value)) {
                $custom_fields_values[$field['key']] = $value;
            }
        }
        
        $shipping_dto = $this->userRepo->getShippingAddressDTO($userId);

        // Get phone number and referral code from user meta, converting to Value Objects
        $phone_meta = $this->userRepo->getUserMeta($userId, 'phone_number', true);
        // Fix: Use the correct method to get referral code
        $referral_code_meta = $this->userRepo->getReferralCode($userId);
        
        // Get first and last name from user meta
        $first_name = $user_data->meta['first_name'] ?? '';
        $last_name = $user_data->meta['last_name'] ?? '';
        
        $profile_dto = new FullProfileDTO(
            firstName: $first_name,
            lastName: $last_name,
            phoneNumber: !empty($phone_meta) ? PhoneNumber::fromString($phone_meta) : null,
            referralCode: !empty($referral_code_meta) ? ReferralCode::fromString($referral_code_meta) : null,
            shippingAddress: $shipping_dto,
            unlockedAchievementKeys: [], // This should come from AchievementRepository
            customFields: (object) [
                'definitions' => $custom_fields_definitions,
                'values'      => (object) $custom_fields_values,
            ]
        );

        return $profile_dto;
    }

    public function get_current_user_full_profile_data(): FullProfileDTO {
        $current_user = auth()->user();
        if (!$current_user) {
            throw new Exception("User not authenticated.", 401);
        }
        return $this->get_full_profile_data(UserId::fromInt($current_user->id));
    }

    public function get_user_dashboard_data(UserId $userId): array {
        return [
            'lifetime_points' => $this->userRepo->getLifetimePoints($userId),
        ];
    }
    
    public function request_password_reset(string $email): void {
        // Use Laravel's built-in password reset functionality directly
        $broker = Password::broker();
        $response = $broker->sendResetLink(['email' => $email]);

        if ($response === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent to ' . $email);
        } else {
            Log::error('Could not send password reset link to ' . $email . '. Response: ' . $response);
        }
    }

    public function perform_password_reset(string $token, string $email, string $password): void {
        // Use Laravel's built-in password reset functionality
        $credentials = [
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'token' => $token,
        ];

        // Debug logging
        \Log::info('Password reset attempt', [
            'email' => $email,
            'token' => $token,
            'user_exists_in_db' => \App\Models\User::where('email', $email)->exists(),
            'token_exists_in_db' => DB::table('password_reset_tokens')->where('email', $email)->exists()
        ]);

        $broker = Password::broker();
        $response = $broker->reset($credentials, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($response !== Password::PASSWORD_RESET) {
            // More detailed logging for debugging
            \Log::error('Password reset failed', [
                'response' => $response,
                'email' => $email,
                'token' => $token,
                'user_exists_in_db' => \App\Models\User::where('email', $email)->exists(),
                'token_exists_in_db' => DB::table('password_reset_tokens')->where('email', $email)->exists(),
                'expected_responses' => [
                'PASSWORD_RESET' => Password::PASSWORD_RESET,
                'INVALID_USER' => Password::INVALID_USER,
                'INVALID_TOKEN' => Password::INVALID_TOKEN,
                'THROTTLED' => 'passwords.throttled'
            ]
            ]);
            
            $message = match($response) {
                Password::INVALID_USER => 'The given email address does not exist.',
                Password::INVALID_TOKEN => 'Your password reset token is invalid or has expired.',
                'passwords.throttled' => 'Too many reset attempts. Please try again later.',
                default => 'Your password reset token is invalid or has expired.'
            };
            
            throw new HttpException(400, $message);
        } else {
            \Log::info('Password reset succeeded');
        }
    }
    
    public function login(string $username, string $password): array {
        // Find the user by email
        $user = \App\Models\User::where('email', $username)->first();
        
        if (!$user || !\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
            throw new Exception('Could not generate authentication token after registration.');
        }
        
        // Revoke all existing tokens for this user
        $user->tokens()->delete();
        
        // Create a new Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Get first and last name from user meta
        $first_name = $user->meta['first_name'] ?? $user->name;
        $last_name = $user->meta['last_name'] ?? '';
        
        return [
            'success' => true,
            'data' => [
                'token' => $token,
                'user_email' => $user->email,
                'user_nicename' => $first_name,
                'user_display_name' => $user->name,
            ]
        ];
    }
}