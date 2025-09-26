<?php
namespace App\Services;

use App\Commands\RedeemRewardCommand;
use App\Commands\RedeemRewardCommandHandler;
use App\Includes\EventBusInterface;
use App\Domain\ValueObjects\UserId;

final class FirstScanBonusService {
    private ConfigService $configService;
    private RedeemRewardCommandHandler $redeemHandler;
    private EventBusInterface $eventBus;

    public function __construct(
        ConfigService $configService,
        RedeemRewardCommandHandler $redeemHandler,
        EventBusInterface $eventBus
    ) {
        $this->configService = $configService;
        $this->redeemHandler = $redeemHandler;
        $this->eventBus = $eventBus;
    }

    public function awardWelcomeGift(array $payload): void {
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        
        // NO MORE CONDITIONAL CHECK! This method only runs for first scans.
        if ($user_id > 0) {
            $welcome_reward_id = $this->configService->getWelcomeRewardProductId();
            if ($welcome_reward_id > 0) {
                $this->redeemHandler->handle(new RedeemRewardCommand(UserId::fromInt($user_id), \App\Domain\ValueObjects\ProductId::fromInt($welcome_reward_id), []));
            }
        }
    }
}