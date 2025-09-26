<?php
namespace App\Commands;

use App\Domain\ValueObjects\RewardCode;

/**
 * Command DTO for an unauthenticated user attempting to claim a code.
 */
final class ProcessUnauthenticatedClaimCommand {
    public RewardCode $code;

    public function __construct(RewardCode $code) {
        $this->code = $code;
    }
}