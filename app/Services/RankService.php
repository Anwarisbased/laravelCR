<?php
namespace App\Services;

use App\Domain\ValueObjects\Points;
use App\Domain\ValueObjects\RankKey;
use App\Repositories\UserRepository;
use App\DTO\RankDTO;
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
        
        // This will find the 'member' rank DTO from the structure, or a default if not found
        return $this->getRankByKey('member');
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

        // For now, return a default rank structure since we're moving away from WordPress CPTs
        $ranks = [];

        // Default ranks - in a full implementation, these would come from a proper Eloquent model
        $ranks[] = new RankDTO(
            key: RankKey::fromString('bronze'),
            name: 'Bronze',
            pointsRequired: Points::fromInt(1000),
            pointMultiplier: 1.2
        );
        
        $ranks[] = new RankDTO(
            key: RankKey::fromString('silver'),
            name: 'Silver',
            pointsRequired: Points::fromInt(5000),
            pointMultiplier: 1.5
        );
        
        $ranks[] = new RankDTO(
            key: RankKey::fromString('gold'),
            name: 'Gold',
            pointsRequired: Points::fromInt(10000),
            pointMultiplier: 2.0
        );

        $memberRank = new RankDTO(
            key: RankKey::fromString('member'),
            name: 'Member',
            pointsRequired: Points::fromInt(0),
            pointMultiplier: 1.0 // Members get a 1.0x multiplier
        );
        $ranks[] = $memberRank;

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
}