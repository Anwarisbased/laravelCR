<?php
namespace App\DTO;

use App\Domain\ValueObjects\OrderId;
use App\Domain\ValueObjects\Points;

final class RedeemRewardResultDTO {
    public function __construct(
        public readonly OrderId $orderId,
        public readonly Points $newPointsBalance
    ) {}
}