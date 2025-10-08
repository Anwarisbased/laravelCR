<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ReferralCollectionData extends Data
{
    /**
     * @param ReferralData[] $referrals
     * @param ReferralStatsData $stats
     */
    public function __construct(
        public array $referrals,
        public ReferralStatsData $stats,
    ) {
    }

    public static function fromServiceResponse(array $referrals, array $stats): self
    {
        try {
            $referralData = [];
            foreach ($referrals as $referral) {
                $referralData[] = ReferralData::fromServiceResponse($referral);
            }

            return new self(
                referrals: $referralData,
                stats: ReferralStatsData::fromServiceResponse($stats),
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'Referral collection',
                self::class,
                $e->getMessage()
            );
        }
    }
}