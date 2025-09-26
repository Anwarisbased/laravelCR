<?php
namespace App\Services;

use App\Commands\GrantPointsCommand;
use App\Commands\GrantPointsCommandHandler;
use App\Repositories\ProductRepository;

final class StandardScanService {
    public function __construct(
        private ProductRepository $productRepo,
        private GrantPointsCommandHandler $grantPointsHandler
    ) {}

    public function grantPointsForStandardScan(array $payload): void {
        $user_id = $payload['user_snapshot']['identity']['user_id'] ?? 0;
        $product_id = $payload['product_snapshot']['identity']['product_id'] ?? 0;
        $product_name = $payload['product_snapshot']['identity']['product_name'] ?? 'product';

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