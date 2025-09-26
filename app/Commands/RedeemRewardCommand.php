<?php
namespace App\Commands;

use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ProductId;

// Exit if accessed directly.

final class RedeemRewardCommand {
    public UserId $userId;
    public ProductId $productId;
    public array $shippingDetails;

    public function __construct(UserId $userId, ProductId $productId, array $shippingDetails = []) {
        $this->userId = $userId;
        $this->productId = $productId;
        $this->shippingDetails = $shippingDetails;
    }
}