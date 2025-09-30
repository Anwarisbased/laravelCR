<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Repositories\ActionLogRepository;
use Illuminate\Support\Facades\Config;

class ProductEligibilityService
{
    protected $economyService;
    protected $rankService;
    protected $actionLogRepository;

    public function __construct(
        EconomyService $economyService,
        RankService $rankService,
        ActionLogRepository $actionLogRepository
    ) {
        $this->economyService = $economyService;
        $this->rankService = $rankService;
        $this->actionLogRepository = $actionLogRepository;
    }

    public function checkEligibility(User $user, Product $product): array
    {
        $eligibility = [
            'is_eligible' => true,
            'reasons' => [],
            'eligible_for_free_claim' => false,
        ];

        // Check if user can afford the product
        if (!$this->canAfford($user, $product)) {
            $eligibility['is_eligible'] = false;
            $eligibility['reasons'][] = 'insufficient_points';
        }

        // Check rank requirements
        if (!$this->meetsRankRequirement($user, $product)) {
            $eligibility['is_eligible'] = false;
            $eligibility['reasons'][] = 'rank_requirement_not_met';
        }

        // Check if eligible for free claim (welcome gift or referral gift)
        $eligibility['eligible_for_free_claim'] = $this->isEligibleForFreeClaim($user, $product);

        return $eligibility;
    }

    protected function canAfford(User $user, Product $product): bool
    {
        return $user->points_balance >= $product->points_cost;
    }

    protected function meetsRankRequirement(User $user, Product $product): bool
    {
        if (empty($product->required_rank_key)) {
            return true;
        }

        $userRank = $this->rankService->getUserRank($user);
        $requiredRank = $this->rankService->getRankByKey($product->required_rank_key);

        if (!$requiredRank) {
            return true; // Invalid rank requirement
        }

        // Check if user's rank meets or exceeds required rank
        return $userRank->pointsRequired->toInt() >= $requiredRank->pointsRequired->toInt();
    }

    protected function isEligibleForFreeClaim(User $user, Product $product): bool
    {
        // Check if this is a welcome gift or referral gift
        $welcomeGiftId = Config::get('cannarewards.welcome_gift_product_id');
        $referralGiftId = Config::get('cannarewards.referral_sign_up_gift_id');

        if ($product->id !== $welcomeGiftId && $product->id !== $referralGiftId) {
            return false;
        }

        // Check if user has scanned 0 or 1 products (eligible for free claim)
        $scanCount = $this->actionLogRepository->countUserActions($user->id, 'scan');
        return $scanCount <= 1;
    }
}