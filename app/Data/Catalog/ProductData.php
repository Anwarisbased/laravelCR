<?php

namespace App\Data\Catalog;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use App\Data\Catalog\CategoryData;

#[MapName(SnakeCaseMapper::class)]
class ProductData extends Data
{
    public function __construct(
        public int $id,
        #[Validation(['required', 'string', 'max:255'])]
        public string $name,
        #[Validation(['required', 'string', 'max:255', 'regex:/^[A-Z0-9_-]+$/'])]
        public string $sku,
        #[Validation(['nullable', 'string', 'max:1000'])]
        public ?string $description = null,
        #[MapName('short_description')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $shortDescription = null,
        #[Validation(['integer', 'min:0'])]
        public int $pointsAward = 0,
        #[Validation(['integer', 'min:0'])]
        public int $pointsCost = 0,
        #[MapName('required_rank_key')]
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $requiredRankKey = null,
        #[Validation(['boolean'])]
        public bool $isActive = false,
        #[Validation(['boolean'])]
        public bool $isFeatured = false,
        #[Validation(['boolean'])]
        public bool $isNew = false,
        #[MapName('category_id')]
        #[Validation(['nullable', 'integer'])]
        public ?int $categoryId = null,
        public ?CategoryData $category = null,
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $brand = null,
        #[MapName('strain_type')]
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $strainType = null,
        #[MapName('thc_content')]
        #[Validation(['nullable', 'numeric', 'between:0,100'])]
        public ?float $thcContent = null,
        #[MapName('cbd_content')]
        #[Validation(['nullable', 'numeric', 'between:0,100'])]
        public ?float $cbdContent = null,
        #[MapName('product_form')]
        #[Validation(['nullable', 'string', 'max:50'])]
        public ?string $productForm = null,
        #[MapName('marketing_snippet')]
        #[Validation(['nullable', 'string', 'max:255'])]
        public ?string $marketingSnippet = null,
        #[MapName('images')]
        #[Validation(['array'])]
        public ?array $imageUrls = [],
        #[Validation(['array'])]
        public ?array $tags = [],
        #[MapName('sort_order')]
        #[Validation(['nullable', 'integer', 'min:0'])]
        public ?int $sortOrder = null,
        #[MapName('available_from')]
        #[Validation(['nullable', 'date'])]
        public ?string $availableFrom = null,
        #[MapName('available_until')]
        #[Validation(['nullable', 'date'])]
        public ?string $availableUntil = null,
        #[MapName('created_at')]
        public ?string $createdAt = null,
        #[MapName('updated_at')]
        public ?string $updatedAt = null,
        #[MapName('meta_data')]
        #[Validation(['array'])]
        public ?array $metaData = [],
        public ?EligibilityData $eligibility = null,
    ) {
    }

    public static function fromModel(\App\Models\Product $product): self
    {
        try {
            return new self(
                id: $product->id,
                name: $product->name,
                sku: $product->sku,
                description: $product->description,
                shortDescription: $product->short_description,
                pointsAward: $product->points_award,
                pointsCost: $product->points_cost,
                requiredRankKey: $product->required_rank_key,
                isActive: $product->is_active,
                isFeatured: $product->is_featured,
                isNew: $product->is_new,
                categoryId: $product->category_id,
                category: $product->category ? CategoryData::fromModel($product->category) : null,
                brand: $product->brand,
                strainType: $product->strain_type,
                thcContent: $product->thc_content,
                cbdContent: $product->cbd_content,
                productForm: $product->product_form,
                marketingSnippet: $product->marketing_snippet,
                imageUrls: $product->image_urls,
                tags: $product->tags,
                sortOrder: $product->sort_order,
                availableFrom: $product->available_from,
                availableUntil: $product->available_until,
                createdAt: $product->created_at,
                updatedAt: $product->updated_at,
                metaData: $product->meta_data ?? [],
                eligibility: $product->eligibility ? EligibilityData::fromArray($product->eligibility) : null,
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\Product::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}