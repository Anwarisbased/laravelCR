<?php
namespace App\Commands;

use App\Domain\ValueObjects\OrderId;
use App\Domain\ValueObjects\Points;
use App\DTO\RedeemRewardResultDTO;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ActionLogRepository;
use App\Services\ActionLogService;
use App\Services\ContextBuilderService;
use App\Includes\EventBusInterface; // <<<--- IMPORT INTERFACE
use App\Infrastructure\WordPressApiWrapperInterface; // <<<--- IMPORT INTERFACE
use Exception;

final class RedeemRewardCommandHandler {
    private ProductRepository $productRepo;
    private UserRepository $userRepo;
    private OrderRepository $orderRepo;
    private ActionLogRepository $logRepo;
    private ActionLogService $logService;
    private ContextBuilderService $contextBuilder;
    private EventBusInterface $eventBus; // <<<--- ADD PROPERTY
    private WordPressApiWrapperInterface $wp; // <<<--- CHANGE TO INTERFACE

    public function __construct(
        ProductRepository $productRepo,
        UserRepository $userRepo,
        OrderRepository $orderRepo,
        ActionLogService $logService,
        ContextBuilderService $contextBuilder,
        ActionLogRepository $logRepo,
        EventBusInterface $eventBus, // <<<--- ADD DEPENDENCY
        WordPressApiWrapperInterface $wp // <<<--- CHANGE TO INTERFACE
    ) {
        $this->productRepo = $productRepo;
        $this->userRepo = $userRepo;
        $this->orderRepo = $orderRepo;
        $this->logService = $logService;
        $this->contextBuilder = $contextBuilder;
        $this->logRepo = $logRepo;
        $this->eventBus = $eventBus; // <<<--- ASSIGN PROPERTY
        $this->wp = $wp; // <<<--- ASSIGN WRAPPER PROPERTY
    }

    public function handle(RedeemRewardCommand $command): RedeemRewardResultDTO {
        $user_id = $command->userId->toInt();
        $product_id = $command->productId->toInt();
        
        $points_cost = $this->productRepo->getPointsCost($command->productId);
        $current_balance = $this->userRepo->getPointsBalance($command->userId);
        $new_balance = $current_balance - $points_cost;

        $order_id = $this->orderRepo->createFromRedemption($user_id, $product_id, $command->shippingDetails);
        if (!$order_id) { throw new Exception('Failed to create order for redemption.'); }

        $this->userRepo->saveShippingAddress($command->userId, $command->shippingDetails);
        $this->userRepo->savePointsAndRank($command->userId, $new_balance, $this->userRepo->getLifetimePoints($command->userId), $this->userRepo->getCurrentRankKey($command->userId));

        $product_name = $this->wp->getTheTitle($product_id);
        $log_meta_data = ['description' => 'Redeemed: ' . $product_name, 'points_change' => -$points_cost, 'new_balance' => $new_balance, 'order_id' => $order_id];
        $this->logService->record($user_id, 'redeem', $product_id, $log_meta_data);
        
        $full_context = $this->contextBuilder->build_event_context($user_id, $this->wp->getPost($product_id));
        
        // REFACTOR: Use the injected event bus
        $this->eventBus->dispatch('reward_redeemed', $full_context);
        
        return new RedeemRewardResultDTO(
            OrderId::fromInt($order_id),
            Points::fromInt($new_balance)
        );
    }
}