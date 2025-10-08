<?php

namespace App\Data\Catalog;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class EligibilityData extends Data
{
    public function __construct(
        #[Validation(['boolean'])]
        public bool $isEligible,
        #[Validation(['array'])]
        public array $reasons,
        #[Validation(['boolean'])]
        public bool $eligibleForFreeClaim,
    ) {
    }
    
    public static function fromArray(array $data): self
    {
        try {
            return new self(
                isEligible: $data['is_eligible'] ?? $data['isEligible'] ?? false,
                reasons: $data['reasons'] ?? [],
                eligibleForFreeClaim: $data['eligible_for_free_claim'] ?? $data['eligibleForFreeClaim'] ?? false
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'array',
                self::class,
                $e->getMessage()
            );
        }
    }

    /**
     * Create from model data (for direct use from Eloquent model attributes)
     */
    public static function fromModelData($eligibilityData): self
    {
        try {
            if (is_array($eligibilityData)) {
                return self::fromArray($eligibilityData);
            }
            
            // If it's already an instance of EligibilityData, return it
            if ($eligibilityData instanceof self) {
                return $eligibilityData;
            }
            
            // If it's null, return a default instance
            if ($eligibilityData === null) {
                return new self(
                    isEligible: false,
                    reasons: [],
                    eligibleForFreeClaim: false
                );
            }
            
            // Default return
            return new self(
                isEligible: false,
                reasons: [],
                eligibleForFreeClaim: false
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                gettype($eligibilityData),
                self::class,
                $e->getMessage()
            );
        }
    }
}