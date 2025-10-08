<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class AchievementData extends Data
{
    public function __construct(
        #[MapName('achievement_key')]
        #[Validation(['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_-]+$/'])]
        public string $key,
        #[Validation(['required', 'string', 'max:255'])]
        public string $title,
        #[Validation(['nullable', 'string', 'max:1000'])]
        public ?string $description = null,
        #[MapName('points_reward')]
        #[Validation(['integer', 'min:0'])]
        public int $pointsReward = 0,
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $rarity = null,
        #[MapName('icon_url')]
        #[Validation(['nullable', 'url', 'max:500'])]
        public ?string $iconUrl = null,
        #[MapName('is_active')]
        #[Validation(['boolean'])]
        public bool $isActive = true,
        #[MapName('trigger_event')]
        #[Validation(['nullable', 'string', 'max:100'])]
        public ?string $triggerEvent = null,
        #[MapName('trigger_count')]
        #[Validation(['integer', 'min:1'])]
        public int $triggerCount = 1,
        #[Validation(['array'])]
        public ?array $conditions = [],
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $category = null,
        #[MapName('sort_order')]
        #[Validation(['integer', 'min:0'])]
        public int $sortOrder = 0,
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $type = null,
        #[MapName('created_at')]
        public ?string $createdAt = null,
        #[MapName('updated_at')]
        public ?string $updatedAt = null,
    ) {
    }

    public static function fromModel(\App\Models\Achievement $achievement): self
    {
        try {
            return new self(
                key: $achievement->achievement_key,
                title: $achievement->title,
                description: $achievement->description,
                pointsReward: $achievement->points_reward,
                rarity: $achievement->rarity,
                iconUrl: $achievement->icon_url,
                isActive: $achievement->is_active,
                triggerEvent: $achievement->trigger_event,
                triggerCount: $achievement->trigger_count,
                conditions: $achievement->conditions,
                category: $achievement->category,
                sortOrder: $achievement->sort_order,
                type: $achievement->type,
                createdAt: $achievement->created_at,
                updatedAt: $achievement->updated_at,
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\Achievement::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}