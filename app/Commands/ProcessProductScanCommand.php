<?php
namespace App\Commands;

use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\RewardCode;

/**
 * Command DTO for processing a product scan.
 */
final class ProcessProductScanCommand {
    public UserId $userId;
    public RewardCode $code;

    public function __construct(UserId $userId, RewardCode $code) {
        $this->userId = $userId;
        $this->code = $code;
    }
}