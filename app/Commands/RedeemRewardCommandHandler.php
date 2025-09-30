<?php
namespace App\Commands;

use App\Domain\ValueObjects\OrderId;
use App\Domain\ValueObjects\Points;
use App\DTO\RedeemRewardResultDTO;
use App\Events\RewardRedeemed;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ActionLogRepository;
use App\Services\ActionLogService;
use App\Services\ContextBuilderService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class RedeemRewardCommandHandler {
    private ProductRepository $productRepo;
    private UserRepository $userRepo;
    private OrderRepository $orderRepo;
    private ActionLogRepository $logRepo;
    private ActionLogService $logService;
    private ContextBuilderService $contextBuilder;

    public function __construct(
        ProductRepository $productRepo,
        UserRepository $userRepo,
        OrderRepository $orderRepo,
        ActionLogService $logService,
        ContextBuilderService $contextBuilder,
        ActionLogRepository $logRepo
    ) {
        $this->productRepo = $productRepo;
        $this->userRepo = $userRepo;
        $this->orderRepo = $orderRepo;
        $this->logService = $logService;
        $this->contextBuilder = $contextBuilder;
        $this->logRepo = $logRepo;
    }

    public function handle(RedeemRewardCommand $command): RedeemRewardResultDTO {
        \Illuminate\Support\Facades\Log::info('RedeemRewardCommandHandler.handle called', [
            'user_id' => $command->userId->toInt(),
            'product_id' => $command->productId->toInt()
        ]);
        
        $user_id = $command->userId->toInt();
        $product_id = $command->productId->toInt();
        
        $points_cost = $this->productRepo->getPointsCost($command->productId);
        $current_balance = $this->userRepo->getPointsBalance($command->userId);
        $new_balance = $current_balance - $points_cost;

        $order_id = $this->orderRepo->createFromRedemption($user_id, $product_id, $command->shippingDetails);
        if (!$order_id) { 
            \Illuminate\Support\Facades\Log::error('RedeemRewardCommandHandler: Failed to create order for redemption');
            throw new Exception('Failed to create order for redemption.'); 
        }
        \Illuminate\Support\Facades\Log::info('RedeemRewardCommandHandler: Order created', [
            'order_id' => $order_id
        ]);

        $this->userRepo->saveShippingAddress($command->userId, $command->shippingDetails);
        $this->userRepo->savePointsAndRank($command->userId, $new_balance, $this->userRepo->getLifetimePoints($command->userId), $this->userRepo->getCurrentRankKey($command->userId));

        // In a pure Laravel implementation, we'd query the product from the products table
        $product = DB::table('products')->where('id', $product_id)->first();
        $product_name = $product ? $product->name : 'Reward';
        
        $log_meta_data = ['description' => 'Redeemed: ' . $product_name, 'points_change' => -$points_cost, 'new_balance' => $new_balance, 'order_id' => $order_id];
        $this->logService->record($user_id, 'redeem', $product_id, $log_meta_data);
        
        // Build context without WordPress dependencies
        $product_post = $product ? (object)['ID' => $product->id] : null;
        $full_context = $this->contextBuilder->build_event_context($user_id, $product_post);
        
        // Dispatch Laravel event instead of using custom event bus
        Event::dispatch(new RewardRedeemed($full_context));
        
        return new RedeemRewardResultDTO(
            OrderId::fromInt($order_id),
            Points::fromInt($new_balance)
        );
    }
}