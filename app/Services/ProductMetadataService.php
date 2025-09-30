<?php

namespace App\Services;

use App\Models\Product;

class ProductMetadataService
{
    public function getFormattedProductData(Product $product): array
    {
        return [
            "id" => $product->id,
            "name" => $product->name,
            "sku" => $product->sku,
            "description" => $product->description,
            "short_description" => $product->short_description,
            "points_award" => $product->points_award,
            "points_cost" => $product->points_cost,
            "required_rank" => $product->required_rank_key,
            "category" => $product->category ? [
                "id" => $product->category->id,
                "name" => $product->category->name,
                "slug" => $product->category->slug,
            ] : null,
            "brand" => $product->brand,
            "strain_type" => $product->strain_type,
            "thc_content" => $product->thc_content,
            "cbd_content" => $product->cbd_content,
            "product_form" => $product->product_form,
            "marketing_snippet" => $product->marketing_snippet,
            "images" => $this->formatImages($product->image_urls),
            "tags" => $product->tags,
            "is_featured" => $product->is_featured,
            "is_new" => $product->is_new,
            "availability_dates" => [
                "from" => $product->available_from,
                "until" => $product->available_until,
            ],
        ];
    }
    
    protected function formatImages(array $imageUrls): array
    {
        return array_map(function ($url) {
            return [
                "url" => $url,
                "thumbnail" => $this->generateThumbnailUrl($url),
                "alt" => "Product image",
            ];
        }, $imageUrls);
    }
    
    protected function generateThumbnailUrl(string $originalUrl): string
    {
        // Implement thumbnail generation logic
        return str_replace("/images/", "/images/thumbnails/", $originalUrl);
    }
}
