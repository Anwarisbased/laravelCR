<?php
namespace App\Services;

use App\Commands\GrantPointsCommand;
use App\Commands\GrantPointsCommandHandler;
use App\Includes\EventBusInterface;
use App\Repositories\ProductRepository;

final class StandardScanService {
    private ProductRepository $productRepo;
    private GrantPointsCommandHandler $grantPointsHandler;
    private EventBusInterface $eventBus;

    public function __construct(
        ProductRepository $productRepo,
        GrantPointsCommandHandler $grantPointsHandler,
        EventBusInterface $eventBus
    ) {
        $this->productRepo = $productRepo;
        $this->grantPointsHandler = $grantPointsHandler;
        $this->eventBus = $eventBus;
    }

    public function grantPointsForStandardScan(array $payload): void {
        \Illuminate\Support\Facades\Log::info('StandardScanService.grantPointsForStandardScan called', ['payload' => $payload]);
        
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        $product_id = $payload['product_snapshot']['identity']['product_id'] ?? 0;
        $product_name = $payload['product_snapshot']['identity']['product_name'] ?? 'product';

        // NO MORE CONDITIONAL CHECK! This method only runs for standard scans.
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