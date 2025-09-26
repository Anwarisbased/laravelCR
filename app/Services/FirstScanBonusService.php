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