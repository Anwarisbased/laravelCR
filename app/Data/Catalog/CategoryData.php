<?php

namespace App\Data\Catalog;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class CategoryData extends Data
{
    public function __construct(
        public int $id,
        #[Validation(['required', 'string', 'max:255'])]
        public string $name,
        #[Validation(['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',])]
        public string $slug,
        #[Validation(['nullable', 'string', 'max:1000'])]
        public ?string $description = null,
        #[Validation(['nullable', 'integer'])]
        public ?int $parentId = null,
        #[Validation(['nullable', 'integer', 'min:0'])]
        public ?int $sortOrder = null,
        #[Validation(['nullable', 'boolean'])]
        public ?bool $isActive = null,
    ) {
    }

    public static function fromModel(\App\Models\ProductCategory $category): self
    {
        try {
            return new self(
                id: $category->id,
                name: $category->name,
                slug: $category->slug,
                description: $category->description,
                parentId: $category->parent_id,
                sortOrder: $category->sort_order,
                isActive: $category->is_active,
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\ProductCategory::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}