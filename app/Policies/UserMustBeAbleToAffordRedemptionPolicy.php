<?php
namespace App\Policies;

use App\Commands\RedeemRewardCommand;
use App\Domain\ValueObjects\UserId;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use Exception;

class UserMustBeAbleToAffordRedemptionPolicy implements AuthorizationPolicyInterface {
    public function __construct(
        private ProductRepository $productRepo,
        private UserRepository $userRepo
    ) {}

    public function check(UserId $userId, object $command): void {
        if (!$command instanceof RedeemRewardCommand) {
            return;
        }
        
        $pointsCost = $this->productRepo->getPointsCost($command->productId);
        $currentBalance = $this->userRepo->getPointsBalance($userId);

        if ($currentBalance < $pointsCost) {
            // 402 Payment Required is the semantically correct HTTP code.
            throw new Exception('Insufficient points.', 402);
        }
    }
}