<?php

namespace App\Services;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Domain\MetaKeys;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\EmailAddress;
use App\Domain\ValueObjects\PhoneNumber;
use App\Domain\ValueObjects\PlainTextPassword;
use App\Domain\ValueObjects\ReferralCode;
use App\Domain\ValueObjects\ResetToken;
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
    private DataCachingService $dataCachingService;

    public function __construct(
        ContainerInterface $container, // Keep container for lazy-loading handlers/policies
        array $policy_map,
        RankService $rankService,
        CustomFieldRepository $customFieldRepo,
        UserRepository $userRepo,
        DataCachingService $dataCachingService,
        OrderRepository $orderRepo = null
    ) {
        $this->container = $container;
        $this->policy_map = $policy_map;
        $this->rankService = $rankService;
        $this->customFieldRepo = $customFieldRepo;
        $this->userRepo = $userRepo;
        $this->dataCachingService = $dataCachingService;
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
    
    public function get_user_session_data(UserId $userId): \App\Data\SessionData {
        // Try to get from cache first
        $cached = $this->dataCachingService->get(\App\Data\SessionData::class, $userId->toInt());
        if ($cached) {
            return $cached;
        }

        // Get user core data and multiple meta fields in a single optimized query
        $userDataResult = $this->userRepo->getUserCoreAndMeta($userId);
        if (!$userDataResult) {
            throw new Exception("User with ID {$userId->toInt()} not found.");
        }

        $user_data = $userDataResult['user'];
        $pointsBalance = $userDataResult['points_balance'];
        $referral_code = $userDataResult['referral_code'];

        $rank_dto = $this->rankService->getUserRank($userId);
        $shipping_data = $this->userRepo->getShippingAddressData($userId);

        // Get first and last name from user meta
        $first_name = $user_data->meta['first_name'] ?? '';
        $last_name = $user_data->meta['last_name'] ?? '';

        // Create SessionUserDTO as before, extracting values from shipping_data
        $session_dto = new \App\DTO\SessionUserDTO(
            id: $userId,
            firstName: $first_name,
            lastName: $last_name,
            email: \App\Domain\ValueObjects\EmailAddress::fromString($user_data->email),
            pointsBalance: \App\Domain\ValueObjects\Points::fromInt($pointsBalance),
            rank: $rank_dto,
            shippingAddress: new \App\DTO\ShippingAddressDTO(
                firstName: $shipping_data->firstName,
                lastName: $shipping_data->lastName,
                address1: $shipping_data->address1,
                city: $shipping_data->city,
                state: $shipping_data->state,
                postcode: $shipping_data->postcode
            ),
            referralCode: $referral_code,
            featureFlags: new \stdClass()
        );

        // Convert to SessionData using the fromSessionDto method
        $sessionData = \App\Data\SessionData::fromSessionDto($session_dto);

        // Cache for 1 hour
        $this->dataCachingService->put(\App\Data\SessionData::class, $userId->toInt(), $sessionData, 60);

        return $sessionData;
    }
    
    public function get_current_user_session_data(): \App\Data\SessionData {
        $current_user = auth()->user();
        if (!$current_user) {
            throw new Exception("User not authenticated.", 401);
        }
        return $this->get_user_session_data(UserId::fromInt($current_user->id));
    }
    
    public function get_full_profile_data(UserId $userId): \App\Data\ProfileData {
        // Try to get from cache first
        $cached = $this->dataCachingService->get(\App\Data\ProfileData::class, $userId->toInt());
        if ($cached) {
            return $cached;
        }

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
        
        $shipping_data = $this->userRepo->getShippingAddressData($userId);

        // Get phone number and referral code from user meta, converting to Value Objects
        $phone_meta = $this->userRepo->getUserMeta($userId, 'phone_number', true);
        // Get referral code using the optimized method
        $referral_code_meta = $this->userRepo->getReferralCode($userId);
        
        // Get first and last name from user meta
        $first_name = $user_data->meta['first_name'] ?? '';
        $last_name = $user_data->meta['last_name'] ?? '';
        
        // Create FullProfileDTO as before, extracting values from shipping_data
        $profile_dto = new \App\DTO\FullProfileDTO(
            firstName: $first_name,
            lastName: $last_name,
            phoneNumber: !empty($phone_meta) ? PhoneNumber::fromString($phone_meta) : null,
            referralCode: !empty($referral_code_meta) ? ReferralCode::fromString($referral_code_meta) : null,
            shippingAddress: new \App\DTO\ShippingAddressDTO(
                firstName: $shipping_data->firstName,
                lastName: $shipping_data->lastName,
                address1: $shipping_data->address1,
                city: $shipping_data->city,
                state: $shipping_data->state,
                postcode: $shipping_data->postcode
            ),
            unlockedAchievementKeys: [], // This should come from AchievementRepository
            customFields: (object) [
                'definitions' => $custom_fields_definitions,
                'values'      => (object) $custom_fields_values,
            ]
        );

        // Convert to ProfileData using the fromProfileDto method
        $profileData = \App\Data\ProfileData::fromProfileDto($profile_dto);

        // Cache for 1 hour
        $this->dataCachingService->put(\App\Data\ProfileData::class, $userId->toInt(), $profileData, 60);

        return $profileData;
    }

    public function get_current_user_full_profile_data(): \App\Data\ProfileData {
        $current_user = auth()->user();
        if (!$current_user) {
            throw new Exception("User not authenticated.", 401);
        }
        return $this->get_full_profile_data(UserId::fromInt($current_user->id));
    }
    
    public function getUserData(UserId $userId): ?\App\Data\UserData
    {
        // Try to get from cache first
        $cached = $this->dataCachingService->get(\App\Data\UserData::class, $userId->toInt());
        if ($cached) {
            return $cached;
        }

        $user = $this->userRepo->getUserCoreData($userId);
        if (!$user) {
            return null;
        }
        
        $userData = \App\Data\UserData::fromModel($user);

        // Cache for 1 hour
        $this->dataCachingService->put(\App\Data\UserData::class, $userId->toInt(), $userData, 60);

        return $userData;
    }
    
    public function get_current_user_data(): ?\App\Data\UserData
    {
        $current_user = auth()->user();
        if (!$current_user) {
            return null;
        }
        return $this->getUserData(UserId::fromInt($current_user->id));
    }

    public function get_user_dashboard_data(UserId $userId): \App\Data\DashboardData {
        // Try to get from cache first
        $cached = $this->dataCachingService->get(\App\Data\DashboardData::class, $userId->toInt());
        if ($cached) {
            return $cached;
        }

        // Using the optimized method to get meta data
        $userDataResult = $this->userRepo->getUserCoreAndMeta($userId);
        $lifetimePoints = $userDataResult ? $userDataResult['lifetime_points'] : 0;

        $dashboardData = \App\Data\DashboardData::fromServiceResponse(
            $lifetimePoints
        );

        // Cache for 30 minutes
        $this->dataCachingService->put(\App\Data\DashboardData::class, $userId->toInt(), $dashboardData, 30);

        return $dashboardData;
    }
    
    public function request_password_reset(EmailAddress $email): void {
        // Use Laravel's built-in password reset functionality directly
        $broker = Password::broker();
        $response = $broker->sendResetLink(['email' => $email->value]);

        if ($response === Password::RESET_LINK_SENT) {
            Log::info('Password reset link sent to ' . $email->value);
        } else {
            Log::error('Could not send password reset link to ' . $email->value . '. Response: ' . $response);
        }
    }

    public function perform_password_reset($token, $email, $password): void {
        // Convert to value objects if raw values provided
        $tokenVO = is_string($token) ? \App\Domain\ValueObjects\ResetToken::fromString($token) : $token;
        $emailVO = is_string($email) ? \App\Domain\ValueObjects\EmailAddress::fromString($email) : $email;
        $passwordVO = is_string($password) ? \App\Domain\ValueObjects\PlainTextPassword::fromString($password) : $password;

        // Use Laravel's built-in password reset functionality
        $credentials = [
            'email' => $emailVO->value,
            'password' => $passwordVO->value,
            'password_confirmation' => $passwordVO->value,
            'token' => $tokenVO->value,
        ];

        // Debug logging
        \Log::info('Password reset attempt', [
            'email' => $emailVO->value,
            'token' => $tokenVO->value,
            'user_exists_in_db' => \App\Models\User::where('email', $emailVO->value)->exists(),
            'token_exists_in_db' => DB::table('password_reset_tokens')->where('email', $emailVO->value)->exists()
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
                'email' => $emailVO->value,
                'token' => $token,
                'user_exists_in_db' => \App\Models\User::where('email', $emailVO->value)->exists(),
                'token_exists_in_db' => DB::table('password_reset_tokens')->where('email', $emailVO->value)->exists(),
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
    
    public function login(EmailAddress $email, PlainTextPassword $password): \App\Data\LoginResponseData {
        // Find the user by email
        $user = \App\Models\User::where('email', $email->value)->first();
        
        if (!$user || !\Illuminate\Support\Facades\Hash::check($password->value, $user->password)) {
            throw new Exception('Could not generate authentication token after registration.');
        }
        
        // Revoke all existing tokens for this user
        $user->tokens()->delete();
        
        // Create a new Sanctum token
        $token = $user->createToken('auth-token')->plainTextToken;
        
        // Get first and last name from user meta
        $first_name = $user->meta['first_name'] ?? $user->name;
        $last_name = $user->meta['last_name'] ?? '';
        
        return \App\Data\LoginResponseData::fromServiceResponse(
            $token,
            $user->email,
            $first_name,
            $user->name
        );
    }
}