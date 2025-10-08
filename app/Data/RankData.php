<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class RankData extends Data
{
    public function __construct(
        #[Validation(['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'])]
        public string $key,
        #[Validation(['required', 'string', 'max:255'])]
        public string $name,
        #[Validation(['nullable', 'string', 'max:1000'])]
        public ?string $description = null,
        #[MapName('points_required')]
        #[Validation(['integer', 'min:0'])]
        public int $pointsRequired = 0,
        #[MapName('point_multiplier')]
        #[Validation(['numeric', 'min:0'])]
        public float $pointMultiplier = 1.0,
        #[MapName('is_active')]
        #[Validation(['boolean'])]
        public bool $isActive = true,
        #[MapName('sort_order')]
        #[Validation(['integer', 'min:0'])]
        public int $sortOrder = 0,
        #[Validation(['array'])]
        public ?array $benefits = [],
        #[MapName('created_at')]
        public ?string $createdAt = null,
        #[MapName('updated_at')]
        public ?string $updatedAt = null,
    ) {
    }

    public static function fromModel(\App\Models\Rank $rank): self
    {
        try {
            return new self(
                key: $rank->key,
                name: $rank->name,
                description: $rank->description,
                pointsRequired: $rank->points_required,
                pointMultiplier: $rank->point_multiplier,
                isActive: $rank->is_active,
                sortOrder: $rank->sort_order,
                benefits: $rank->benefits,
                createdAt: $rank->created_at,
                updatedAt: $rank->updated_at,
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\Rank::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}