<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ReferralStatsData extends Data
{
    public function __construct(
        public int $totalReferrals,
        public int $convertedReferrals,
        public float $conversionRate,
    ) {
    }

    public static function fromServiceResponse(array $stats): self
    {
        try {
            return new self(
                totalReferrals: $stats['total_referrals'],
                convertedReferrals: $stats['converted_referrals'],
                conversionRate: $stats['conversion_rate'],
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'Referral stats array',
                self::class,
                $e->getMessage()
            );
        }
    }
}