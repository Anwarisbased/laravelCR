<?php
namespace App\Commands;

use App\Commands\CreateUserCommand;
use App\Events\UserCreated;
use App\Repositories\UserRepository;
use App\Services\ReferralService;
use App\Services\CDPService;
use App\Services\ConfigService;
use Exception;
use Illuminate\Support\Facades\Event;

final class CreateUserCommandHandler {
    private $user_repository;
    private $cdp_service;
    private $referral_service;
    private ConfigService $configService;

    public function __construct(
        UserRepository $user_repository,
        CDPService $cdp_service,
        ReferralService $referral_service,
        ConfigService $configService
    ) {
        $this->user_repository = $user_repository;
        $this->cdp_service = $cdp_service;
        $this->referral_service = $referral_service;
        $this->configService = $configService;
    }

    public function handle(CreateUserCommand $command): array {
        if (!$this->configService->canUsersRegister()) {
            throw new Exception('User registration is currently disabled.', 503);
        }

        if (empty($command->password)) {
            throw new Exception('A password is required.', 400);
        }

        $user_id = $this->user_repository->createUser(
            $command->email,
            $command->password,
            $command->firstName,
            $command->lastName
        );

        $user_id_vo = new \App\Domain\ValueObjects\UserId($user_id);
        $this->user_repository->saveInitialMeta($user_id_vo, $command->phone ? (string) $command->phone : '', $command->agreedToMarketing);
        $this->user_repository->savePointsAndRank($user_id_vo, 0, 0, 'member');

        // Dispatch the UserCreated event with the richer payload.
        $userIdVO = \App\Domain\ValueObjects\UserId::fromInt($user_id);
        Event::dispatch(new UserCreated([
            'user_id' => $user_id,
            'firstName' => $command->firstName,
            'referral_code' => $command->referralCode ? (string)$command->referralCode : null
        ]));
        
        $this->cdp_service->track($userIdVO, 'user_created', ['signup_method' => 'password', 'referral_code_used' => $command->referralCode]);

        return ['success' => true, 'message' => 'Registration successful.', 'userId' => $user_id];
    }
}