<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class ReferralData extends Data
{
    public function __construct(
        public int $id,
        #[Validation(['email'])]
        public string $inviteeEmail,
        public string $status,
        public ?string $convertedAt,
        public ?int $bonusPointsAwarded,
        public string $createdAt,
    ) {
    }

    public static function fromServiceResponse(array $referral): self
    {
        try {
            return new self(
                id: $referral['id'],
                inviteeEmail: $referral['invitee_email'],
                status: $referral['status'],
                convertedAt: $referral['converted_at'],
                bonusPointsAwarded: $referral['bonus_points_awarded'],
                createdAt: $referral['created_at'],
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'Referral array',
                self::class,
                $e->getMessage()
            );
        }
    }
}


