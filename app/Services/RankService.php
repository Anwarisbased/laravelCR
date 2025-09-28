<?php
namespace App\Services;

use App\Domain\ValueObjects\Points;
use App\Domain\ValueObjects\RankKey;
use App\Models\Rank;
use App\Models\User;
use App\Repositories\UserRepository;
use App\DTO\RankDTO;
use App\Events\UserRankChanged;
use App\Jobs\UpdateUserRankBenefits;
use App\Jobs\NotifyRankChange;
use Illuminate\Support\Facades\Cache;

final class RankService {
    private UserRepository $userRepository;
    private ?array $rankStructureCache = null;

    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
        // The constructor is now lean. The cache will be loaded on-demand.
    }

    /**
     * Get the full RankDTO for a specific rank key.
     */
    public function getRankByKey(string $rankKey): ?RankDTO {
        $ranks = $this->getRankStructure();
        foreach ($ranks as $rank) {
            if ((string)$rank->key === $rankKey) {
                return $rank;
            }
        }
        return null;
    }

    /**
     * Get user's rank from Eloquent model (for direct model usage)
     */
    public function getUserRankFromModel(User $user): ?Rank
    {
        $lifetimePoints = $user->lifetime_points ?? 0;
        
        $ranks = $this->loadRankStructureFromModel();
        
        // Find the highest rank the user qualifies for
        $qualifyingRanks = $ranks->filter(
            fn($rank) => $rank->qualifiesFor($lifetimePoints)
        );
        
        return $qualifyingRanks->last() ?? $this->getDefaultRank();
    }

    public function getUserLifetimePoints(\App\Domain\ValueObjects\UserId $userId): int {
        return $this->userRepository->getLifetimePoints($userId);
    }

    public function getUserRank(\App\Domain\ValueObjects\UserId $userId): RankDTO {
        $lifetimePoints = $this->userRepository->getLifetimePoints($userId);
        $ranks = $this->getRankStructure();

        foreach ($ranks as $rank) {
            if ($lifetimePoints >= $rank->pointsRequired->toInt()) {
                return $rank; // The first one we hit is the correct one due to DESC sorting
            }
        }
        
        // If no specific rank is found, return the lowest rank (member or first in list)
        $defaultRank = $this->getRankByKey('member');
        if ($defaultRank) {
            return $defaultRank;
        }
        
        // Fallback to the last rank in the array (which would be the lowest due to DESC sorting)
        return end($ranks) ?: new RankDTO(
            key: RankKey::fromString('member'),
            name: 'Member',
            pointsRequired: Points::fromInt(0),
            pointMultiplier: 1.0
        );
    }

    public function getRankStructure(): array {
        if ($this->rankStructureCache !== null) {
            return $this->rankStructureCache;
        }

        $cacheKey = 'canna_rank_structure_dtos_v2';
        $cachedRanks = Cache::get($cacheKey);
        if (is_array($cachedRanks)) {
            $this->rankStructureCache = $cachedRanks;
            return $this->rankStructureCache;
        }

        // Fetch ranks from the Eloquent model instead of using defaults
        $eloquentRanks = $this->loadRankStructureFromModel();
        
        // Convert Eloquent models to DTOs
        $ranks = [];
        foreach ($eloquentRanks as $eloquentRank) {
            $ranks[] = new RankDTO(
                key: RankKey::fromString($eloquentRank->key),
                name: $eloquentRank->name,
                pointsRequired: Points::fromInt($eloquentRank->points_required),
                pointMultiplier: $eloquentRank->point_multiplier
            );
        }

        // Ensure ranks are unique and sorted correctly (DESC order by points required)
        $uniqueRanks = [];
        foreach ($ranks as $rank) {
            $uniqueRanks[(string)$rank->key] = $rank;
        }
        $ranks = array_values($uniqueRanks);
        usort($ranks, fn($a, $b) => $b->pointsRequired->toInt() <=> $a->pointsRequired->toInt());
        
        Cache::put($cacheKey, $ranks, 12 * 60 * 60); // 12 hours in seconds
        $this->rankStructureCache = $ranks;

        return $this->rankStructureCache;
    }

    /**
     * Load rank structure from Eloquent models
     */
    private function loadRankStructureFromModel()
    {
        return Cache::remember('rank_structure', 3600, function () {
            return Rank::active()->ordered()->get();
        });
    }

    /**
     * Get default rank
     */
    private function getDefaultRank(): ?Rank
    {
        return Rank::where('key', 'member')->first() 
            ?? Rank::orderBy('points_required')->first();
    }

    /**
     * Recalculate user rank and update if necessary
     */
    public function recalculateUserRank(User $user): Rank
    {
        $lifetimePoints = $user->lifetime_points ?? 0;
        $newRank = $this->getUserRankFromModel($user);
        $currentRankKey = $user->current_rank_key;

        if ($newRank && $currentRankKey !== $newRank->key) {
            $user->current_rank_key = $newRank->key;
            $user->save();
            
            // Fire event for rank change
            event(new UserRankChanged($user, $newRank));
            
            // Dispatch jobs for follow-up actions
            UpdateUserRankBenefits::dispatch($user);
            NotifyRankChange::dispatch($user, $newRank);
        }

        return $newRank;
    }
}