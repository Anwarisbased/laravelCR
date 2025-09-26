<?php

namespace App\Policies;

use App\Repositories\RewardCodeRepository;
use Exception;
use Illuminate\Support\Facades\Log;

final class UnauthenticatedCodeIsValidPolicy implements ValidationPolicyInterface {
    private RewardCodeRepository $rewardCodeRepository;
    
    public function __construct(RewardCodeRepository $rewardCodeRepository) {
        $this->rewardCodeRepository = $rewardCodeRepository;
    }
    
    public function check($value): void {
        
        $validCode = $this->rewardCodeRepository->findValidCode($value);
        if ($validCode === null) {
            // Add the 409 status code to the exception
            Log::error("Throwing exception with code 409 for invalid code: " . $value);
            throw new Exception("The reward code {$value} is invalid or has already been used.", 409);
        }
    }
}